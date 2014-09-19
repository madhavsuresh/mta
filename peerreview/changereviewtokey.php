<?php
require_once("inc/common.php");
require_once("inc/calibrationutils.php");
try
{
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();
	
	$assignment = get_peerreview_assignment();
	
	$submission = $assignment->getSubmission(new SubmissionID (require_from_get("submissionid")));
	
	$markerMatches = $assignment->getInstructorMatchesForSubmission($submission->submissionID);//Is this the right function here??? Also takes anonymous reviews
	$content = "";
	
	$keyMatches = $assignment->getCalibrationKeyMatchesForSubmission($submission->submissionID);
	
	if(0 != $keyMatches)
		$content .= "There is already a calibration key for this submission";
	elseif(0 == sizeof($markerMatches))
		$content .= "There is no marker review for this submission";
	elseif(1 < sizeof($markerMatches))
		$content .= "There are more than one marker reviews for this submission";
	elseif(1 == sizeof($markerMatches))
	{
		global $dataMgr;
		
		$review = $assignment->getReview($markerMatches[0]);
		$newReviewerID = $assignment->getUserIDForCopyingReview($review->reviewerID, $dataMgr->getUsername($review->reviewerID), $submission->submissionID);
		$newMatchID = $assignment->createMatch($submission->submissionID, $newReviewerID, false, 1);
		$review->reviewerID = $newReviewerID;
		$review->matchID = $newMatchID;
		$assignment->saveReview($review);
		
		$markerDisplayName = $dataMgr->getUserDisplayName($review->reviewerID);
		
		$content .= "Review by ".$markerDisplayName." has been copied as a calibration review"; 
		
		//Note: won't be able to delete the copied calibration key. Cannot be undone.
	}
	
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
