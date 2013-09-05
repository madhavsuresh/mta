<?php 
require_once("inc/common.php");

try
{
    $title .= " | View Calibration";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $assignment = get_peerreview_assignment();

    $beforeReviewStart = $NOW < $assignment->reviewStartDate;
    $afterReviewStop   = $assignment->reviewStopDate < $NOW;

    if(array_key_exists("calibration", $_GET)){
        #We're in student mode
        $reviewerID = $USERID;
        $id = $_GET["calibration"];
        $reviewAssignments = $assignment->getAssignedCalibrationReviews($reviewerID);

        #Try and extract who the author is - if we have an invalid index, return to main
        if(!isset($reviewAssignments[$id]))
            throw new Exception("No review with id $id");

        #Get the match id, then everything else
        $matchID = $reviewAssignments[$id];

        $assignmentWithSubmission = $dataMgr->getAssignment($dataMgr->getAssignmentDataManager("peerreview")->getAssignmentIDForMatchID($matchID));

        $submission = $assignmentWithSubmission->getSubmission($matchID);
        $instructorReview = $assignmentWithSubmission->getSingleInstructorReviewForSubmission($submission->submissionID);
        $review = $assignmentWithSubmission->getReview($matchID);
        $reviewerName = $dataMgr->getUserDisplayName($reviewerID);
    }
    else
    {
        redirect_to_main();
    }

    #Check to make sure submissions are valid
    if($beforeReviewStart)
    {
        $content .= 'This assignment has not been posted';
    }
    else if($afterReviewStop)
    {
        $content .= 'Reviews can no longer be submitted';
    }
    else if($assignment->deniedUser($review->reviewerID))
    {
        $content .= 'You have been excluded from this assignment';
    }
    else #There's no reason not to run up the submission interface now
    {
        #Show the submission question
        $content .= "<h1>Submission Question</h1>\n";
        $content .= $assignmentWithSubmission->submissionQuestion;

        #Get the review that we are currently working on
        $content .= "<h1>Submission</h1>\n";
        $content .= $submission->getHTML();
        
        //Get the instructor's review
        $content .= "<h1>Instructor Review</h1>\n";
        $content .= $instructorReview->getHTML();

        //Make the user's review
        $content .= "<h1>$reviewerName's Review</h1>\n";
        $content .= $review->getHTML();

        //Tell them how many points they recieved
        $content .= "<h1>Review Points</h1>\n";
        $content .= $assignmentWithSubmission->getReviewMark($matchID)->getReviewPoints();

        if(array_key_exists("saved", $_GET))
        $content .= "<br><a href='".get_redirect_url("peerreview/requestcalibrationreviews.php?assignmentid=".$assignment->assignmentID)."'>Get another calibration essay</a>";
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

