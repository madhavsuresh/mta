<?php

class CopyAssignments extends Script
{
	function getName()
    {
        return "Copy Assignments";
    }
    function getDescription()
    {
        return "Copy assignments from a previous offering.";
    }
    function getFormHTML()
    {
        return "(None)";
    }
    function hasParams()
    {
        return false;
    }
} 

?>