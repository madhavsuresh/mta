<?php
require_once("inc/common.php");
try
{
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();
	
	$assignment = get_peerreview_assignment();
	
	$submission = $assignment->getSubmission(new SubmissionID (require_from_get("submissionid")));
	
	$markerMatches = $assignment->getInstructorMatchesForSubmission($submission->submissionID);//TODO: Is this the right function here??? Also takes anonymous reviews
	$markerMatches = array_filter($markerMatches, function($matchID) use ($assignment){$review = $assignment->getReview(new MatchID($matchID)); return $review->answers;});
	
	$content = "";
	
	$keyMatches = $assignment->getCalibrationKeyMatchesForSubmission($submission->submissionID);
	$keyMatches = array_filter($keyMatches, function($matchID) use ($assignment){$review = $assignment->getReview(new MatchID($matchID)); return $review->answers;});
	
	$errors = array();
	
	if(0 != sizeof($keyMatches))
		$errors[] = "There is already a calibration key for this submission";
	if(0 == sizeof($markerMatches))
		$errors[] = "There is no marker review for this submission";
	if(1 < sizeof($markerMatches))
		$errors[] = "There are more than one marker reviews for this submission";
	if((1 == sizeof($markerMatches)) && 0 == sizeof($errors))
	{
		global $dataMgr;
		
		$review = $assignment->getReview($markerMatches[0]);
		$markerDisplayName = $dataMgr->getUserDisplayName($review->reviewerID);
		$newReviewerID = $assignment->getUserIDForCopyingReview($review->reviewerID, $dataMgr->getUsername($review->reviewerID), $submission->submissionID);
		$newMatchID = $assignment->createMatch($submission->submissionID, $newReviewerID, true, 'key');
		$review->reviewerID = $newReviewerID;
		$newAnonymousName = $dataMgr->getUserDisplayName($newReviewerID);
		$review->matchID = $newMatchID;
		$assignment->saveReview($review);
		
		$content .= "Review by $markerDisplayName has been copied as a  calibration key by $newAnonymousName.<br>";
		$content .= "Please make sure to refresh the marking page."; 
	} else {
		$content .= "The following error(s) have been encountered:\n<ul>";
		foreach($errors as $error)
			$content .= "<li>$error</li>";
		$content .= "</ul>";
	}
	
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
