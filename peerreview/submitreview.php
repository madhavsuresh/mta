<?php
include("inc/common.php");
try
{
    $title .= " | Submit Review";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $closeOnDone = array_key_exists("close", $_GET);

    #Get this assignment's data
    $assignment = get_peerreview_assignment();

    $beforeReviewStart = $NOW < $assignment->reviewStartDate;
    $afterReviewStop   = $assignment->reviewStopDate < $NOW;

    if(array_key_exists("reviewid", $_GET)){
        #We're in student mode
        $id = $_GET["reviewid"];
        $reviewerID = $USERID;

        $reviewAssignments = $assignment->getAssignedReviews($reviewerID);

        #Try and extract who the author is - if we have an invalid index, return to main
        if(!isset($reviewAssignments[$id]))
            throw new Exception("No review assignment with id $id");

        #Set the match id
        $matchID = $reviewAssignments[$id];
    }
    else
    {
        //We better be an instructor
        $authMgr->enforceInstructor();

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
    }

    if($beforeReviewStart)
    {
        $content .= 'This assignment has not been posted';
    }
    else if($afterReviewStop)
    {
        $content .= 'Reviews can no longer be submitted';
    }
    else if($assignment->deniedUser($reviewerID))
    {
        $content .= 'You have been excluded from this assignment';
    }
    else
    {
        #Recover everything from the post
        $review = new Review($assignment);
        $review->matchID = $matchID;
        $review->reviewerID = $reviewerID;

        $review->loadFromPost($_POST);
        $assignment->saveReview($review);

        $content .= "Review saved - check to make sure that it looks right below. You may edit your review by returning to the home page\n";
        $content .= "<h1>Review</h1>\n";
        $content .= $assignment->getReview($matchID)->getShortHTML();
    }

    if($closeOnDone)
    {
        $content .= '<script type="text/javascript"> window.onload = function(){window.close();} </script>';
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>

