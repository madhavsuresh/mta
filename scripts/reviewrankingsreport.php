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
		
		$students = array();
		foreach($dataMgr->getUserDisplayMap() as $user => $name)
        {
            if(!$dataMgr->isStudent(new UserID($user))){
                continue;
            }
            $student = new stdClass();
			$student->name = $name;
			$student->calibrationScore = getWeightedAverage(new UserID($user));
			$student->reviewScore = precisionFloat(compute_peer_review_score_for_assignments(new UserID($user), $assignments));
			$student->orderable = max($student->calibrationScore, $student->reviewScore);
			insertr($student, $students);
        }
        
        $html = "";
        $html .= "<h1>Student Calibration Averages and Review Scores</h1>";
        $html .= "<table width=100%>";
		foreach($students as $student)
		{
			if($student->calibrationScore == "--")
				$calibrationScore = 0;
			else 
				$calibrationScore = $student->calibrationScore*10;
			$html .= "<tr><td width='10%'>$student->name</td><td width='90%'>
			<div style='opacity: 0.5; height: 15px; width: ".(int) $calibrationScore."%; background-color: #CCC;'>$calibrationScore</div>
			<div style='opacity: 0.5; position:relative; left:0px; height: 15px; width: ".($student->reviewScore*10)."%;'>$student->reviewScore</div>
			</td></tr>";
		}
		$html .= "</table>";
		
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