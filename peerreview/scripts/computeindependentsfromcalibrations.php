<?php
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/calibrationutils.php");

class ComputeIndependentsFromCalibrationsPeerReviewScript extends Script
{
    function getName()
    {
        return "Compute Independents From Calibrations";
    }
    function getDescription()
    {
        return "Determines which users should be in the independent pool for this assignment as determined by the weighted average score";
    }

    function getFormHTML()
    {
        //TODO: Load the defaults from the config
        $html  = "<table width='100%'>\n";
		/*$html .= "<tr><td width='300'>Minimum number of calibration reviews for qualification</td><td>";
        $html .= "<input type='text' name='minimumReviews' id='minimumReviews' value='3' size='10'/></td></tr>\n";
		$html .= "<tr><td>Review Score Threshold</td><td>";
        $html .= "<input type='text' name='threshold' id='threshold' value='1.80' size='10'/></td></tr>";*/
        $html .= "<tr><td>Keep Already Independent</td><td>";
        $html .= "<input type='checkbox' name='keep' value='keep' checked/></td></tr>";
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        //Get all the assignments

        $currentAssignment = get_peerreview_assignment();

        //$minimumCalibrationReviews = require_from_post("minimumReviews");
        //$independentThreshold = require_from_post("threshold");
        $userNameMap = $dataMgr->getUserDisplayMap();
        $students = $dataMgr->getActiveStudents();
        if(array_key_exists("keep", $_POST)){
            $independents = $currentAssignment->getIndependentUsers();
        }else{
            $independents = array();
        }
        
        /*
        $html = "<h2>Used Assignments</h2>";
        foreach($assignments as $asn){
            $html .= $asn->name . "<br>";
        }*/

        $html .= "<table width='100%'>\n";
        $html .= "<tr><td><h2>Student</h2></td><td><h2>Weighted Average Score</h2></td><td><h2>Effective Calibration Reviews Done</h2></td><td><h2>Status</h2></td></tr>\n";
        $currentRowType = 0;

		$count = 1;
        foreach($students as $student)
        {
            $html .= "<tr class='rowType$currentRowType'><td>".$userNameMap[$student->id]."</td>";
			$weightedAverage = getWeightedAverage($student, $currentAssignment);
            $html .= "<td>$weightedAverage</td>";
			$numReviews = $dataMgr->numCalibrationReviews($student);
			$html .= "<td>".$numReviews."</td>";
            $html .= "</td><td>\n";
            if($weightedAverage >= $currentAssignment->calibrationThresholdScore && $numReviews >= $currentAssignment->calibrationMinCount && !array_key_exists($student->id, $independents))
            {
                $independents[] = $student;
                $html .= "Independent";
            }
            $html .= "</td></tr>\n";
            $currentRowType = ($currentRowType+1)%2;
            $count++;
        }
        $html .= "</table>\n";

        $currentAssignment->saveIndependentUsers($independents);
        return $html;
    }
}

