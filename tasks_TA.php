<?php
require_once("inc/common.php");

global $assignments;
global $dataMgr;
global $USERID;
global $content;

//$content .= "<h1>HELLO THERE TA</h1>";

$reviewTasks = array();

foreach($assignments as $assignment)
{
	$reviews = $assignment->getReviewsForMarker($USERID);
	$spotChecks = $assignment->getSpotChecksForMarker($USERID);
	$reviewMap = $assignment->getReviewMap();
	
	if($assignment->assignmentID->id == 104)
	{
		$spotCheckMap = $assignment->getSpotCheckMap();
		$appealMap = $assignment->getReviewAppealMapBySubmission();
    	$markAppealMap = $assignment->getReviewMarkAppealMapBySubmission();
		$markerToSubmissionsMap = $assignment->getMarkerToSubmissionsMap();
		//print_r($appealMap);
		//print_r($markAppealMap);
		
		$markers = $dataMgr->getMarkers();
		
		$markingLoadMap = array();
		$sumLoad = 0;
		foreach($markers as $markerID)
		{
			$markerLoad = $dataMgr->getMarkingLoad(new UserID($markerID));
			$markingLoadMap[$markerID] = $markerLoad;
			$sumLoad += $markerLoad;
		}
		
		$targetLoads = array();
		foreach($markers as $markerID)
			$targetLoads[$markerID] = precisionFloat($markingLoadMap[$markerID]/$sumLoad);
		
		$markerSubs = array();
		foreach($markers as $markerID)
			$markerSubs[$markerID] = 0;
		
		$assignedJobs = 0;
		$loadDefecits = array();
		$markerTasks = array();
		foreach($markers as $markerID)
			$markerTasks[$markerID] = array();
		foreach($markAppealMap as $submissionID => $reviewAppeals)
		{
			foreach($markers as $markerID)
        		$loadDefecits[$markerID] = $targetLoads[$markerID] - 1.0*$markerSubs[$markerID]/($assignedJobs+1);
			while(1)
			{
				if(sizeof($loadDefecits)==0)
				{
					throw new Exception('Somehow this submission has been reviewed by all markers');
				}
				$res = array_keys($loadDefecits, max($loadDefecits));
           		$markerID = $res[0];
				if(array_key_exists($submissionID, $markerToSubmissionsMap[$markerID]))
				{
					unset($loadDefecits[$markerID]);
					continue;
				}
				//Assign all review appeals (most likely only one but hey ... it doesn't hurt to be robust)
				$markerTasks[$markerID][$submissionID] = array();
				foreach($reviewAppeals as $matchID => $needsResponse)
				{
					$markerTasks[$markerID][$submissionID][$matchID] = $needsResponse;
				}
				$markerSubs[$markerID]++;
				$assignedJobs++;
				break;
			}
		}
		foreach($appealMap as $submissionID => $submissionAppeals)
		{
			foreach($markers as $markerID)
        		$loadDefecits[$markerID] = $targetLoads[$markerID] - 1.0*$markerSubs[$markerID]/($assignedJobs+1);
			while(1)
			{
				if(sizeof($loadDefecits)==0)
				{
					throw new Exception('Somehow this submission has been reviewed by all markers');
				}
				$res = array_keys($loadDefecits, max($loadDefecits));
           		$markerID = $res[0];
				if(array_key_exists($submissionID, $markerToSubmissionsMap[$markerID]))
				{
					unset($loadDefecits[$markerID]);
					continue;
				}
				if(!array_key_exists($submissionID, $markerTasks[$markerID]))
				{
					$markerTasks[$markerID][$submissionID] = array();
					$markerSubs[$markerID]++;
					$assignedJobs++;
				}
				foreach($submissionAppeals as $matchID => $needsResponse)
				{
					$markerTasks[$markerID][$submissionID][$matchID] = $needsResponse;
				}
				break;
			}
		}
		print_r($markerTasks);
	}

	$color = '';
	if($NOW >= $assignment->markPostDate)
		$color = 'red';
	
	//For each of the marker's assigned reviews
	foreach($reviews as $reviewObj)
	{
		//get all student reviews associated with this marker assigned review 
		$studentReviews = $assignment->getStudentReviewsForSubmission($reviewObj->submissionID);
		
		$allReviewsMarked = true;
		$numStudentReviews = 0;
		$numMarkedStudentReviews = 0;
		foreach($studentReviews as $studentReview)
		{
			//The only way as of now to check if review is instructorForced is through reviewMap
			if(!($reviewMap[$reviewObj->submissionID->id][$studentReview->reviewerID->id]->instructorForced))
			{
				if($assignment->getReviewMark($studentReview->matchID)->isValid)
					$numMarkedStudentReviews++;
				elseif(!$studentReview->answers && $NOW > $assignment->reviewStopDate)
				{
					//Trigger auto mark for reviews not done after review stop date
					$mark = new ReviewMark();
	    			$mark->score = 0;
					$mark->comments = "Review not done - Autograded";
	    			$assignment->saveReviewMark($mark, $assignment->getMatchID($reviewObj->submissionID , $studentReview->reviewerID));
					$numMarkedStudentReviews++;
				}
				$numStudentReviews++;
			}
		}
		$allReviewsMarked = ($numMarkedStudentReviews == $numStudentReviews);
		
		if(!$reviewObj->exists || !$allReviewsMarked)
		{
			$markerReviewStatus = "Not Done";
			if($reviewObj->exists)
				$markerReviewStatus = "Done";
			if($assignment->reviewDraftExists($reviewObj->matchID))
				$markerReviewStatus = "Draft only";
				
			$reviewTask = new stdClass();
			$reviewTask->endDate = $assignment->markPostDate;
			$reviewTask->html = 
			"<table width='100%'><tr><td class='column1'><h4>$assignment->name</h4></td>
			<td class='column2'>Review</td>
			<td class='column3'><table><td>Your Review: $markerReviewStatus<br>$numMarkedStudentReviews of $numStudentReviews reviews are marked </td><td><a target='_blank' title='Edit' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&submissionid=$reviewObj->submissionID&matchid=$reviewObj->matchID&close=1&showall=1")."'><button>Grade</button></a></td></table></td>
			<td class='column4'><span style='color:$color'>".phpDate($assignment->markPostDate)."</span></td></tr></table>\n";
			insert($reviewTask, $reviewTasks);
		}
		
	}
	
	foreach($spotChecks as $spotCheck)
	{
		if($spotCheck->getStatusString() == 'Pending')
		{
            $args = "type0=submission&submissionid0=$spotCheck->submissionID";
            $i=1;
            if(array_key_exists($spotCheck->submissionID->id, $reviewMap))
            {
                foreach($reviewMap[$spotCheck->submissionID->id] as $reviewObj)
                {
                    if($reviewObj->exists)
                    {
                        $args .= "&type$i=review&&matchid$i=$reviewObj->matchID";
                        $i++;
                    }
                }
            }
			
            $reviewTask = new stdClass();
			$reviewTask->endDate = $assignment->markPostDate;
			$reviewTask->html = 
			"<table width='100%'><tr><td class='column1'><h4>$assignment->name</h4></td>
			<td class='column2'>Spot Check</td>
            <td><a  target='_blank' href='peerreview/viewer.php?assignmentid=$assignment->assignmentID&$args&type$i=spotcheck&submissionid$i=$spotCheck->submissionID'><button>Confirm</button><br></a></td>
            <td class='column4'><span style='color:$color'>".phpDate($assignment->markPostDate)."</span></td></tr></table>\n";
			insert($reviewTask, $reviewTasks);
        }
	}
}

$content .= print_r($spotChecks, true);
$content .= "<div style='margin-bottom:20px'>";
$content .= "<h1>Tasks</h1>\n";
if($reviewTasks)
{
	$bg = '';
	foreach($reviewTasks as $reviewTask)
	{
		$bg = ($bg == '#E0E0E0' ? '' : '#E0E0E0');
		$content .= "<div class='TODO' style='background-color:$bg;'>";
		$content .= $reviewTask->html;
		$content .= "</div>";
	}
}
else
	$content .= "You currently have no assigned tasks";
$content .= "</div>";
?>