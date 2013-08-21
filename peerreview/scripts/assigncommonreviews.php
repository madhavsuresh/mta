<?php
require_once("peerreview/inc/common.php");

class AssignCommonReviewsPeerReviewScript extends Script
{
    function getName()
    {
        return "Assign Common Reviews";
    }
    function getDescription()
    {
        return "Makes all the students perform the same set of reviews of the submissions you specify";
    }
    function getFormHTML()
    {
        global $dataMgr;
        //TODO: Load the defaults from the config
        $assignment = get_peerreview_assignment();
        $html = "";
        if(sizeof($assignment->getReviewerAssignment()))
        {
            $html .= "<h1 style='color:red;'>WARNING: About to overwrite existing review assignments</h1>\n";
            $html .= "If students have already started to submit answers, it is likely that you will delete them by running this script<br><br><br>\n";
        }

        $submissionAuthors = $assignment->getAuthorSubmissionMap();
        $displayMap = $dataMgr->getUserDisplayMap();
        $html .= "<h3>Select Submissions to Review</h3>";
        $html .= "<table width='100%'>\n";
        foreach($displayMap as $authorID => $authorName)
        {
            if(!array_key_exists($authorID, $submissionAuthors))
                continue;
            $submissionID = $submissionAuthors[$authorID];
            $html .= "<tr><td><input type='checkbox' name='submissions[]' value='$submissionID' />$authorName</td></tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        $assignment = get_peerreview_assignment();
        $submissions = require_from_post("submissions");
        $displayMap = $dataMgr->getUserDisplayMap();

        $reviewers = array();
        foreach($displayMap as $reviewerID => $reviewerName)
        {
            $reviewerID = new UserID($reviewerID);
            if($dataMgr->isStudent($reviewerID) && !$assignment->deniedUser($reviewerID))
                $reviewers[] = $reviewerID;
        }

        $reviewerAssignment = array();
        foreach($submissions as $submissionID){
            $submissionID = new SubmissionID($submissionID);
            $userNameMap = $dataMgr->getUserDisplayMap();
            $reviewerAssignment[$submissionID->id] = $reviewers;
        }
        $assignment->saveReviewerAssignment($reviewerAssignment);

        return "Assignment completed";
    }
}

