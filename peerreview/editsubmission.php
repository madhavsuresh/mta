<?php
require_once("../inc/common.php");
try
{
    $title .= " | Edit Submission";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    if(array_key_exists("close", $_GET))
        $closeOnDone = "&close=1";
    else
        $closeOnDone = "";

    #Get this assignment's data
    $assignment = get_peerreview_assignment();
    $authorID = $USERID;

    $beforeSubmissionStart = $NOW < $assignment->submissionStartDate;
    $afterSubmissionStop   = grace($assignment->submissionStopDate) < $NOW;
    #Have we been given an $author override?
    $authorInGet='';
    if(array_key_exists("authorid", $_GET)){
        $authMgr->enforceMarker($USERID);
        $authorID = new UserID($_GET["authorid"]);
        if(!$dataMgr->isUser($authorID))
            throw new Exception("Invalid author ID");
        $authorInGet="&authorid=$authorID";

        #We can just override the data on this assignment so that we can force a write
        $beforeSubmissionStart = false;
        $afterSubmissionStop   = false;
    }
    if(!$dataMgr->isUser($authorID))
        throw new Exception("User id '$author' is not a valid user");

    #Check to make sure submissions are valid
    if($beforeSubmissionStart)
    {
        $content .= "This assignment has not been posted\n";
    }
    else if($afterSubmissionStop)
    {
        $content .= "Submissions can no longer be submitted\n";
    }
    else if($assignment->deniedUser($authorID))
    {
        $content .= "You have been excluded from this assignment\n";
    }
    else #They've passed all the roadblocks - let them write something
    {
        $content .= init_tiny_mce(false);
        $content .= "<h1>Current Submission Question</h1>\n";
        #Remember that the submissionQuestion may have endlines in it
        $content .= $assignment->submissionQuestion;

        $content .= "\n<br><br>\n";
        $content .= "The submission is due <span id='submissionStopDate'.>\n";
        $content .= set_element_to_date("submissionStopDate", $assignment->submissionStopDate, "html", $assignment->dateFormat, false);

        $content .= "</div><div class='box'>\n";
        $content .= "<h1>" . $dataMgr->getUserDisplayName($authorID)."'s Submission</h1>\n";

        try {
            $submission = $assignment->getSubmission($authorID);
        } catch(Exception $e) {
            $submissionType = $assignment->submissionType."Submission";
            $submission = new $submissionType($assignment->submissionSettings);
        }

        $attribs  = $submission->getFormAttribs();
        $content .= "<form id='submission' action='".get_redirect_url("peerreview/submitsubmission.php?assignmentid=$assignment->assignmentID&$authorInGet$closeOnDone")."' method='post' accept-charset='UTF-8' $attribs >\n";

        $content .= $submission->getFormHTML();
        $content .= "<input type='submit' value='Submit' />\n";
        $content .= "</form>\n";
        $content .= "<script type='text/javascript'> $(document).ready(function(){ $('#submission').submit(function() {\n";
        $content .= "var error = false;\n";
        $content .= $submission->getValidationCode();
        $content .= "if(error){return false;}else{return true;}\n";
        $content .= "}); }); </script>\n";
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>
