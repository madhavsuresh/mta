<?php
include("inc/common.php");
try
{
    $title = " | Submit Mark";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    $assignment = get_peerreview_assignment();

    #Figure out what type we're saving
    $type = require_from_get("type");
    $mark = new Mark();
    $mark->loadFromPost($_POST);

    if($type == "submission")
    {
        $assignment->saveSubmissionMark($mark, new SubmissionID(require_from_get("submissionid")));
    }
    else if ($type == "review")
    {
        $assignment->saveReviewMark($mark, new MatchID(require_from_get("matchid")));
    }
    else
    {
        throw new Exception("Unknown mark type '$type'");
    }

    $content .= '<script type="text/javascript"> window.onload = function(){window.close();} </script>';

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>
