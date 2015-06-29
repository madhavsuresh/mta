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

    $appealType = require_from_get("appealtype");
    if($appealType != "review" && $appealType != "reviewmark") {
        throw new Exception("Unknown appeal type $appealType");
    }

    //See if we can figure out if this has a student's response
    $appealID = NULL;
    $appealAuthor = $USERID;
    $viewedByStudent = false;
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
        $viewedByStudent = true;

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
    }
    else if(array_key_exists("matchid", $_GET))
    {
        $authMgr->enforceMarker();

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

    $appealMessage = new AppealMessage($appealID, $appealType, $review->matchID, $appealAuthor);
    $appealMessage->loadFromPost($_POST);
    $assignment->saveAppealMessage($appealMessage);
	if($dataMgr->isStudent($appealAuthor))
		$assignment->assignAppeal($appealMessage->matchID);

    $content .= "<h1>Appeal Submitted</h1>\n";
    $appeal = $assignment->getAppeal($review->matchID, $appealType);
    $content .= $appeal->getHTML();

    if($closeOnDone)
    {
        $content .= '<script type="text/javascript"> window.onload = function(){window.opener.location.reload(); window.close();} </script>';
    }

    if($viewedByStudent)
        $assignment->markAppealAsViewedByStudent($review->matchID, $appealType);

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>

