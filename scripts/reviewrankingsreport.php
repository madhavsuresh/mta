<?php
require_once("peerreview/inc/calibrationutils.php");

class ReviewRankingsReportScript extends Script
{
	
	function getName()
    {
        return "Review Rankings Report";
    }
    function getDescription()
    {
        return "Show student rankings in weighted average calibration and rolling average review score";
    }
    function getFormHTML()
    {
    	global $dataMgr;
		
		$assignments = $dataMgr->getAssignments();
		
		$latestCalibrationAssignment = latestCalibrationAssignment();
		
		$students = array();
		foreach($dataMgr->getUserDisplayMap() as $user => $name)
        {
            if(!$dataMgr->isStudent(new UserID($user))){
                continue;
            }
            $student = new stdClass();
			$student->name = $name;
			$student->calibrationScore = (getWeightedAverage(new UserID($user), $latestCalibrationAssignment) == "--") ? 0 : (getWeightedAverage(new UserID($user), latestCalibrationAssignment()));
			$student->reviewScore = precisionFloat(compute_peer_review_score_for_assignments(new UserID($user), $assignments))*100;
			$student->orderable = max($student->calibrationScore*10, $student->reviewScore);
			$student->maxScore = ($student->calibrationScore*10 > $student->reviewScore) ? 1 : 0; 
			insertr($student, $students);
        }
        
        $html = "";
        $html .= "<h1>Student Calibration Averages and Review Scores</h1>";
        $html .= "<div width=100%><table id='bargraph' width=100%>";
		foreach($students as $student)
		{
			if($student->maxScore == 1)
				$html .= "<tr><td width='20%'>$student->name</td><td width='80%'>
				<div style='opacity: 0.5; text-align: right; height: 20px; width: ".($student->calibrationScore*10)."%; background-color: #0080FF;'>$student->calibrationScore</div>
				<div style='opacity: 0.5; text-align: right; margin-top: -20px; height: 20px; width: ".$student->reviewScore."%; background-color: #04B404;'>".$student->reviewScore."%</div>
				</td></tr>";
			else
				$html .= "<tr><td width='20%'>$student->name</td><td width='80%'>
				<div style='opacity: 0.5; text-align: right; height: 20px; width: ".($student->calibrationScore*10)."%; background-color: #0080FF;'>$student->calibrationScore</div>
				<div style='opacity: 0.5; text-align: right; margin-top: -20px; height: 20px; width: ".$student->reviewScore."%; background-color: #04B404;'>".$student->reviewScore."%</div>
				</td></tr>";
		}
		$html .= "</table>";
		$html .= "<div id='threshold' style='border-left: 2px solid #000000;'>&nbsp</div>";
		$html .= "<script type='text/javascript'>
					var height = $('#bargraph').height();
					$('#threshold').height(height);
					var calibThreshold = ".$latestCalibrationAssignment->calibrationThresholdScore.";
					var blah = 20 + 80 * (calibThreshold/10)
					$('#threshold').css('margin-top', - height);
					$('#threshold').css('margin-left', blah+'%');
				 </script>";
		$html .= "</div>";
		

		return $html;
    }
    function hasParams()
    {
        return true;
    }
	
	function executeAndGetResult()
	{
		return "";
	}
	

}

?>