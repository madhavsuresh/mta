<?php
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/spotcheckutils.php");

class TestScriptPeerReviewScript extends Script
{
    function getName()
    {
        return "Test Script";
    }
    function getDescription()
    {
        return "Test Script for testing";
    }

    function getFormHTML()
    {
        //TODO: Load the defaults from the config
        global $dataMgr;
        $assignment = get_peerreview_assignment();
        $html  = "Hi";

        return $html;
    }
    function executeAndGetResult()
    {	
		$independentSubs2 = array();
		
		for($k = 1; $k <= 10; $k++)
		{
			$independentSub = new stdClass;
			$independentSub->submissionID = $k;
			$independentSub->authorID = $k;
			$independentSub->weight = $k;
			$independentSubs2[] = $independentSub;
		}
		
		mt_shuffle($independentSubs2);
		
		pickSpotChecks($independentSubs2, 0.3);
    }

}

