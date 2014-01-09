<?php

abstract class Submission
{
    public $submissionID;
    public $authorID;
    public $noPublicUse;
    protected $submissionSettings;

    function __construct(SubmissionSettings $settings, SubmissionID $submissionID = NULL, UserID $authorID = NULL, $noPublicUse = false)
    {
        $this->submissionID = $submissionID;
        $this->submissionSettings = $settings;
        $this->authorID = $authorID;
        $this->noPublicUse = $noPublicUse;
    }

    function loadFromPost($POST)
    {
        $this->noPublicUse = isset_bool($POST["nopublicuse"]);
        $this->_loadFromPost($POST);
    }

    function getHTML($showHidden=false)
    {
        $html = $this->_getHTML($showHidden);
        if($this->noPublicUse)
            $html .= "<h2>Exclude from public use</h2>";
        return $html;
    }

    function getFormHTML()
    {
        $html = $this->_getFormHTML();

        $tmp = '';
        if($this->noPublicUse)
            $tmp = 'checked';
        $html .= "<input type='checkbox' name='nopublicuse' $tmp />&nbsp;Do not use my submission anonymously in public<br><br>\n";
        return $html;
    }

    function getValidationCode()
    {
        return $this->_getValidationCode();
    }

    function getFormAttribs() {
        return "";
    }

    function getDownloadContents()
    {
        return "<html><body>".$this->getHTML()."</body></html>";
    }

    function getDownloadSuffix()
    {
        return ".html";
    }

    abstract function _loadFromPost($POST);
    abstract function _getHTML($showHidden);
    abstract function _getFormHTML();
    function _getValidationCode() { return ""; }
    function _dumpRaw($forceDownload=false, $dumpHeaders=true) { $this->_getHTML(false); }
};

abstract class SubmissionSettings
{
    abstract function getFormHTML();
    function getValidationCode() {}
    abstract function loadFromPost($POST);
};

