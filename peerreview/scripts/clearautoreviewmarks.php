<?php
require_once("peerreview/inc/common.php");

class ClearAutoReviewMarksPeerReviewScript extends Script
{
    function getName()
    {
        return "Clear Auto Review Marks";
    }
    function getDescription()
    {
        return "Deletes all the marks that are automatically assigned to reviews";
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
        global $dataMgr;
        $assignment = get_peerreview_assignment();
        $authors = $assignment->getAuthorSubmissionMap();

        foreach($authors as $author => $submissionID)
        {
            foreach($assignment->getMatchesForSubmission($submissionID) as $matchID){
                $mark = $assignment->getReviewMark($matchID);
                if($mark->isAutomatic){
                    $assignment->removeReviewMark($matchID);
                }
            }
        }
    }
}


