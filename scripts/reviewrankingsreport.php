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
        return "(None)";
    }
    function hasParams()
    {
        return false;
    }
    function executeAndGetResult()
    {
    	global $dataMgr;
		
		$assignments = $dataMgr->getAssignments();
		
		$latestCalibrationAssignment = latestCalibrationAssignment();
		$userDisplayMap = $dataMgr->getUserDisplayMap();
		
		$students = array();
		foreach($dataMgr->getActiveStudents() as $user)
        {
            $student = new stdClass();
			$student->name = $name;
			$student->calibrationScore = (getWeightedAverage(new UserID($user), $latestCalibrationAssignment) == "--") ? 0 : (getWeightedAverage(new UserID($user), latestCalibrationAssignment()));
			$student->reviewScore = precisionFloat(compute_peer_review_score_for_assignments(new UserID($user), $assignments))*100;
			$student->orderable = max($student->calibrationScore*10, $student->reviewScore);
			$student->other = min($student->reviewScore, $student->calibrationScore*10);
			insert_sort_tiebreak($student, $students);
        }
        
        $html = "";
        $html .= "<h1>Student Calibration Averages and Review Scores</h1>";
		$html .= "<table style='margin-bottom:10px;'><tr><td>Calibration Weighted Average</td><td><div style='opacity: 0.5; height:20px; width:20px; background-color: #0080FF;'></div></td></tr>";
		$html .= "<tr><td>Review Rolling Average</td><td><div style='opacity: 0.5; height:20px; width:20px; background-color: #04B404;;'></div></td></tr></table>";
		$html .= "<div id='thresholdlabel'>Calibration Threshold: <br>$latestCalibrationAssignment->calibrationThresholdScore</div>";
        $html .= "<div width=100%><table id='bargraph' width=100%>";
		$i = 1;
		foreach($students as $student)
		{
				$html .= "<tr><td width='5%'>$i</td> 
				<td width='95%'>
				<div style='opacity: 0.5; text-align: right; height: 20px; width: ".($student->calibrationScore*10)."%; background-color: #0080FF;'></div>
				<div style='opacity: 0.5; text-align: right; margin-top: -20px; height: 20px; width: ".$student->reviewScore."%; background-color: #04B404;'></div>
				</td></tr>";
				$i = $i + 1;
		}
		$html .= "</table>";
		$html .= "<div id='threshold' style='border-left: 2px solid #000000;'>&nbsp</div>";
		$html .= "</div>";
				$html .= "<script type='text/javascript'>
					var height = $('#bargraph').height();
					$('#threshold').height(height);
					var calibThreshold = ".$latestCalibrationAssignment->calibrationThresholdScore.";
					var x_pos = 5 + 95 * (calibThreshold/10);
					$('#threshold').css('margin-top', - height);
					$('#threshold').css('margin-left', x_pos+'%');
					$('#thresholdlabel').css('margin-left', x_pos+'%');
				 </script>";
		

		return $html;
    }
}

function insert_sort_tiebreak($object, &$array)
{
	$length = sizeof($array);
	if($length == 0)
	{
		$array[0] = $object;
		return;
	}
	for($i = 0; $i < $length; $i++)
	{
		if($object->orderable == $array[$i]->orderable)
		{
			if($object->other > $array[$i]->other)
			{
				for($j = $length; $j > $i; $j--)
				{
					$array[$j] = $array[$j-1];
				}
				$array[$i] = $object;
				return;
			}	
		}
		elseif($object->orderable > $array[$i]->orderable)
		{
			for($j = $length; $j > $i; $j--)
			{
				$array[$j] = $array[$j-1];
			}
			$array[$i] = $object;
			return;
		}
	}
	$array[$length] = $object;
}

?>