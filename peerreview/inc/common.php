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
    "image" => "Image",
    "code" => "Code"
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

function compute_peer_review_score_for_assignments(UserID $student, $assignments)
{
    $scores = array();
    foreach($assignments as $assignment)
    {
        foreach($assignment->getAssignedReviews($student) as $matchID)
        {
            $mark = $assignment->getReviewMark($matchID);
            if($mark->isValid)
                $scores[] = $mark->getScore() * 1.0 / $assignment->maxReviewScore;
        }
    }
    if(sizeof($scores))
        return array_reduce($scores, function($a, $b) { return $a+$b; }) / sizeof($scores);
    else
        return 0;
}

function count_valid_peer_review_marks_for_assignments(UserID $student, $assignments)
{
  $scores = 0;
    foreach($assignments as $assignment)
    {
        foreach($assignment->getAssignedReviews($student) as $matchID)
        {
            $mark = $assignment->getReviewMark($matchID);
            if($mark->isValid)
              $scores = $scores + 1;
        }
    }
    return $scores;
}

