<?php
require_once("inc/common.php");
try
{
    $title = " | View Question";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $assignment = get_peerreview_assignment();

    $content .= "<h1>$assignment->name</h1>\n";

    $content .= "<h2>Question</h2>\n";
    $content .= $assignment->submissionQuestion;

    $content .= "<h2>Submission</h2>\n";
    #Show the submission
    try
    {
        $content .= $assignment->getSubmission($assignment->getSubmissionID($USERID))->getHTML();
    }catch(Exception $e){
        $content .= "(No Submission)\n";
    }
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>


