<?php
require_once("peerreview/inc/common.php");

class CopyMarksPeerReviewScript extends Script
{
    function getName()
    {
        return "Copy Instructor Marks";
    }
    function getDescription()
    {
        return "Copies instructor marks from the reviews into the essay slots in cases where the mark doesn't alreday exist";
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
        $scoreMap = $assignment->getMatchScoreMap();
        $reviewMap = $assignment->getReviewMap();

        $html = "";
        foreach($reviewMap as $submissionID => $reviewObjs)
        {
            $submissionID = new SubmissionID($submissionID);
            $oldMark = $assignment->getSubmissionMark($submissionID);
            if($oldMark->isValid)
                continue;

            foreach($reviewObjs as $reviewObj)
            {
                if(($dataMgr->isInstructor($reviewObj->reviewerID) || $dataMgr->isMarker($reviewObj->reviewerID)) && array_key_exists($reviewObj->matchID->id, $scoreMap))
                {
                    $assignment->saveSubmissionMark(new Mark($scoreMap[$reviewObj->matchID->id], ""), $submissionID);
                    $html .= $dataMgr->getUserDisplayName($assignment->getSubmission($submissionID)->authorID).", ".$scoreMap[$reviewObj->matchID->id]."<br>";
                }
            }
        }
        return $html;
    }
}

