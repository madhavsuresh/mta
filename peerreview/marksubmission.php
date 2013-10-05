<?php
require_once("inc/common.php");
try
{
    $title .= " | Mark Submission";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    #Get this assignment's data
    $assignment = get_peerreview_assignment(false);
    $submissionID = new SubmissionID(require_from_get("submissionid"));

    $submission = $assignment->getSubmission($submissionID);

    $content .= "<h1>".$dataMgr->getUserDisplayName($submission->authorID)."'s Submission</h1>";
    $content .= $submission->getHTML();

    $content .= "<form id='mark' action='".get_redirect_url("peerreview/submitmark.php?assignmentid=$assignment->assignmentID&type=submission&submissionid=$submissionID")."' method='post'>";
    $content .= $assignment->getSubmissionMark($submissionID)->getFormHTML();
    $content .= "<br><br><input type='submit' value='Submit' />";
    $content .= '</form>';

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>

