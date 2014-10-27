<?php
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/calibrationutils.php");
require_once("peerreview/inc/spotcheckutils.php");

class AutogradeAndAssignMarkersCronJob
{
    function executeAndGetResult(AssignmentID $assignmentID, PDODataManager $globalDataMgr)
    {
    	try{
	    	//First check if the job has already been done
			if($globalDataMgr->isJobDone($assignmentID, 'autogradeandassign'))
				return;
			
			$configuration = $globalDataMgr->getCourseConfiguration($assignmentID);
			
			$assignment = $globalDataMgr->getAssignment($assignmentID);
			
			$minReviews = $configuration->minReviews;//$minReviews = intval(require_from_post("minReviews"));
			$highMarkThreshold = $configuration->highMarkThreshold * 0.01;//$highMarkThreshold = floatval(require_from_post("highMarkThreshold"))*0.01;
			mt_srand($assignment->submissionStartDate);//hard-coded in
			$randomSpotCheckProb = $configuration->spotCheckProb;//$randomSpotCheckProb = floatval(require_from_post("spotCheckProb"));
		
			$userNameMap = $globalDataMgr->getUserDisplayMapByAssignment($assignmentID);
			$independents = $assignment->getIndependentUsers();
			$highMarkBias = $configuration->highMarkBias;//$highMarkBias = floatval(require_from_post("highMarkBias"));
			$calibrationThreshold = $configuration->calibrationThreshold;//$calibrationThreshold = floatval(require_from_post("calibrationThreshold"));
			$calibrationBias = $configuration->calibrationBias;//$calibrationBias = floatval(require_from_post("calibrationBias"));
			
			$markers = $globalDataMgr->getMarkersByAssignment($assignmentID);
			mt_shuffle($markers);
			
			$targetLoads = array();
			$targetLoadSum = 0;
			foreach($markers as $markerID)
			{
			    //TODO: Grab from post
			    $targetLoads[$markerID] = $globalDataMgr->getMarkingLoad(new UserID($markerID));
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
			$autogradedSubmissions = 0; $autogradedReviews = 0;
			//Autograde covert calibrations by taking covert reviews from students
			foreach($studentToCovertReviewsMap as $reviewer => $covertReviews)
			{
				$autogradedReviews += sizeof($covertReviews);
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
			
			$output .= "<h3>High Mark Threshold: $highSpotCheckThreshold</h3>";
			$output .= "<h3>Calibration Threshold: $calibrationThreshold</h3>";
			$output .= "<h3>High Mark Bias: $highMarkBias</h3>";
			$output .= "<h3>Calibration Bias: $calibrationBias</h3>";
			$output .= "<table><tr><td>Name</td><td>Initial Weight</td><td>Median Score</td><td>Calibration Averages</td><td>Covert Scores</td><td>Final Weight</td><tr>";
			
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
					$autogradedSubmissions++;
			
					//Package all independent submissions with their calculated weights
					$independentSub = new stdClass();
					$independentSub->submissionID = $submissionID->id;
			        $independentSub->authorID = $authorID->id;
					$independentSub->weight = sizeof($reviews);
					$finalweight = sizeof($reviews);
					$output .= "<tr><td>".$globalDataMgr->getUserDisplayName($authorID)."</td><td>".sizeof($reviews)."</td>";
					if(1.0*$medScore/$assignment->maxSubmissionScore >= $highSpotCheckThreshold)
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
							$output .= "<li>".$globalDataMgr->getUserDisplayName($review->reviewerID)." - <span style='color:red'>".getWeightedAverage($review->reviewerID, $assignment)."</span></li>";
						}
						else
							$output .= "<li>".$globalDataMgr->getUserDisplayName($review->reviewerID)." - ".getWeightedAverage($review->reviewerID, $assignment)."</li>";
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
							$output .= "<li>".$globalDataMgr->getUserDisplayName($review->reviewerID)." - <span style='color:red'>".$covertaverage."</span></li>";
						}
						else
							$output .= "<li>".$globalDataMgr->getUserDisplayName($review->reviewerID)." - ".$covertaverage."</li>";
					}
					
					$finalweight .= " = ".$independentSub->weight;
					$output .= "</ul></td><td>$finalweight</td>";
					$independentSubs[] = $independentSub;
					
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
					$autogradedReviews += sizeof($emptyReviews);
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
			
			$submissionsAssigned = sizeof($pendingSubmissions);
			$spotChecksAssigned = sizeof($pendingSpotChecks);
			
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
	
			$html = "";	
			$html .= "<table width='100%'>\n";
			$html .= "<tr><td><h2>Marker</h2></td><td><h2>Submissions to Mark</h2></td><td><h2>SpotChecks</h2></td></tr>\n";
			foreach($globalDataMgr->getMarkersByAssignment($assignmentID) as $markerID)
			{
			    $html .= "<tr><td>".$userNameMap[$markerID]."</td><td>".($markerJobs[$markerID]-$assignedSpotChecks[$markerID])."</td><td>".$assignedSpotChecks[$markerID]."</td></tr>\n";
			}
			$html .= "</table>";
			
			$spotChecksAssigned = sizeof($pendingSpotChecks);
			
			$summary = "Autograded submissions: $autogradedSubmissions Autograded reviews: $autogradedReviews<br> Submissions assigned: $submissionsAssigned Spotchecks assigned: $spotChecksAssigned";
			
			$globalDataMgr->createNotification($assignmentID, 'autogradeandassign', 1, $summary, $html);
		}catch(Exception $exception){
			$globalDataMgr->createNotification($assignmentID, 'autogradeandassign', 0, cleanString($exception->getMessage()), "");
		}
	}
}

?>
