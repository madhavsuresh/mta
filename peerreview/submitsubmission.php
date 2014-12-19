<?php
require_once("inc/common.php");
try
{
    function displaySubmissionWithError($msg = "")
    {
        global $authMgr, $_POST, $content;

        $content .= $msg;

        try
        {
            $assignment = get_peerreview_assignment();
            $submissionType = $assignment->submissionType."Submission";
            $submission = new $submissionType($assignment->submissionSettings);
            $submission->loadFromPost($_POST);
            $content .= "<h1>Unsaved Submission</h1>\n";
            $content .= $submission->getHTML();
        } catch(Exception $e){
            //Just eat it
        }
        render_page();
    }

    $title .= " | Submit Submission";
    if(!$authMgr->isLoggedIn())
    {
        displaySubmissionWithError("<h2>Session Expired</h2><a href='".get_redirect_url("login.php")."'>Login</a><br><br>");
    }
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    #See if we should close on success
    $closeOnDone = array_key_exists("close", $_GET);

    #Get this assignment and author
    $assignment = get_peerreview_assignment();
    $authorID = $USERID;

    $beforeSubmissionStart = $NOW < $assignment->submissionStartDate;
    $afterSubmissionStop   = grace($assignment->submissionStopDate) < $NOW;

    if(array_key_exists("authorid", $_GET)){
        #We better be an instructor
        $authMgr->enforceMarker();
        $authorID = new UserID($_GET["authorid"]);

        if(!$dataMgr->isUser($authorID))
            throw new Exception("Invalid author ID");

        #Make sure we can still submit it
        $beforeSubmissionStart = false;
        $afterSubmissionStop   = false;
    }

    #What we do depends on the current action
    if($beforeSubmissionStart)
    {
        displaySubmissionWithError('This assignment has not been posted');
    }
    else if($afterSubmissionStop)
    {
        displaySubmissionWithError('Submissions can no longer be submitted');
    }
    else if($assignment->deniedUser($authorID))
    {
        displaySubmissionWithError('You have been excluded from this assignment');
    }
    else #There's no reasy why they can't submit
    {
        $submissionType = $assignment->submissionType."Submission";
        $submission = new $submissionType($assignment->submissionSettings);
        try
        {
            //We need to try and figure out what the old ID was. if not, it is set to null
            $submission->submissionID = $assignment->getSubmissionID($authorID);
        }catch(Exception $e) {}

        $submission->authorID = $authorID;

        $errors = $submission->loadFromPost($_POST);

        $assignment->saveSubmission($submission);

        $content .= "Submission saved - check to make sure that it looks right below. You may edit your submission by returning to the home page.\n";
        $content .= "<h1>Submission</h1>\n";
        $content .= $assignment->getSubmission($submission->submissionID)->getHTML();
    }

    if($closeOnDone)
    {
        $content .= '<script type="text/javascript"> window.onload = function(){window.opener.location.reload(); window.close();} </script>';
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
