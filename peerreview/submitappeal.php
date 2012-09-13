<?php
require_once("inc/common.php");
try
{
    $title .= " | Submit Appeal";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    #Get this assignment's data
    $assignment = get_peerreview_assignment();

    $closeOnDone = array_key_exists("close", $_GET);

    //See if we can figure out if this has a student's response
    $appealID = NULL;
    $appealAuthor = $USERID;
    $viewedByStudent = false;
    if(array_key_exists("reviewid", $_GET))
    {
        //We can only show this if we're after the post date
        if($NOW < $assignment->markPostDate)
        {
            $content .= "Marks have not yet been posted";
            render_page();
        }

        //Figure out if this review exists
        $reviews = $assignment->getReviewsForSubmission($assignment->getSubmissionID($USERID));
        if(isset($reviews[$_GET["reviewid"]])) {
            $review = $reviews[$_GET["reviewid"]];
        } else {
            throw new Exception("Invalid review ID");
        }

        //If we're after the stop date, we better be sure that this appeal exists
        if($assignment->appealStopDate < $NOW && !$assignment->appealExists($review->matchID))
        {
            $content .= "Appeal submissions are closed";
            render_page();
        }

        $submission = $assignment->getSubmission($review->matchID);
        $viewedByStudent = true;

        if($submission->authorID->id != $USERID->id)
            throw new Exception("A serious error happened - contact your TA");
    }
    else if(array_key_exists("matchid", $_GET))
    {
        $authMgr->enforceInstructor();

        //Get this review and submission
        $matchID = new MatchID($_GET["matchid"]);
        $review = $assignment->getReview($matchID);
        $submission = $assignment->getSubmission($matchID);

        if(array_key_exists("appealid", $_GET))
        {
            $appealID = $_GET["appealid"];
        }
        if(array_key_exists("authorid", $_GET))
        {
            $appealAuthor = new UserID($_GET["authorid"]);
        }
    }
    else
    {
        throw new Exception("No valid object for an appeal");
    }

    $appealMessage = new AppealMessage($appealID, $review->matchID, $appealAuthor);
    $appealMessage->loadFromPost($_POST);
    $assignment->saveAppealMessage($appealMessage);

    $content .= "<h1>Appeal Submitted</h1>\n";
    $appeal = $assignment->getAppeal($review->matchID);
    $content .= $appeal->getHTML();

    if($closeOnDone)
    {
        $content .= '<script type="text/javascript"> window.onload = function(){window.close();} </script>';
    }

    if($viewedByStudent)
        $assignment->markAppealAsViewedByStudent($review->matchID);

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>

