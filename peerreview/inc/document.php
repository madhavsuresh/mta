<?php
require_once(dirname(__FILE__)."/submission.php");

class DocumentSubmission extends Submission
{
    public $document= "";

    function _loadFromPost($POST)
    {
            //There better be a file
            global $_FILES;
            if ($_FILES["documentfile"]["error"] > 0)
                throw new Exception("File upload error: " . $_FILES["documentfile"]["error"]);;

            //Try and get the image data
            $this->document = file_get_contents($_FILES["documentfile"]["tmp_name"]);
    }

    function _getHTML($showHidden)
    {
        global $page_scripts;
        $script = get_ui_url(false)."prettify/run_prettify.js";
        if(strlen($this->submissionSettings->language)){
            $script .= "?lang=".$this->submissionSettings->language;
        }
        $page_scripts[] = $script;
        $html = "";
        if(strlen($this->document)) {
            $html .= "<p>Document has been uploaded.</p>";
        }
        else {
            $html .= "<p>No document has been uploaded yet.</p>";
        }
        $html .= "<embed width='765' height='500' src='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=0")."'></embed><br>";
        $html .= "<a href='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=1")."'>Download</a><br>";

        return $html;
    }

    function _dumpRaw($forceDownload = false, $dumpHeaders = true)
    {
        if($dumpHeaders){
            header('Content-Type: application/pdf');
        }
        if($forceDownload)
            header("Content-Disposition: attachment; filename=$this->submissionID.".$this->submissionSettings->extension);

        echo $this->document;
    }

    function _getFormHTML()
    {
        $html = "";
        $html .= "<input type='hidden' name='documentMode' id='hiddenDocumentMode'>";
        $html .= "<div id='documentFileDiv'>";
        $html .= "Document File: <input type='file' name='documentfile' id='documentFile'/><br><br>";
        if(strlen($this->document)) {
          $html .= "<embed width='800' height='500' src='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=0")."'></embed><br>";
          $html .= "<a href='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=1")."'>Download</a><br>";
        }
        $html .= "<div class=errorMsg><div class='errorField' id='error_file'></div></div><br>\n";
        $html .= "</div>\n";

        return $html;
    }

    function _getValidationDocument()
    {
        //only if we have topics do we need to ensure that one has been picked
        $document  = "if($(documentMode).val() == 'upload'){\n";
        $document .= "$('#error_file').html('').parent().hide();\n";
        $document .= "if(!$('#documentFile').val()) {";
        $document .= "$('#error_file').html('You must select a document file');\n";
        $document .= "$('#error_file').parent().show();\n";
        $document .= "error = true;}\n";
        $document .= "else {";
        if($this->submissionSettings->extension){
            $document .= "if(!stringEndsWith($('#documentFile').val().toLowerCase(), '.".$this->submissionSettings->extension."')) {";
            $document .= "$('#error_file').html('Your file must have a .".$this->submissionSettings->extension." extension');\n";
            $document .= "$('#error_file').parent().show();\n";
            $document .= "error = true;}\n";
        }
        $document .= "}";
        $document .= "}";
        $document .= "if(!error) { $(hiddenDocumentMode).val($(documentMode).val()); $(documentMode).val('0'); }";
        return $document;
    }

    function getFormAttribs() {
        return "enctype='multipart/form-data' action='api/upload'";
    }

    function getDownloadContents()
    {
        return $this->document;
    }

    function getDownloadSuffix()
    {
        return $this->submissionSettings->extension;
    }

};

class DocumentSubmissionSettings extends SubmissionSettings
{
    public $extension = "";

    function getFormHTML()
    {
        $html  = "<table width='100%' align='left'>\n";
        $html .= "<tr><td width='190px'>File extension </td><td><input type='text' name='documentExtension' value='$this->extension'/></td></tr>\n";
        $html .= "<tr><td colspan='2'>Lower case. Leave blank if you want any type of file</td></tr>\n";
        $html .= "</table>\n";
        return $html;
    }

    function loadFromPost($POST)
    {
        //We need to figure out the topics
        if(!array_key_exists("documentExtension", $POST))
            throw new Exception("Failed to get the extension from POST");
        $this->extension= $POST["documentExtension"];
    }
};

class DocumentPDOPeerReviewSubmissionHelper extends PDOPeerReviewSubmissionHelper
{
    function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment)
    {
        //Delete any old settings, and just write in the new ones
        $sh = $this->prepareQuery("saveDocumentAssignmentSubmissionSettingsQuery", "INSERT INTO peer_review_assignment_document_settings (assignmentID, documentExtension) VALUES (?, ?) ON DUPLICATE KEY UPDATE documentExtension = ?;");
        $sh->execute(array($assignment->assignmentID, $assignment->submissionSettings->extension, $assignment->submissionSettings->extension));
    }

    function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("loadDocumentAssignmentSubmissionSettingsQuery", "SELECT documentExtension FROM peer_review_assignment_document_settings WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));
        $res = $sh->fetch();
        $assignment->submissionSettings = new DocumentSubmissionSettings();
        if($res->documentExtension){
            $assignment->submissionSettings->extension = $res->documentExtension;
        }
    }

    function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $document = new DocumentSubmission($assignment->submissionSettings, $submissionID);
        $sh = $this->prepareQuery("getDocumentSubmissionQuery", "SELECT `document` FROM peer_review_assignment_document WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get document submission '$submissionID'");
        $document->document = $res->document;
        return $document;
    }

    function saveAssignmentSubmission(PeerReviewAssignment $assignment, Submission $document, $isNewSubmission)
    {
        // This is for a DB compatibility issue I ran into. -- Jake Collins
        //$sh = $this->prepareQuery("saveDocumentSubmissionQuery", "REPLACE INTO peer_review_assignment_document (submissionID, `document`) VALUES (?, ?);");
        //$sh->execute(array($document->submissionID, $document->document));

        // This is more standard for this codebase.
        $sh = $this->prepareQuery("saveDocumentSubmissionQuery", "INSERT INTO peer_review_assignment_document (submissionID, `document`) VALUES (?, ?) DUPLICATE KEY UPDATE document = ?;");
        $sh->execute(array($document->submissionID, $document->document, $document->document));
    }
}
