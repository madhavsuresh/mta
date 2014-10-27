<?php
require_once("peerreview/inc/calibrationutils.php");

class TickTockScript extends Script
{
	
	function getName()
    {
        return "TickTock";
    }
    function getDescription()
    {
        return "TickTock Script to test Cron TickTock page";
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
		require_once(MTA_ROOTPATH."ticktock.php");
	}
}
?>