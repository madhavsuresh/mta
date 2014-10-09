<?php

class TickTockScript extends Script
{
	
	function getName()
    {
        return "Tick Tock";
    }
    function getDescription()
    {
        return "Activate tick tock script for testing";
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
		require_once(MTA_ROOTPATH.'ticktock.php');
    }
}

?>