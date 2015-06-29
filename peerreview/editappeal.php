<?php
require_once("inc/common.php");
try
{
    $title .= " | View/Edit Appeal";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    #Get this assignment's data
    $assignment = get_peerreview_assignment();

    if(array_key_exists("close", $_GET))
        $closeOnDone = "&close=1";
    else
        $closeOnDone = "";

    $viewedByStudent = false;

    $appealType = require_from_get("appealtype");
    if($appealType != "review" && $appealType != "reviewmark") {
        throw new Exception("Unknown appeal type $appealType");
    }

    //See if we can figure out if this has a student's response
    if(array_key_exists("reviewid", $_GET))
    {
        $reviewid = intval($_GET["reviewid"]);
        //We can only show this if we're after the post date
        if($NOW < $assignment->markPostDate)
        {
            $content .= "Marks have not yet been posted";
            render_page();
        }

        //Figure out if this review exists
        switch($appealType)
        {
        case "review":
            $reviews = $assignment->getReviewsForSubmission($assignment->getSubmissionID($USERID));
            $reviews = array_values(array_filter($reviews, function($v) { return sizeof($v->answers) > 0; }));
            if(isset($reviews[$reviewid])) {
                $review = $reviews[$reviewid];
            } else {
                throw new Exception("Invalid review ID");
            }
            break;
        case "reviewmark":
            //Get the specified id
            $matches = $assignment->getAssignedReviews($USERID);
			$reviews = array();
			foreach($matches as $match)
				$reviews[] = $assignment->getReview($match);
            $reviews = array_values(array_filter($reviews, function($v) { return sizeof($v->answers) > 0; }));
            if(isset($reviews[$reviewid])) {
                $review = $reviews[$reviewid];
            } else {
                throw new Exception("Invalid review ID");
            }
            break;
        default:
            throw new Exception("Unknown appeal type $appealType");
        }

        //If we're after the stop date, we better be sure that this appeal exists
        if(grace($assignment->appealStopDate) < $NOW && !$assignment->appealExists($review->matchID, $appealType))
        {
            $content .= "Appeal submissions are closed";
            render_page();
        }

        $submission = $assignment->getSubmission($review->matchID);

        switch($appealType){
        case "review":
            if($submission->authorID->id != $USERID->id)
                throw new Exception("A serious error happened - contact your TA");
            break;
        case "reviewmark":
            if($review->reviewerID->id != $USERID->id)
                throw new Exception("A serious error happened - contact your TA");
            break;
        default:
            throw new Exception("Unknown appeal type $appealType");
        }

        $getParams = "reviewid=".$_GET["reviewid"];
        $viewedByStudent = true;
    }
    else if(array_key_exists("matchid", $_GET))
    {
        $authMgr->enforceMarker();

        //Get this review and submission
        $matchID = new MatchID($_GET["matchid"]);
        $review = $assignment->getReview($matchID);
        $submission = $assignment->getSubmission($matchID);
        $getParams = "matchid=$matchID";
    }
    else
    {
        throw new Exception("No valid object for an appeal");
    }

    $appeal = $assignment->getAppeal($review->matchID, $appealType);

    $content .= init_tiny_mce(false);
    $content .= "<h1>Submission</h1>\n";
    $content .= $submission->getHTML();
    $content .= "<h1>Appealed Review</h1>\n";
    $content .= $review->getHTML();
    if($appealType == "reviewmark" || $assignment->showMarksForReviewsReceived)
    {
        $content .= "<h1>Appealed Review Mark</h1>\n";
        $content .= $assignment->getReviewMark($review->matchID)->getHTML();
    }
    $content .= "<h1>Appeal</h1>\n";
    $content .= $appeal->getHTML();

    //Add in the form slot
    $appealMessage = new AppealMessage(null, $appealType, $review->matchID, $USERID);
    $content .= "<br class='clear'><h2>Add Message</h2>\n";
    $content .= "<form id='submission' action='".get_redirect_url("peerreview/submitappeal.php?assignmentid=$assignment->assignmentID&appealtype=$appealType&$getParams$closeOnDone")."' method='post'>\n";
    $content .= $appealMessage->getFormHTML();
    $content .= "<input type='submit' value='Submit' />\n";
    $content .= "</form>\n";

    //If we've gotten here, we need to update the review entries that we've shown
    if($viewedByStudent)
        $assignment->markAppealAsViewedByStudent($review->matchID, $appealType);

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
