<?php
require_once(dirname(__FILE__)."/submission.php");

class CodeSubmission extends Submission
{
    public $code= "";

    function _loadFromPost($POST)
    {
        if(!array_key_exists("codeMode", $POST))
            throw new Exception("Missing codeMode in POST");

        if($POST["codeMode"] == "paste"){
            if(!array_key_exists("code", $POST))
                throw new Exception("Missing code in POST");

            //The code is stored raw, we have to escape it every time we display it
            $this->code = $POST["code"];
        }else{
            //There better be a file
            global $_FILES;
            if ($_FILES["codefile"]["error"] > 0)
                throw new Exception("File upload error: " . $_FILES["codefile"]["error"]);;
            
            //Try and get the image data
            $this->code = file_get_contents($_FILES["codefile"]["tmp_name"]);
        }
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
        $lang = "";
        if($this->submissionSettings->uploadOnly) {
          if(strlen($this->code)) {
            $html .= "<p>Code has been uploaded.</p>";
          }
          else {
            $html .= "<p>No code has been uploaded yet.</p>";
          }
        }
        else {
          if(strlen($this->submissionSettings->language)){
            $lang = "lang-".$this->submissionSettings->language;
          }
          $html .= "<pre class='prettyprint $lang linenums'>\n";
          $html .= htmlentities($this->code, ENT_COMPAT|ENT_HTML401,'UTF-8');
          $html .= "</pre>";
        }
        $html .= "<a href=".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=1")."'>Download</a><br>";

        return $html;
    }
    
    function _dumpRaw($forceDownload, $dumpHeaders)
    {
        if($dumpHeaders){
            header('Content-Type: text/plain');
        }
        if($forceDownload)
            header("Content-Disposition: attachment; filename=$this->submissionID.".$this->submissionSettings->extension);

        echo $this->code;
    }

    function _getFormHTML()
    {
        $html = "";
        $displayUpload="";
        if($this->submissionSettings->uploadOnly) {
          $html .= "<input type='hidden' name='_codeMode' id='codeMode' value='upload'>";
          $displayUpload = "show";
        }
        else {
          $html .= "Submission Mode: <select name='_codeMode' id='codeMode' onChange=\"if(document.getElementById('codeMode').selectedIndex == 0) { $(codeFileDiv).hide(); $(codeEdit).show(); } else { $(codeFileDiv).show(); $(codeEdit).hide();}\"><option value='paste' selected>Paste into browser</option><option value='upload'>Upload File</option></select><br>\n";
          $html .= "<textarea name='code' cols='60' rows='40' id='codeEdit' accept-charset='utf-8'>\n";
          $html .= htmlentities($this->code, ENT_COMPAT|ENT_HTML401,'UTF-8');
          $html .= "</textarea><br>\n";
          $displayUpload = "none";
        }
        $html .= "<input type='hidden' name='codeMode' id='hiddenCodeMode'>";
        $html .= "<div id='codeFileDiv' style='display:".$displayUpload.";'>";
        $html .= "Code File: <input type='file' name='codefile' id='codeFile'/><br><br>";
        if(strlen($this->code)) {
          $html .= "<a href=".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=1")."'>Download</a><br>";
        }
        $html .= "<div class=errorMsg><div class='errorField' id='error_file'></div></div><br>\n";
        $html .= "</div>\n";

        return $html;
    }
    
    function _getValidationCode()
    {
        //only if we have topics do we need to ensure that one has been picked
        $code  = "if($(codeMode).val() == 'upload'){\n";
        $code .= "$('#error_file').html('').parent().hide();\n";
        $code .= "if(!$('#codeFile').val()) {";
        $code .= "$('#error_file').html('You must select a code file');\n";
        $code .= "$('#error_file').parent().show();\n";
        $code .= "error = true;}\n";
        $code .= "else {";
        if($this->submissionSettings->extension){
            $code .= "if(!stringEndsWith($('#codeFile').val().toLowerCase(), '.".$this->submissionSettings->extension."')) {";
            $code .= "$('#error_file').html('Your file must have a .".$this->submissionSettings->extension." extension');\n";
            $code .= "$('#error_file').parent().show();\n";
            $code .= "error = true;}\n";
        }
        $code .= "}";
        $code .= "}";
        $code .= "if(!error) { $(hiddenCodeMode).val($(codeMode).val()); $(codeMode).val('0'); }";
        return $code;
    }

