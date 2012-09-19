<?php
require_once("inc/common.php");
try
{
    $title .= " | Add Rebuttal Comment";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    #Get this assignment's data
    $assignment = get_peerreview_assignment();

    if(array_key_exists("close", $_GET))
        $closeOnDone = "&close=1";
    else
        $closeOnDone = "";


