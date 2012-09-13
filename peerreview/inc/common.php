<?php

global $PEER_REVIEW_QUESTION_TYPES;
$PEER_REVIEW_QUESTION_TYPES = array(
    "TextAreaQuestion" => 'Text Area Question',
    "RadioButtonQuestion" => 'Radio Button Question'
);
global $PEER_REVIEW_SUBMISSION_TYPES;
$PEER_REVIEW_SUBMISSION_TYPES = array(
    "essay" => "Essay",
    "articleresponse" => "Article Response",
);

//Get stuff from the main site
require_once(dirname(__FILE__)."/../../inc/common.php");

//Handy helper functions
function get_peerreview_assignment()
{
    global $_GET, $dataMgr;
    #Make sure they specified the assignment
    $assignmentID = new AssignmentID(require_from_get("assignmentid"));

    return $dataMgr->getAssignment($assignmentID, "peerreview");
}

class SubmissionID extends MechanicalTA_ID
{
};

class QuestionID extends MechanicalTA_ID
{
};

class MatchID extends MechanicalTA_ID
{
}

?>
