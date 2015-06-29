<?php
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/calibrationutils.php");

class AutoGradeAndAssignMarkersPeerReviewScript extends Script
{
    function getName()
    {
        return "Autograde + Assign Markers";
    }
    function getDescription()
    {
        return "Assigns grades to people in the independent pool, flags items for spot checks, and assigns markers when needed";
    }

    function getFormHTML()
    {
        //TODO: Load the defaults from the config
        global $dataMgr;
        $assignment = get_peerreview_assignment();
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td width='200'>Min Reviews for Auto-Grade</td><td>";
        $html .= "<input type='text' name='minReviews' id='minReviews' value='3' size='10'/></td></tr>\n";
        $html .= "<tr><td>Auto Spot Check Probability</td><td>"; ;
        $html .= "<input type='text' name='spotCheckProb' id='spotCheckProb' value='0.25' size='10'/>(should be between 0 and 1)</td></tr>\n";
		$html .= "<tr><td>High Mark Threshold</td><td>";
        $html .= "<input type='text' name='highMarkThreshold' value='80' size='10'/>%</td></tr>\n";
		$html .= "<tr><td>High Mark Bias</td><td>";
		$html .= "<input type='text' name='highMarkBias' value='2' size='10'/></td></tr>\n";
		$html .= "<tr><td>Low Calibration Threshold</td><td>";
		$html .= "<input type='text' name='calibrationThreshold' value='8.5' size='10'/></td></tr>\n";
		$html .= "<tr><td>Calibration Bias</td><td>";
		$html .= "<input type='text' name='calibrationBias' value='1.5' size='10'/></td></tr>\n";
		$html .= "<tr><td>Seed</td><td>";
        $html .= "<input type='text' name='seed' value='$assignment->submissionStartDate' size='30'/></td></tr>\n";
        $html .= "<tr><td>&nbsp</td></tr>\n";

		$i = 0;
        foreach($dataMgr->getMarkers() as $markerID)
        {
            $html .= "<tr><td>".$dataMgr->getUserDisplayName(new UserID($markerID))."'s Load</td><td>";
            $html .= "<input class='load' id='load$i' type='text' name='load$markerID' value='".$dataMgr->getMarkingLoad(new UserID($markerID))."' size='30'/></td></tr>\n";
			$i++;
        }
        $html .= "</table>\n";
		$supervisedSubmissions = $assignment->supervisedSubmissions();
		$independentSubmissions = $assignment->independentSubmissions();
		$html .= "<h1>Estimates</h1>";
		$html .= "<table>";
		$html .= "<tr><td>Number of submissions to mark</td><td><div id='submissionstomarkestimate'></div></td></tr>\n";
		$html .= "<tr><td>Number of reviews to mark</td><td><div id='reviewstomarkestimate'></div></td></tr>\n";
		$html .= "<tr><td>Number of spot checks will be</td><td><div id='spotcheckestimate'></div></td></tr>";
		$html .= "</table>";
		$html .= "<script type='text/javascript'>";
		if($independentSubmissions)
		{
		$html .= "var independentSubsToReviewsMap = {";
			$i = 0;
			foreach($independentSubmissions as $independentSubmission => $numReviews)
			{
				if($i != 0) $html .= ", ";
				$html .= $independentSubmission." : ".$numReviews;
				$i++;
			}
		$html .= "};";
		}
		else 
		{
		$html .= "var independentSubsToReviewsMap = new Array();";
		}
		$html .= "
			var independentReviewsToMark = 0;
			var independentSubsToMark = 0;
			$.each( independentSubsToReviewsMap, function( index, value ){
				if(value < $('#minReviews').val())
				{
					independentReviewsToMark += value;
					independentSubsToMark++;
				}
			});
			var numSupervisedSubmissions = ".sizeof($supervisedSubmissions).";
			var numIndependentSubmissions = ".sizeof($independentSubmissions).";";
		$supervisedReviews = $supervisedSubmissions ? array_reduce($supervisedSubmissions, function($res, $item){return $res+$item;}) : 0;
		$html .= "var numSupervisedReviews = ".$supervisedReviews.";
			
			$('#submissionstomarkestimate').html(numSupervisedSubmissions+independentSubsToMark);
			
			$('#reviewstomarkestimate').html(numSupervisedReviews+independentReviewsToMark);
			$('#minReviews').on('input', function() {
				var independentReviewsToMark_local = 0;
				var independentSubsToMark_local = 0;
				$.each( independentSubsToReviewsMap, function( index, value ){
					if(value < $('#minReviews').val())
					{
						independentReviewsToMark_local += value;
						independentSubsToMark_local++;
					}
				});
			    $('#reviewstomarkestimate').html(numSupervisedReviews + independentReviewsToMark_local);
			    $('#submissionstomarkestimate').html(numSupervisedSubmissions + independentSubsToMark_local);
			    $('#spotcheckestimate').html(Math.ceil((numIndependentSubmissions-independentSubsToMark_local)*$('#spotCheckProb').val()));
			    independentSubsToMark = independentSubsToMark_local;
			    independentReviewsToMark = independentReviewsToMark_local;
			});
			$('#spotcheckestimate').html(Math.ceil((numIndependentSubmissions-independentSubsToMark)*$('#spotCheckProb').val()));
            $('#spotCheckProb').on('input', function() { 
			    $('#spotcheckestimate').html(Math.ceil((numIndependentSubmissions-independentSubsToMark)*this.value));
			});";
		$html .= "</script>\n";
		//Some crazy distribution predictor I set aside because ended being too much work for its worth
		/*$html .= "	
		  	var numSupervisedSubmissions = ".sizeof($supervisedSubmissions).";
		 	var totalLoad = 0;
			for(i = 0; i < $i; i++)
			{
				totalLoad += Number($('#load'+i).val());
			}
			for(i = 0; i < $i; i++)
			{
				var reviewestimate = Math.ceil( ($('#load'+i).val()/totalLoad) * numSupervisedSubmissions );
				$('#reviewestimate'+i).html(reviewestimate);
			}
			$('.load').on('input', function() {
				//Recalculate total load
				totalLoad = 0;
				for(j = 0; j < $i; j++)
				{
					totalLoad += Number($('#load'+j).val());
				}
				for(i = 0; i < $i; i++)
				{
					var reviewestimate = Math.ceil( ($('#load'+i).val()/totalLoad) * numSupervisedSubmissions );
					$('#reviewestimate'+i).html(reviewestimate);
				}
			});
			</script>\n";*/
	
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        $assignment = get_peerreview_assignment();

        $minReviews = intval(require_from_post("minReviews"));
        $highMarkThreshold = floatval(require_from_post("highMarkThreshold"))*0.01;
        mt_srand(require_from_post("seed"));
        $randomSpotCheckProb = floatval(require_from_post("spotCheckProb"));
        $userNameMap = $dataMgr->getUserDisplayMap();
        $independents = $assignment->getIndependentUsers();
		$highMarkBias = floatval(require_from_post("highMarkBias"));
		$calibrationThreshold = floatval(require_from_post("calibrationThreshold"));
		$calibrationBias = floatval(require_from_post("calibrationBias"));

        $markers = $dataMgr->getMarkers();
        mt_shuffle($markers);

        $targetLoads = array();
        $targetLoadSum = 0;
        foreach($markers as $markerID)
        {
            //TODO: Grab from post
            $targetLoads[$markerID] = floatval(require_from_post("load$markerID"));
            $targetLoadSum += $targetLoads[$markerID];
        }
        #if ($targetLoadSum == 0)
            #throw new Exception("No marker has a load value, so nothing can be assigned");
        foreach($markers as $markerID)
            $targetLoads[$markerID] /= $targetLoadSum;

        $pendingSpotChecks = array();
        $pendingSubmissions = array();

        $clearExistingAssignments = ($targetLoadSum != 0);
        if($clearExistingAssignments)
        {
            $reviewMap = $assignment->getReviewMap();
            foreach($reviewMap as $submissionID=>$reviews)
            {
                foreach($reviews as $reviewObj)
                {
                    if(!$reviewObj->exists && $reviewObj->instructorForced)
                    {
                        $assignment->removeMatch($reviewObj->matchID);
                    }
                }
            }
        }

        $reviewMap = $assignment->getReviewMap();
        $scoreMap = $assignment->getMatchScoreMap();
        $submissions = $assignment->getAuthorSubmissionMap_();
		$studentToCovertReviewsMap = $assignment->getStudentToCovertReviewsMap();
		
        $reviewedScores = array();
		$independentSubs = array();
		
		//Autograde covert calibrations by taking covert reviews from students
		foreach($studentToCovertReviewsMap as $reviewer => $covertReviews)
		{
			foreach($covertReviews as $covertMatch)
			{
				$submissionID = $assignment->getSubmissionID(new MatchID($covertMatch));
				$keyReview = $assignment->getSingleCalibrationKeyReviewForSubmission($submissionID);
				$review = $assignment->getReview(new MatchID($covertMatch));
				//If review not done give a reviewPoints of -1
				if(sizeof($review->answers) < 1)
					$mark = new ReviewMark(0, "Review not done - Autograded", false, -1);
				else
				{
					//Just like a calibration EXCEPT review score is auto-graded to max like regular independent peer reviews
					$mark = generateAutoMark($assignment, $keyReview, $review);
					$mark->score = $assignment->maxReviewScore;
				}
				$assignment->saveReviewMark($mark, new MatchID($covertMatch));
			}
		}
		
		$studentToCovertScoresMap = $assignment->getStudentToCovertScoresMap();
		
		$output .= "<h3>High Mark Threshold: $highMarkThreshold</h3>";
		$output .= "<h3>Calibration Threshold: $calibrationThreshold</h3>";
		$output .= "<h3>High Mark Bias: $highMarkBias</h3>";
		$output .= "<h3>Calibration Bias: $calibrationBias</h3>";
		$output .= "<table><tr><td>Name</td><td>Initial Weight</td><td>Median Score</td><td>Calibration Averages</td><td>Covert Scores</td><td>Final Weight</td><tr>";
		
        $html = "";
        foreach($submissions as $authorID => $submissionID)
        {
            //TODO: This should probably output something useful...
            $authorID = new UserID($authorID);

            //We don't want to overwrite anything
            $subMark = $assignment->getSubmissionMark($submissionID);
            if($subMark->isValid && !$subMark->isAutomatic)
                continue;

            $reviews = array_filter($reviewMap[$submissionID->id], function($x) { return $x->exists; });
			$emptyReviews = array_filter($reviewMap[$submissionID->id], function($x) { return !$x->exists; });

            #Compute the mean score of this one, used for ranking to assign
            $submissionScores[$submissionID->id] =
                array_reduce(array_map( function($x) { global $scoreMap; if(isset($scoreMap[$x->matchID->id])) { return $scoreMap[$x->matchID->id]; } return 0; }, $reviews),
                    function($v,$w) {return $v+$w; });
            if(sizeof($reviews))
                $submissionScores[$submissionID->id] /= (sizeof($reviews) * $assignment->maxSubmissionScore);


            #See if this is an independent review
            if(array_reduce($reviews, function($res,$item) use (&$independents) {return $res & array_key_exists($item->reviewerID->id, $independents);}, True) &&
               sizeof($reviews) >= $minReviews )
            {
                #All Independent, take the median and assign auto grades
                $scores = array_map(function($review) use(&$scoreMap) { return $scoreMap[$review->matchID->id]; }, $reviews);
                $medScore = median($scores);

                $assignment->saveSubmissionMark(new Mark($medScore, null, true), $submissionID);

				//Package all independent submissions with their calculated weights
				$independentSub = new stdClass();
				$independentSub->submissionID = $submissionID->id;
                $independentSub->authorID = $authorID->id;
				$independentSub->weight = sizeof($reviews);
				$finalweight = sizeof($reviews);
				$output .= "<tr><td>".$dataMgr->getUserDisplayName($authorID)."</td><td>".sizeof($reviews)."</td>";
				if(1.0*$medScore/$assignment->maxSubmissionScore >= $highMarkThreshold)
				{
					$independentSub->weight *= $highMarkBias;
					$finalweight .= "*".$highMarkBias;
					$output .= "<td><span style='color:red'>".(1.0*$medScore/$assignment->maxSubmissionScore)."</span></td><td><ul>";
				}
				else
					$output .= "<td>".(1.0*$medScore/$assignment->maxSubmissionScore)."</td><td><ul>";
				foreach($reviews as $review)
				{
					if(getWeightedAverage($review->reviewerID, $assignment) < $calibrationThreshold || getWeightedAverage($review->reviewerID, $assignment) == "--")
					{
						$independentSub->weight *= $calibrationBias;
						$finalweight .= "*".$calibrationBias;
						$output .= "<li>".$dataMgr->getUserDisplayName($review->reviewerID)." - <span style='color:red'>".getWeightedAverage($review->reviewerID, $assignment)."</span></li>";
					}
					else
						$output .= "<li>".$dataMgr->getUserDisplayName($review->reviewerID)." - ".getWeightedAverage($review->reviewerID, $assignment)."</li>";
				}
				$output .= "</ul></td><td><ul>";
				foreach($reviews as $review)
				{
					$sum = array_reduce($studentToCovertScoresMap[$review->reviewerID->id], function($res, $item) use ($assignment){if($item < 0) return $res + 0; return $res + convertTo10pointScale($item, $assignment);});
					$covertaverage = $sum / sizeof($studentToCovertScoresMap[$review->reviewerID->id]);
					if($covertaverage < $calibrationThreshold)
					{
						$covertBias = (1 + ($calibrationThreshold - $covertaverage));
						$independentSub->weight *= $covertBias;
						$finalweight .= "*".$covertBias;
						$output .= "<li>".$dataMgr->getUserDisplayName($review->reviewerID)." - <span style='color:red'>".$covertaverage."</span></li>";
					}
					else
						$output .= "<li>".$dataMgr->getUserDisplayName($review->reviewerID)." - ".$covertaverage."</li>";
				}
				
				$finalweight .= " = ".$independentSub->weight;
				$output .= "</ul></td><td>$finalweight</td>";
				$independentSubs[] = $independentSub;
				
				//OLD spot checking method
                //Do we need to assign a spot check to this one?
                /*if(1.0*$medScore/$assignment->maxSubmissionScore >= $highSpotCheckThreshold || 1.0*mt_rand()/mt_getrandmax() <= $randomSpotCheckProb )
                {
                    $obj = new stdClass;
                    $obj->submissionID = $submissionID->id;
                    $obj->authorID = $authorID->id;
                    $pendingSpotChecks[] = $obj;
                }*/

                //Update the reviewer's  marks
                foreach($reviews as $review)
                {
                    $revMark = $assignment->getReviewMark($review->matchID);
                    if(!$revMark->isValid || $revMark->isAutomatic)
                        $assignment->saveReviewMark(new ReviewMark($assignment->maxReviewScore, null, true), $review->matchID);
                }
            }
            else
            {
                //We need to put this into the list of stuff to be marked by a TA
                if(array_reduce($reviewMap[$submissionID->id], function($res,$item)use(&$markers){return $item->exists && in_array($item->reviewerID->id, $markers); }))
                    continue;
                $obj = new stdClass;
                $obj->submissionID = $submissionID->id;
                $obj->authorID = $authorID->id;
                $pendingSubmissions[] = $obj;
            }
			
			
			//Autograde to 0 the reviews that have not been done after the review stop date
			global $NOW;
			if(grace($assignment->reviewStopDate) < $NOW)
			{
				foreach($emptyReviews as $emptyReview)
				{
					//If the empty review is a covert review just leave it alone because it already has a mark
					if($assignment->getReviewMark(new MatchID($emptyReview->matchID))->reviewPoints < 0)
						continue;
					$mark = new ReviewMark();
					$mark->score = 0;
					$mark->comments = "Review not done - Autograded";
					$assignment->saveReviewMark($mark, $assignment->getMatchID($submissionID, $emptyReview->reviewerID));
				}
			}
        }

		//Shuffle independent submissions and spot check proportionally with their weights;
		mt_shuffle($independentSubs);
		$pendingSpotChecks = pickSpotChecks($independentSubs, $randomSpotCheckProb);
		
        //asort($submissionScores, SORT_NUMERIC);
        if ($targetLoadSum == 0)
            return "Only marks updated, no assignments to markers"; //$html;

        $markerJobs = array();
        $markerReviewCountMaps = array();
        $assignedJobs = 0;
        foreach($markers as $markerID)
        {
            $markerJobs[$markerID] = 0;
            $markerReviewCountMaps[$markerID] = $assignment->getNumberOfTimesReviewedByUserMap(new UserID($markerID));
        }

        //We need to sort the pending submissions by their reviewer score
        $assignedJobs = 0;
        while(sizeof($pendingSubmissions))
        {
            $loadDefecits = array();
            //Who gets it?
            foreach($markers as $markerID)
            {
                $loadDefecits[$markerID] = $targetLoads[$markerID] - 1.0*$markerJobs[$markerID]/($assignedJobs+1);
            }
            $res = array_keys($loadDefecits, max($loadDefecits));
            $markerID = $res[0];

            //Figure out what submission we should assign to this person
            $submissionID = null;
            $bestScore = INF;
            $bestIndex = 0;
            foreach($pendingSubmissions as $index => $obj)
            {
                //We scale it by 0.5 to make sure that we keep the lexicographical component
                $s = $submissionScores[$obj->submissionID]*0.5;
                if(isset($markerReviewCountMaps[$markerID][$obj->authorID]))
                    $s += $markerReviewCountMaps[$markerID][$obj->authorID];
                if($s < $bestScore)
                {
                    $bestScore = $s;
                    $submissionID = $obj->submissionID;
                    $bestIndex = $index;
                }
            }
            if(is_null($submissionID))
            {
                throw new Exception("Failed to find a suitable candidate for an marker - how the hell can this happen?");
            }
            unset($pendingSubmissions[$bestIndex]);

            //Is there an marker already assigned to this paper?
            if(array_reduce($reviewMap[$submissionID], function($res,$item)use(&$markers){return in_array($item->reviewerID->id, $markers); }))
                continue;

            $assignment->createMatch(new SubmissionID($submissionID), new UserID($markerID), true);

            $markerJobs[$markerID]++;
            $assignedJobs++;
        }

        $assignedSpotChecks = array();
        foreach($markers as $markerID)
        {
            $assignedSpotChecks[$markerID] = 0;
        }
		
		//First remove old spot checks
		$assignment->removeSpotChecks();
		
        //Now do all the spot checks
        while(sizeof($pendingSpotChecks))
        {
            $loadDefecits = array();
            //Who gets it?
            foreach($markers as $markerID)
            {
                $loadDefecits[$markerID] = $targetLoads[$markerID] - 1.0*$markerJobs[$markerID]/($assignedJobs+1);
            }
            $res = array_keys($loadDefecits, max($loadDefecits));
            $markerID = $res[0];

            //Figure out what submission we should assign to this person
            $submissionID = null;
            $bestScore = INF;
            $bestIndex = 0;
            foreach($pendingSpotChecks as $index => $obj)
            {
                //We scale it by 0.5 to make sure that we keep the lexicographical component
                $s = $submissionScores[$obj->submissionID]*0.5;
                if(isset($markerReviewCountMaps[$markerID][$obj->authorID]))
                    $s += $markerReviewCountMaps[$markerID][$obj->authorID];
                if($s < $bestScore)
                {
                    $bestScore = $s;
                    $submissionID = $obj->submissionID;
                    $bestIndex = $index;
                }
            }
            if(is_null($submissionID))
            {
                throw new Exception("Failed to find a suitable candidate for an marker - how the hell can this happen?");
            }
            unset($pendingSpotChecks[$bestIndex]);

            //Is there an marker already assigned to this paper?
            if(array_reduce($reviewMap[$submissionID], function($res,$item)use(&$markers){return in_array($item->reviewerID->id, $markers); }))
                continue;

            //If there already is something that has been assigned, skip it
            try
            {
                $check = $assignment->getSpotCheck(new SubmissionID($submissionID));
                if($check->status != "pending")
                    continue;
            }catch(Exception $e){
                //We failed to find a spot check
            }

            $assignment->saveSpotCheck(new SpotCheck(new SubmissionID($submissionID), new UserID($markerID)));

            $markerJobs[$markerID]++;
            $assignedSpotChecks[$markerID]++;
            $assignedJobs++;
        }

        $html .= "<table width='100%'>\n";
        $html .= "<tr><td><h2>Marker</h2></td><td><h2>Submissions to Mark</h2></td><td><h2>SpotChecks</h2></td></tr>\n";
        foreach($dataMgr->getMarkers() as $markerID)
        {
            $html .= "<tr><td>".$userNameMap[$markerID]."</td><td>".($markerJobs[$markerID]-$assignedSpotChecks[$markerID])."</td><td>".$assignedSpotChecks[$markerID]."</td></tr>\n";
        }
        $html .= "</table>";

        return $html;
    }

}

