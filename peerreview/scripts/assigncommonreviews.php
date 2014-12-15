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
		$droppedUsers = $dataMgr->getDroppedStudents();
        $html .= "<h3>Select Submissions to Review</h3>";
        $html .= "<table width='100%'>\n";
        foreach($displayMap as $authorID => $authorName)
        {
            if(!array_key_exists($authorID, $submissionAuthors))
                continue;
			if(in_array($authorID, $droppedUsers))
				continue;
            $submissionID = $submissionAuthors[$authorID];
            $html .= "<tr><td><input type='checkbox' name='submissions[]' value='$submissionID' />$authorName</td></tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
    	ini_set('display_errors','On');
        global $dataMgr;
        $assignment = get_peerreview_assignment();
        $submissions = require_from_post("submissions");
        $displayMap = $dataMgr->getUserDisplayMap();

        $students = $dataMgr->getActiveStudents();
        $reviewers = array();
        foreach($students as $reviewerID)
        {
            $reviewerID = new UserID($reviewerID);
            if(!$assignment->deniedUser($reviewerID))
                $reviewers[] = $reviewerID;
        }

        $reviewerAssignment = array();
        foreach($submissions as $submissionID){
            $submissionID = new SubmissionID($submissionID);
            $reviewerAssignment[$submissionID->id] = $reviewers;
        }
        $assignment->saveReviewerAssignment($reviewerAssignment);

        return "Assignment completed";
    }
}

