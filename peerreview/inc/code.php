<?php
require_once(dirname(__FILE__)."/submission.php");

class CodeSubmission extends Submission
{
    public $code= "";

    function _loadFromPost($POST)
    {
        if(!array_key_exists("code", $POST))
            throw new Exception("Missing code in POST");

        //TODO: Check this for possible exploits?
        $this->code = $POST["code"];

    }

    function _getHTML($showHidden)
    {
        global $page_scripts;
        $script = "https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js";
        if(strlen($this->submissionSettings->language)){
            $script .= "?lang=".$this->submissionSettings->language;
        }
        $page_scripts[] = $script;
        $html = "";
        $lang = "";
        if(strlen($this->submissionSettings->language)){
            $lang = "lang-".$this->submissionSettings->language;
        }
        $html .= "<pre class='prettyprint $lang linenums'>\n";
        $html .= $this->code;
        $html .= "</pre>";

        return $html;
    }

    function _getFormHTML()
    {
        $html = "";

        $html .= "<textarea name='code' cols='60' rows='40' id='codeEdit' accept-charset='utf-8'>\n";
        //TODO: Do we need to un-purify it? htmlentities($this->text, ENT_COMPAT|ENT_HTML401,'UTF-8');
        $html .= $this->code;
        $html .= "</textarea><br>\n";
        return $html;
    }

    function getDownloadContents()
    {
        return $this->code;
    }

    function getDownloadSuffix()
    {
        return ".txt";
    }

};

class CodeSubmissionSettings extends SubmissionSettings
{
    public $language = "";

    function getFormHTML()
    {
        $html  = "<table width='100%' align='left'>\n";
        $html .= "<tr><td width='190px'>Language</td><td><input type='text' name='codeLanguage' value='$this->language'/></td></tr>\n";
        $html .= "<tr><td colspan='2'>Leave blank if you want automatic detection, otherwise look <a href='https://code.google.com/p/google-code-prettify/'>here</a> for supported languages</td></tr>\n";
        $html .= "</table>\n";
        return $html;
    }

    function loadFromPost($POST)
    {
        //We need to figure out the topics
        if(!array_key_exists("codeLanguage", $POST))
            throw new Exception("Failed to get the language from POST");
        $this->language = $POST["codeLanguage"];
    }
};

class CodePDOPeerReviewSubmissionHelper extends PDOPeerReviewSubmissionHelper
{
    function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment)
    {
        //Delete any old topics, and just write in the new ones
        $sh = $this->prepareQuery("saveCodeAssignmentSubmissionSettingsQuery", "INSERT INTO peer_review_assignment_code_settings (assignmentID, codeLanguage) VALUES (?, ?) ON DUPLICATE KEY UPDATE codeLanguage = ?;");
        $sh->execute(array($assignment->assignmentID, $assignment->submissionSettings->language, $assignment->submissionSettings->language));
    }

    function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("loadCodeAssignmentSubmissionSettingsQuery", "SELECT codeLanguage FROM peer_review_assignment_code_settings WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));
        $res = $sh->fetch();
        $assignment->submissionSettings = new CodeSubmissionSettings();
        if($res->codeLanguage){
            $assignment->submissionSettings->language = $res->codeLanguage;
        }
    }

    function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $code = new CodeSubmission($assignment->submissionSettings);
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


