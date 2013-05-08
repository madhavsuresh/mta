<?php
require_once("inc/common.php");
try
{
    $title .= " | Mark Review";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    $assignment=get_peerreview_assignment();
    $matchID = new MatchID(require_from_get("matchid"));

    $review = $assignment->getReview($matchID);
    $submission = $assignment->getSubmission($review->submissionID);

    $content .= "<h1>".$dataMgr->getUserDisplayName($submission->authorID)."'s Submission</h1>\n";
    $content .= $submission->getHTML();

    $content .= "<h1>".$dataMgr->getUserDisplayName($review->reviewerID)."'s Review</h1>\n";
    $content .= $review->getHTML(true);

    $content .= "<h1>".$dataMgr->getUserDisplayName($review->reviewerID)."'s Current Review Score</h1>\n";
    //TODO: Remove this hardcoded bit for the window size
    $content .= precisionFloat(compute_peer_review_score_for_assignments($review->reviewerID, $assignment->getAssignmentsBefore(4))*100)."%";

    $content .= "<form id='mark' action='".get_redirect_url("peerreview/submitmark.php?assignmentid=$assignment->assignmentID&type=review&matchid=$matchID")."' method='post'>\n";
    $content .= $assignment->getReviewMark($matchID)->getFormHTML();
    $content .= "<br><br><input type='submit' value='Submit' />\n";
    $content .= "</form>\n";

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>
