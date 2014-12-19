<?php
require_once("inc/common.php");
try
{
    $title = " | Submit Mark";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    $assignment = get_peerreview_assignment();

    #Figure out what type we're saving
    $type = require_from_get("type");

    if($type == "submission")
    {
        $mark = new Mark();
        $mark->loadFromPost($_POST);
        $assignment->saveSubmissionMark($mark, new SubmissionID(require_from_get("submissionid")));
    }
    else if ($type == "review")
    {
        $mark = new ReviewMark();
        $mark->loadFromPost($_POST);
        $assignment->saveReviewMark($mark, new MatchID(require_from_get("matchid")));
    }
    else
    {
        throw new Exception("Unknown mark type '$type'");
    }

    $content .= '<script type="text/javascript"> window.onload = function(){window.opener.location.reload(); window.close();} </script>';

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>
