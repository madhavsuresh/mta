<?php
require_once("inc/common.php");
require_once("inc/calibrationutils.php");
try
{
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();
	
	$assignment = get_peerreview_assignment();
	
	$submission = $assignment->getSubmission(new SubmissionID (require_from_get("submissionid")));
	
	$matches = $assignment->getSpecialMatchesForSubmission($submission->submissionID);//Is this the right function here??? Also takes anonymous reviews
	$content = "";
	
	if(1 == sizeof($matches))
	{
		global $dataMgr;
		
		$review = $assignment->getReview($matches[0]);
		$newReviewerID = $assignment->getUserIDForCopyingReview($review->reviewerID, $dataMgr->getUsername($review->reviewerID), $submission->submissionID);
		$newMatchID = $assignment->createMatch($submission->submissionID, $newReviewerID, false, 1);
		$review->reviewerID = $newReviewerID;
		$review->matchID = $newMatchID;
		$assignment->saveReview($review);
		
		$markerDisplayName = $dataMgr->getUserDisplayName($review->reviewerID);
		
		$content .= "Review by ".$markerDisplayName." has been copied as a calibration review"; 
	}elseif(0 == sizeof($matches))
		$content .= "There is no marker review for this submission";
	elseif(1 < sizeof($matches))
		$content .= "There are more than one marker review for this submission";
	
	//check how many calibrationKey reviews there are after
	
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
