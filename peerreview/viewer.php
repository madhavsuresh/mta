<?php
include("inc/common.php");
try
{
    $title .= " | Viewer";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

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
            $content .= "<h1>Review by $reviewerName</h1>\n";
            $content .= $review->getHTML(true);

            $content .= "<h2>Mark</h2>\n";
            $content .= $assignment->getReviewMark($matchID)->getHTML();
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
