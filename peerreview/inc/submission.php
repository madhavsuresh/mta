<?php

abstract class Submission
{
    public $submissionID;
    public $authorID;
    public $noHallOfFame;
    protected $submissionSettings;

    function __construct(SubmissionSettings $settings, SubmissionID $submissionID = NULL, UserID $authorID = NULL, $noHallOfFame = false)
    {
        $this->submissionID = $submissionID;
        $this->submissionSettings = $settings;
        $this->authorID = $authorID;
        $this->noHallOfFame = $noHallOfFame;
    }

    function loadFromPost($POST)
    {
        $this->noHallOfFame = array_key_exists("nohalloffame", $POST);
        $this->_loadFromPost($POST);
    }

    function getHTML($showHidden=false)
    {
        $html = $this->_getHTML($showHidden);
        if($showHidden)
            $html .= "<h2>Exclude from hall of fame</h2>";
        return $html;
    }

    function getFormHTML()
    {
        $html = $this->_getFormHTML();

        $tmp = '';
        if($this->noHallOfFame)
            $tmp = 'checked';
        $html .= "<input type='checkbox' name='nohalloffame' $tmp />&nbsp;Do not include my submission in the hall of fame<br><br>\n";
        return $html;
    }

    function getValidationCode()
    {
        return $this->_getValidationCode();
    }

    function getFormAttribs() {
        return "";
    }

    abstract function _loadFromPost($POST);
    abstract function _getHTML($showHidden);
    abstract function _getFormHTML();
    function _getValidationCode() { return ""; }
};

abstract class SubmissionSettings
{
    abstract function getFormHTML();
    function getValidationCode() {}
    abstract function loadFromPost($POST);
};

