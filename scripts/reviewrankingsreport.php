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
		
		$html = "";
		
		foreach($dataMgr->getUserDisplayMap() as $user => $name)
        {
            if(!$dataMgr->isStudent(new UserID($user))){
                continue;
            }
            $student = new stdClass();
			//$student = $dataMgr->get
        }
    }
    function hasParams()
    {
        return false;
    }

}

?>