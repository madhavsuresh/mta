<?php
require_once("peerreview/inc/zip.lib.php");
require_once("peerreview/inc/common.php");

class DownloadSubmissionsPeerReviewScript extends Script
{
    function getName()
    {
        return "Download Submissions";
    }
    function getDescription()
    {
        return "Gets a zip file with all the submissions for this assignment in it";
    }
	function getFormHTML()
    {
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td>Include dropped students</td><td>";
        $html .= "<input type='checkbox' name='includedropped' value='includedropped' checked/></td></tr>";
        $html .= "</table>\n";
        return $html;
    }
    /*function getFormHTML()
    {
        return "(None)";
    }
    function hasParams()
    {
        return false;
    }*/
    function executeAndGetResult()
    {
        global $dataMgr;
        $assignment = get_peerreview_assignment();
		if(array_key_exists("includedropped", $_POST)){
            $authors = $assignment->getAuthorSubmissionMap_();
        }else{
            $authors = $assignment->getActiveAuthorSubmissionMap_();
        }
        $userNameMap = $dataMgr->getUserDisplayMap();

        $zip = new zipfile();
        foreach($authors as $author => $submissionID)
        {
            $submission = $assignment->getSubmission($submissionID);
            $zip->addFile($submission->getDownloadContents(), $userNameMap[$author].$submission->getDownloadSuffix());
        }

        $zippedfile = $zip->file();
        $id = $_GET["assignmentid"];
        #header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$assignment->name-id$id-submissions.zip");
        header("Content-Type: application/zip");
        #header("Content-length: " . strlen($zippedfile)+1 . "\n\n");
        #header("Content-Transfer-Encoding: binary");
        // output data to the browser
        echo $zippedfile;
        echo 0;
        exit();

    }
}

