<?php
require_once("inc/common.php");
try
{
    //Has the course been set?
    if(!$dataMgr->courseName)
    {
        //Nope, run up the course picker for people
        $content .= "<h1>Course Select</h1>";
        foreach($dataMgr->getCourses() as $courseObj)
        {
            if($courseObj->browsable)
                $content .= "<a href='$SITEURL$courseObj->name/'>$courseObj->displayName</a><br>";
        }
        render_page();
    }
    else
    {
        $authMgr->enforceLoggedIn();

        #$dataMgr->numStudents();
        $content .= show_timezone();

        #Figure out what courses are availible, and display them to the user (showing what roles they have)
        $assignments = $dataMgr->getAssignments();
		
		if($dataMgr->isStudent($USERID))
		{
			if($scores = $dataMgr->getCalibrationScores($USERID)) {
				$currentAverage = computeWeightedAverage($scores);
			} else {
				$currentAverage = "--";
			}
			$totalReviews = $dataMgr->numCalibrationReviews($USERID);
			
			$output = array();
			
			foreach($assignments as $assignment)
			{
				
				if(!$assignment->showForUser($USERID))
                continue;
       			
				if($assignment->submissionStartDate <= $NOW AND $assignment->submissionStopDate > $NOW)
				{
					if(!($assignment->password == NULL) AND !($dataMgr->hasEnteredPassword($assignment->assignmentID, $USERID)))
					{
						$output[$assignment->submissionStopDate] = 
						"<tr><td><h4><i>$assignment->name</i></h4></td>
						<td>Password</td></td><td>Enter password:<form action='enterpassword.php?assignmentid=".$assignment->assignmentID."' method='post'><input type='text' name='password' size='10'/></td>
						<td><input type='submit' value='Enter'/></form></td>
						<td>".date('M jS Y, H:i', $assignment->submissionStopDate)."</td></tr>\n";
					}
					else 
					{
						if(!$assignment->submissionExists($USERID))
						{
							$output[$assignment->submissionStopDate] = 
							"<tr><td><h4><i>$assignment->name</i></h4></td>
							<td>".ucfirst($assignment->submissionType)."</td>
							<td></td>
							<td><form action='".get_redirect_url("peerreview/editsubmission.php?assignmentid=$assignment->assignmentID")."' method='post'><input type='submit' value='Create Submission'/></form></td>
							<td>".date('M jS Y, H:i', $assignment->submissionStopDate)."</td></tr>\n";
						}
					}	
				}

				if($assignment->reviewStartDate <= $NOW AND $assignment->reviewStopDate > $NOW)
				{
					$reviewAssignments = $assignment->getAssignedReviews($USERID);
					$id=0;
					foreach($reviewAssignments as $matchID)
					{
						if(!$assignment->reviewExists($matchID))
						{
							$output[$assignment->reviewStopDate] = 
							"<tr><td><h4><i>$assignment->name</i></h4></td>
							<td>Peer Review</td>
							<td></td>
							<td><form action='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&review=$id")."' method='post'><input type='submit' value='Go'></form></td>
							<td>".date('M jS Y, H:i', $assignment->reviewStopDate)."</td></tr>";	
						}
						$id++;			
					} 
				
                	$availableCalibrationSubmissions = $assignment->getCalibrationSubmissionIDs();
	                if($availableCalibrationSubmissions)
	                {
	                    $independents = $assignment->getIndependentUsers();
	                    //if student is supervised and has done less than the extra calibrations required
	                    if($currentAverage != "--") $currentAverage = convertTo10pointScale($currentAverage, $assignment->assignmentID) ;
	                    if(!array_key_exists($USERID->id, $independents) && (currentAverage == "--" || $currentAverage < $assignment->calibrationThresholdScore) && $assignment->getNewCalibrationSubmissionForUser($USERID) != NULL)
	                    {
	                    	$output[$assignment->reviewStopDate] = 
	                    	"<tr><td><h4><i>$assignment->name</i></h4></td>
	                    	<td>Calibration Review: <br/>".$assignment->numCalibrationReviewsDone($USERID)." of $assignment->extraCalibrations completed</td>
	                    	<td>Current Average: ".$currentAverage." <br/> Threshold: $assignment->calibrationThresholdScore</td> 
	                    	<td><form action='".get_redirect_url("peerreview/requestcalibrationreviews.php?assignmentid=$assignment->assignmentID")."' method='post'><input type='submit' value='Request Calibration Review'></a></td>
	                    	<td>".date('M jS Y, H:i', $assignment->reviewStopDate)."</td></tr>";
	                   	}
	                }
                }
			}
			ksort($output);
			
			$content .= "<h1>TODO</h1>\n";
            $content .= "<table align='left'>\n";
			foreach($output as $item)
			{
				$content .= $item;
			}
			$content .= "</table><br>";
			
		}

        if($dataMgr->isInstructor($USERID))
        {
            //Give them the option of creating an assignment, or running global scripts
            $content .= "<table align='left'><tr>\n";
            $content .= "<td><a title='Create new Assignment' href='".get_redirect_url("editassignment.php?action=new")."'><div class='icon new'></div></a</td>\n";
            $content .= "<td><a title='Run Scripts' href='".get_redirect_url("runscript.php")."'><div class='icon script'></div></a></td>\n";
            $content .= "<td><a title='User Manager' href='".get_redirect_url("usermanager.php")."'><div class='icon userManager'></div></a></td>\n";
            $content .= "</tr></table><br>\n";
        }

        $content .= "<h1>Assignments</h1>\n";
        $currentRowIndex = 0;
        foreach($assignments as $assignment)
        {
            #See if we should even display this assignment
            if(!$assignment->showForUser($USERID))
                continue;

            $rowClass = "rowType".($currentRowIndex % 2);
            $currentRowIndex++;

            #Make a div for each assignment to live in
            $content .= "<div class='box $rowClass'>\n";
            $content .= "<h3>".$assignment->name."</h3>";
            if($dataMgr->isInstructor($USERID))
            {
                #We need to give them the common options
                $content .= "<table align='left'><tr>\n";
                $content .= "<td><a title='Move Up' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=moveUp")."'><div class='icon moveUp'></div></a</td>\n";
                $content .= "<td><a title='Move Down' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=moveDown")."'><div class='icon moveDown'></div></a></td>\n";
                $content .= "<td><a title='Delete' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=delete")."'><div class='icon delete'></div></a></td>\n";
                $content .= "<td><a title='Edit Main Settings' href='".get_redirect_url("editassignment.php?action=edit&assignmentid=$assignment->assignmentID")."'><div class='icon edit'></div></a></td>\n";
                $content .= "<td><a title='Run Scripts' href='".get_redirect_url("runscript.php?assignmentid=$assignment->assignmentID")."'><div class='icon script'></div></a></td>\n";
                $content .= "<td><a title='Duplicate Assignment' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=duplicate")."'><div class='icon duplicate'></div></a></td>\n";
                $content .= "</table><br/>\n";
            }
            $content .= $assignment->getHeaderHTML($USERID);
            $content .= "</div>";
        }

        render_page();
    }
}catch(Exception $e) {
    render_exception_page($e);
}

?>

