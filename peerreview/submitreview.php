<?php
require_once("inc/common.php");
require_once(dirname(__FILE__)."/inc/calibrationutils.php");
try
{
    function displayReviewWithError($msg = "")
    {
        global $authMgr, $_POST, $content;

        $content .= $msg;

        try
        {
            $review = new Review(get_peerreview_assignment());
            $review->loadFromPost($_POST, true);
            $content .= "<h1>Unsaved Review</h1>\n";
            $content .= $review->getHTML();
        } catch(Exception $e){
            //Just eat it
        }
        render_page();
    }
    $title .= " | Submit Review";
    if(!$authMgr->isLoggedIn())
    {
        displayReviewWithError("<h2>Session Expired</h2><a href='".get_redirect_url("login.php")."'>Login</a><br><br>");
    }
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $closeOnDone = array_key_exists("close", $_GET);
    $action = "save";
    if(require_from_post("saveAction") == "Save Draft")
    {
        $action = "draft";
        $closeOnDone=false;
    }

    #Get this assignment's data
    $assignment = get_peerreview_assignment();
    $assignmentWithSubmission = $assignment;

    $isCalibration = false;
    $beforeReviewStart = $NOW < $assignment->reviewStartDate;
    $afterReviewStop   = grace($assignment->reviewStopDate) < $NOW;
	$beforeCalibrationStart = $NOW < $assignment->calibrationStartDate;
	$afterCalibrationStop   = grace($assignment->calibrationStopDate) < $NOW;

   	if(array_key_exists("review", $_GET) || array_key_exists("calibration", $_GET)){
        #We're in student mode
        $reviewerID = $USERID;
        if(array_key_exists("review", $_GET)){
            $id = $_GET["review"];
            $reviewAssignments = $assignment->getAssignedReviews($reviewerID);
        }else{
            $id = $_GET["calibration"];
            $reviewAssignments = $assignment->getAssignedCalibrationReviews($reviewerID);
			$isCalibration = true;
        }

        #Try and extract who the author is - if we have an invalid index, return to main
        if(!isset($reviewAssignments[$id]))
            throw new Exception("No review assignment with id $id");	

        #Set the match id
        $matchID = $reviewAssignments[$id];

        //Check to make sure that they haven't already submitted this
        if($isCalibration){
            //Get the right assignment that has the data in it
            $assignmentWithSubmission = $dataMgr->getAssignment($dataMgr->getAssignmentDataManager("peerreview")->getAssignmentIDForMatchID($matchID));
            if($assignmentWithSubmission->getReviewMark($matchID)->isValid)
                redirect_to_page("peerreview/viewcalibration.php?assignmentid=$assignment->assignmentID&calibration=".$_GET["calibration"]);
        }
    }
    else
    {
        //We better be an instructor
        $authMgr->enforceMarker();

        if(array_key_exists("matchid", $_GET))
        {
            //This is easy, just go load it up
            $matchID = new MatchID($_GET["matchid"]);
            $review = $assignment->getReview($matchID);
            $reviewerID = $review->reviewerID;
        }
        else if(array_key_exists("reviewer", $_GET))
        {
            //Get the submission, and make a new review
            $reviewer = require_from_get("reviewer");
            $submissionID = new SubmissionID(require_from_get("submissionid"));

            //We now need to make a match here, so we need to get the appropriate user
            if($reviewer == "instructor")
                $reviewerID = $assignment->getUserIDForInstructorReview($USERID, $authMgr->getCurrentUsername(), $submissionID);
            else if($reviewer == "anonymous")
                $reviewerID = $assignment->getUserIDForAnonymousReview($USERID, $authMgr->getCurrentUsername(), $submissionID);
            else
                throw new Exception("Unknown reviewer type '$reviewer'");

            $matchID = $assignment->createMatch($submissionID, $reviewerID, true);
        }
        else
        {
            //No idea what this is
            throw new Exception("No valid options specified");
        }
        #We can just override the data on this assignment so that we can force a write
        $beforeReviewStart = false;
        $afterReviewStop   = false;
		$beforeCalibrationStart = false; //Not necessary but for completion
		$afterCalibrationStop   = false; //Not necessary but for completion
    }

    if($isCalibration ? $beforeCalibrationStart : $beforeReviewStart) 
    {
        displayReviewWithError('This assignment has not been posted');
    }
    else if($isCalibration ? $afterCalibrationStop : $afterReviewStop)
    {
        displayReviewWithError('Reviews can no longer be submitted');
    }
    else if($assignment->deniedUser($reviewerID))
    {
        displayReviewWithError('You have been excluded from this assignment');
    }
    else
    {
        #Recover everything from the post
        $review = new Review($assignmentWithSubmission);
        $review->matchID = $matchID;
        $review->reviewerID = $reviewerID;
        $review->submissionID = $assignment->getSubmissionID($matchID);

        $review->loadFromPost($_POST, $action=="draft");

        if($action == "save")
        {
            $assignmentWithSubmission->saveReview($review);
            $assignmentWithSubmission->deleteReviewDraft($review->matchID);

            if(!$isCalibration)
                $content .= "Review saved - check to make sure that it looks right below. You may edit your review by returning to the home page.\n";
			$author = $assignmentWithSubmission->getSubmission($review->submissionID)->authorID;
			if(($dataMgr->isInstructor($review->reviewerID) || $dataMgr->isMarker($review->reviewerID)) && $dataMgr->isStudent($author))
				$assignmentWithSubmission->saveSubmissionMark(new Mark($review->getScore(), ""), $review->submissionID);
        }
        else
        {
            $content .= "Review draft saved. You may edit your review by returning to the home page.\n";
            $content .= "<br>Note that review drafts are not marked, you must submit it.\n";
            $assignmentWithSubmission->saveReviewDraft($review);
            $assignmentWithSubmission->deleteReview($review->matchID, false);
        }
        $content .= "<h1>Review</h1>\n";
        if($action == "save"){
            if(!$isCalibration){
                $content .= $assignmentWithSubmission->getReview($matchID)->getShortHTML();
            }else{
                //Do the auto grade
                $instructorReview = $assignmentWithSubmission->getSingleCalibrationKeyReviewForSubmission($review->submissionID);

                $mark = generateAutoMark($assignmentWithSubmission, $instructorReview, $review);
                $assignment->saveReviewMark($mark, $matchID);
                //When we're done, redirect them
                redirect_to_page("peerreview/viewcalibration.php?saved=1&assignmentid=$assignment->assignmentID&calibration=".$_GET["calibration"]);
            }
        }else{
            $content .= $assignmentWithSubmission->getReviewDraft($matchID)->getShortHTML();
        }
    }

    if($closeOnDone)
    {
        $content .= '<script type="text/javascript"> window.onload = function(){window.opener.location.reload(); window.close();} </script>';
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>

