<?php
//Get stuff from the main site
require_once(dirname(__FILE__)."/../../inc/common.php");

//Handy helper functions
function get_peerreviewleaderboard_assignment()
{
    global $_GET, $dataMgr;
    #Make sure they specified the assignment
    $assignmentID = new AssignmentID(require_from_get("assignmentid"));

    return $dataMgr->getAssignment($assignmentID, "peerreviewleaderboard");
}

