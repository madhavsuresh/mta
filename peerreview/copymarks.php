<?php
include("inc/common.php");
try
{
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    $assignment = get_peerreview_assignment();
    $authors = $assignment->getAuthorSubmissionMap();
    $scoreMap = $assignment->getMatchScoreMap();
    $reviewMap = $assignment->getReviewMap();


    foreach($reviewMap as $submissionID => $reviewObjs)
    {
        $submissionID = new SubmissionID($submissionID);
        foreach($reviewObjs as $reviewObj)
        {
            if($dataMgr->isInstructor($reviewObj->reviewerID) && array_key_exists($reviewObj->matchID->id, $scoreMap))
            {
                $assignment->saveSubmissionMark(new Mark($scoreMap[$reviewObj->matchID->id], ""), $submissionID);
                $content .= $dataMgr->getUserDisplayName($assignment->getSubmission($submissionID)->authorID).", ".$scoreMap[$reviewObj->matchID->id]."<br>";
            }
        }
    }


    render_page();

}catch(Exception $e){
    render_exception_page($e);
}
?>
