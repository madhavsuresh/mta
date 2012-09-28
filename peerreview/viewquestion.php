<?php
include("inc/common.php");
try
{
    $title = " | View Question";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $assignment = get_peerreview_assignment();

    $content .= "<h1>$assignment->name</h1>\n";

    $content .= "<h2>Question</h2>\n";
    $content .= $assignment->submissionQuestion;

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>

