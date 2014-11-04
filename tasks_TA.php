<?php
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
				elseif(!$studentReview->answers && grace($assignment->reviewStopDate) < $NOW)
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
			$color = ($NOW >= $reviewTask->endDate) ? "red" : "";
			$reviewTask->html = 
			"<table width='100%'><tr><td class='column1'><h4>$assignment->name</h4></td>
			<td class='column2'>Review</td>
			<td class='column3'><table><td>Your Review: $markerReviewStatus<br>$numMarkedStudentReviews of $numStudentReviews reviews are marked </td><td><a target='_blank' title='Edit' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&submissionid=$reviewObj->submissionID&matchid=$reviewObj->matchID&close=1&showall=1")."'><button>Grade</button></a></td></table></td>
			<td class='column4'><span style='color:$color'>".phpDate($assignment->markPostDate)."</span></td></tr></table>\n";
			insertTask($reviewTask, $reviewTasks);
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
			$color = ($NOW >= $reviewTask->endDate) ? "red" : "";
			$reviewTask->html = 
			"<table width='100%'><tr><td class='column1'><h4>$assignment->name</h4></td>
			<td class='column2'>Spot Check</td>
            <td><a target='_blank' href='peerreview/viewer.php?assignmentid=$assignment->assignmentID&$args&type$i=spotcheck&submissionid$i=$spotCheck->submissionID'><button>Confirm</button><br></a></td>
            <td class='column4'><span style='color:$color'>".phpDate($assignment->markPostDate)."</span></td></tr></table>\n";
			insertTask($reviewTask, $reviewTasks);
        }
	}

	$unansweredAppeals = $assignment->getUnansweredAppealsForMarker($USERID);

	foreach($unansweredAppeals as $appealObj)
	{
		if($appealObj->appealType == "review")
		{
			$task = "Review Appeal";
			$appealtype = "review";
		}
		elseif($appealObj->appealType == "reviewMark")
		{
			$task = "Review Mark Appeal";
			$appealtype = "reviewmark";
		}
		$reviewTask = new stdClass();
		$reviewTask->endDate = $assignment->appealStopDate;
		$color = ($NOW >= $reviewTask->endDate) ? "red" : "";
		$reviewTask->html = 
		"<table width='100%'></tr><td class='column1'><h4>$assignment->name</h4></td>
		<td class='column2'>$task</td>
		<td class='column3'><a target='_blank' href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&close=1&matchid=$appealObj->matchID&appealtype=$appealtype")."'><button>Answer</button></a></td>
		<td class='column4'><span style='color:$color'>".phpDate($assignment->appealStopDate)."</span></td></tr></table>\n";
		insertTask($reviewTask, $reviewTasks);
	}
}

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