    function getFormAttribs() {
        return "enctype='multipart/form-data' action='api/upload'";
    }

    function getDownloadContents()
    {
        return $this->code;
    }

    function getDownloadSuffix()
    {
        return $this->submissionSettings->extension;
    }

};

class CodeSubmissionSettings extends SubmissionSettings
{
    public $language = "";
    public $extension = "";
    public $uploadOnly = False;
    
    function getFormHTML()
    {
        $html  = "<table width='100%' align='left'>\n";
        $html .= "<tr><td width='190px'>Language</td><td><input type='text' name='codeLanguage' value='$this->language'/></td></tr>\n";
        $html .= "<tr><td colspan='2'>Leave blank if you want automatic detection, otherwise look <a href='https://code.google.com/p/google-code-prettify/'>here</a> for supported languages</td></tr>\n";
        $html .= "<tr><td width='190px'>File extension </td><td><input type='text' name='codeExtension' value='$this->extension'/></td></tr>\n";
        $html .= "<tr><td colspan='2'>Lower case. Leave blank if you want any type of file</td></tr>\n";
        $tmp = "";
        if($this->uploadOnly) { $tmp = "checked"; }
        $html .= "<tr><td width='190px''>Upload only</td><td><input type='checkbox' name='uploadOnly' $tmp/></td></tr>";
        $html .= "<tr><td colspan='2'>Check to disable preview and cut-and-paste.</td></tr>\n";
        $html .= "</table>\n";
        return $html;
    }

    function loadFromPost($POST)
    {
        //We need to figure out the topics
        if(!array_key_exists("codeLanguage", $POST))
            throw new Exception("Failed to get the language from POST");
        if(!array_key_exists("codeExtension", $POST))
            throw new Exception("Failed to get the extension from POST");
        $this->language = $POST["codeLanguage"];
        $this->extension= $POST["codeExtension"];
        $this->uploadOnly = isset_bool($POST['uploadOnly']);
    }
};

class CodePDOPeerReviewSubmissionHelper extends PDOPeerReviewSubmissionHelper
{
    function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment)
    {
        //Delete any old settings, and just write in the new ones
        $sh = $this->prepareQuery("saveCodeAssignmentSubmissionSettingsQuery", "INSERT INTO peer_review_assignment_code_settings (assignmentID, codeLanguage, codeExtension, uploadOnly) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE codeLanguage = ?, codeExtension = ?, uploadOnly = ?;");
        $sh->execute(array($assignment->assignmentID, $assignment->submissionSettings->language, $assignment->submissionSettings->extension, $assignment->submissionSettings->uploadOnly, $assignment->submissionSettings->language, $assignment->submissionSettings->extension, $assignment->submissionSettings->uploadOnly));
    }

    function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("loadCodeAssignmentSubmissionSettingsQuery", "SELECT codeLanguage, codeExtension, uploadOnly FROM peer_review_assignment_code_settings WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));
        $res = $sh->fetch();
        $assignment->submissionSettings = new CodeSubmissionSettings();
        if($res->codeLanguage){
            $assignment->submissionSettings->language = $res->codeLanguage;
        }
        if($res->codeExtension){
            $assignment->submissionSettings->extension = $res->codeExtension;
        }
        if($res->uploadOnly){
            $assignment->submissionSettings->uploadOnly = $res->uploadOnly;
        }
    }

    function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $code = new CodeSubmission($assignment->submissionSettings, $submissionID);
        $sh = $this->prepareQuery("getCodeSubmissionQuery", "SELECT `code` FROM peer_review_assignment_code WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get code submission '$submissionID'");
        $code->code = $res->code;
        return $code;
    }

    function saveAssignmentSubmission(PeerReviewAssignment $assignment, Submission $code, $isNewSubmission)
    {
        $sh = $this->prepareQuery("saveCodeSubmissionQuery", "INSERT INTO peer_review_assignment_code (submissionID, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE code = ?;");
        $sh->execute(array($code->submissionID, $code->code, $code->code));
    }
}


