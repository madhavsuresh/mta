<?php
require_once("inc/common.php");
try
{
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();
    #Get this assignment's data
    $submissionID =  new SubmissionID(require_from_get("submission"));
    $assignmentID = $dataMgr->getAssignmentDataManager("peerreview")->getAssignmentIDForSubmissionID($submissionID);
    $assignment = $dataMgr->getAssignment($assignmentID, "peerreview");
    $submission = $assignment->getSubmission($submissionID);

    $download = optional_from_get("download", false);

    function isReviewerForCurrentAssignment()
    {
        global $USERID, $assignment, $submissionID, $NOW;
        if($NOW < $assignment->reviewStartDate){
            //They shouldn't have access yet
            return false;
        }

        $matches = $assignment->getMatchesForSubmission($submissionID);
        foreach($matches as $match){
            if($assignment->getReviewerByMatch($match) == $USERID->id){
                return true;
            }
        }
        return false;
    }

    if($submission->authorID == $USERID->id || $dataMgr->isMarker($USERID) || isReviewerForCurrentAssignment()) {
        $submission->_dumpRaw($download, true);
        exit(0);
    }else{
        $content .= "You do not have permission to view this submission";
        render_page();
    }
}catch(Exception $e){
    render_exception_page($e);
}
?>
