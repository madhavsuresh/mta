<?php
require_once("peerreview/inc/common.php");

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
        $html .= "<input type='text' name='minReviews' value='3' size='10'/></td></tr>\n";
        $html .= "<tr><td>Auto Spot Check Grade</td><td>";
        $html .= "<input type='text' name='spotCheckThreshold' value='80' size='10'/>%</td></tr>\n";
        $html .= "<tr><td>Auto Spot Check Probability</td><td>";
        $html .= "<input type='text' name='spotCheckProb' value='0.25' size='10'/></td></tr>\n";
        $html .= "<tr><td>Seed</td><td>";
        $html .= "<input type='text' name='seed' value='$assignment->submissionStartDate' size='30'/></td></tr>\n";
        $html .= "<tr><td>&nbsp</td></tr>\n";

        foreach($dataMgr->getMarkers() as $markerID)
        {
            $html .= "<tr><td>".$dataMgr->getUserDisplayName(new UserID($markerID))."'s Load</td><td>";
            $html .= "<input type='text' name='load$markerID' value='0' size='30'/></td></tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        $assignment = get_peerreview_assignment();

        $minReviews = intval(require_from_post("minReviews"));
        $highSpotCheckThreshold = floatval(require_from_post("spotCheckThreshold"))*0.01;
        mt_srand(require_from_post("seed"));
        $randomSpotCheckProb = floatval(require_from_post("spotCheckProb"));
        $userNameMap = $dataMgr->getUserDisplayMap();
        $independents = $assignment->getIndependentUsers();

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
        $submissions =  $assignment->getAuthorSubmissionMap();

        $reviewedScores = array();

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

                //Do we need to assign a spot check to this one?
                if(1.0*$medScore/$assignment->maxSubmissionScore >= $highSpotCheckThreshold || 1.0*mt_rand()/mt_getrandmax() <= $randomSpotCheckProb )
                {
                    $obj = new stdClass;
                    $obj->submissionID = $submissionID->id;
                    $obj->authorID = $authorID->id;
                    $pendingSpotChecks[] = $obj;
                }

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
        }
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

