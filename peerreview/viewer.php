<?php
require_once("inc/common.php");
try
{
    $title .= " | Viewer";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    $assignment = get_peerreview_assignment(false);

    $i = 0;
    while(array_key_exists("type$i", $_GET))
    {
        $type = $_GET["type$i"];
        if($type == "submission")
        {
            $submissionID= new SubmissionID($_GET["submissionid$i"]);
            $submission = $assignment->getSubmission($submissionID);

            $authorName = $dataMgr->getUserDisplayName($submission->authorID);
            $content .= "<h1>$authorName's Submission</h1>\n";
            #Escape the submission
            $content .= $submission->getHTML(true);
            $content .= "<h2>Mark</h2>\n";
            $content .= $assignment->getSubmissionMark($submissionID)->getHTML();
        }
        else if($type == "review")
        {
            $matchID = new MatchID($_GET["matchid$i"]);

            $review = $assignment->getReview($matchID);
            $reviewerName = $dataMgr->getUserDisplayName($review->reviewerID);
			$submission = $assignment->getSubmission($matchID);
			$submitterName = $dataMgr->getUserDisplayName($submission->authorID);
            $content .= "<h1>Review by $reviewerName on submission by $submitterName</h1>\n";
            $content .= $review->getHTML(true);

            $content .= "<h2>Mark</h2>\n";
            $content .= $assignment->getReviewMark($matchID)->getHTML();
        }
        else if($type == "spotcheck")
        {
            $matchID = new SubmissionID(require_from_get("submissionid$i"));
            $content .= "<h1>Spot Check</h1>";
            $content .= "<form action='".get_redirect_url("peerreview/submitspotcheck.php?assignmentid=$assignment->assignmentID")."' method='post'>\n";
            $content .= $assignment->getSpotCheck($matchID)->getFormHTML();
            $content .= "<br><br><input type='submit' value='Save' />\n";
            $content .= "</form>\n";
        }
        else
        {
            $content .= "<h1>Can't display item $i</h1>\n";
        }

        $i=$i+1;
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
