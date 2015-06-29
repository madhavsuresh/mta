<?php
require_once("peerreview/inc/common.php");

class AssignCalibrationsPeerReviewScript extends Script
{
    function getName()
    {
        return "Assign Calibration Reviews";
    }
    function getDescription()
    {
        return "Inserts the requested number of calibration essays for non independent users";
    }
    function getFormHTML()
    {
        $html = "";
        $html .= "<table width='100%'>\n";
        $html .= "<tr><td>Number of reviews to assign</td><td>";
        $html .= "<input type='text' name='numCalibrations' id='numCalibrations' value='2' size='30'/></td></tr>";
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;

        $assignment = get_peerreview_assignment();

        $numCalibrations = require_from_post("numCalibrations");

        $students = $dataMgr->getActiveStudents();
        $independents = $assignment->getIndependentUsers();
        $userNameMap = $dataMgr->getUserDisplayMap();

		if($assignment->submissionSettings->autoAssignEssayTopic == true && sizeof($assignment->submissionSettings->topics) > 1)
			$isAutoAssign = true;
			
        foreach($students as $student)
        {     	
            if(array_key_exists($student->id, $independents)){
                $html .= $userNameMap[$student->id] . " is independent.<br>";
                continue;
            }
			
			if($isAutoAssign)
			{
        		$topicIndex = topicHash($student, $assignments->submissionSettings->topics);
        		
				$reviewAssignments = $assignment->getAssignedCalibrationReviews($student);
	            $i = 0;
	            for($i = sizeof($reviewAssignments); $i < $numCalibrations; $i++)
	            {
	                $submissionID = $assignment->getNewCalibrationSubmissionForUserRestricted($student, $topicIndex);
	
	                if(is_null($submissionID))
	                {
	                    $html .= "<span class='error'>" . $userNameMap[$student->id] . " ran out of calibrations to do!</span><br>";
	                    break;
	                }
	                $assignment->assignCalibrationReview($submissionID, $student, true); 
	            }
			}
			else
			{
	            $reviewAssignments = $assignment->getAssignedCalibrationReviews($student);
	            $i = 0;
	            for($i = sizeof($reviewAssignments); $i < $numCalibrations; $i++)
	            {
	                $submissionID = $assignment->getNewCalibrationSubmissionForUser($student);
	
	                if(is_null($submissionID))
	                {
	                    $html .= "<span class='error'>" . $userNameMap[$student->id] . " ran out of calibrations to do!</span><br>";
	                    break;
	                }
	                $assignment->assignCalibrationReview($submissionID, $student, true); 
	            }
			}
			
            $html .= $userNameMap[$student->id] . " has $i calibrations.<br>";

        }
        return $html;
    }
}
