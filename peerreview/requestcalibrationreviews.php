<?php
require_once("inc/common.php");
try
{
    $title = " | Request Calibration Review";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $assignment = get_peerreview_assignment();

    $submissionID = $assignment->getNewCalibrationSubmissionForUser($USERID);

    if(!is_null($submissionID))
    {
        $assignment->assignCalibrationReview($submissionID, $USERID); 
        //redirect_to_main();
        $newID = sizeof($assignment->getAssignedCalibrationReviews($USERID)) - 1;
        redirect_to_page("peerreview/editreview.php?assignmentid=$assignment->assignmentID&calibration=$newID");
    }

    //If we don't escape, say that they've done everything
    $content .= "<h3>Oops</h3>There are no more calibration essays to do!";
    render_page();

}catch(Exception $e){
    render_exception_page($e);
}
?>

