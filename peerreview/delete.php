<?php
require_once("inc/common.php");
try
{
    $title .= " | Delete";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    $assignment = get_peerreview_assignment();
    $type = require_from_get("type");

    #Build a list of what we need to pass on
    $args = "type=$type";

    #If we have the confirm key, then we do the delete and jump on back to the target page
    if(array_key_exists("confirm", $_GET))
    {
        if($type == "submission")
        {
            $authorID = new UserID(require_from_get("authorid"));
            $assignment->deleteSubmission($assignment->getSubmissionID($authorID));
        }
        else if($type == "review")
        {
            $assignment->deleteReview(new MatchID(require_from_get("matchid")));
        }
        else
        {
            throw new Exception("Unknown type '$type'");
        }
        redirect_to_page("peerreview/index.php?assignmentid=$assignment->assignmentID");
    }
    else
    {

        if($type == "submission")
        {
            $authorID = new UserID(require_from_get("authorid"));
            $content .= "<h1>Delete ".$dataMgr->getUserDisplayName($authorID)."'s submission?</h1>\n";
            $content .= "Note that all reviews/marks that are attached to this submission will also be deleted\n";
            $args .= "&authorid=$authorID";
        }
        else if($type == "review")
        {
            $matchID= new MatchID(require_from_get("matchid"));
            $review = $assignment->getReview($matchID);
            $submission = $assignment->getSubmission($matchID);
            $content .= "<h1>Delete ".$dataMgr->getUserDisplayName($review->reviewerID)."'s review of ".$dataMgr->getUserDisplayName($submission->authorID)."'s submission?</h1>\n";
            $args .= "&matchid=$matchID";
        }
        else
        {
            throw new Exception("Unknown type '$type'");
        }

        $content .= "<table><tr><td>";
        $content .= "<a href='".get_redirect_url("peerreview/index.php?assignmentid=$assignment->assignmentID")."'>Cancel</a></td>\n";
        $content .= "<td width=40>&nbsp&nbsp</td>\n";
        $content .= "<td><a href='".get_redirect_url("peerreview/delete.php?confirm=1&assignmentid=$assignment->assignmentID&$args")."'>Confirm</a></td></tr></table>\n";

        render_page();
    }
}catch(Exception $e){
    render_exception_page($e);
}
?>

