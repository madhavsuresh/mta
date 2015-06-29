<?php
require_once("inc/common.php");
try
{
    $title = " | Submit Spot Check";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    $assignment = get_peerreview_assignment();

    #Figure out what type we're saving
    $check = new SpotCheck();
    $check->loadFromPost($_POST);

    $assignment->saveSpotCheck($check);

    $content .= '<script type="text/javascript"> window.onload = function(){window.opener.location.reload(); window.close();} </script>';

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>

