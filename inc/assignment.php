<?php

abstract class Assignment
{
    public $assignmentID;
    public $name;
    public $assignmenType;
    protected $dataMgr;

    function __construct(AssignmentID $assignmentID = NULL, $name, AssignmentDataManager $dataMgr)
    {
        $this->assignmentID = $assignmentID;
        $this->name = $name;
        $this->assignmentType = $dataMgr->assignmentType;
        $this->dataMgr = $dataMgr;
    }

    function loadFromPost($POST)
    {
        //Grab the name
        if(!array_key_exists("assignmentName", $POST))
            throw new Exception("Missing 'assignmentName' in POST");
        $this->name = $POST['assignmentName'];
        //Pass it off to the subclass
        $this->_loadFromPost($POST);
    }

    function getFormHTML()
    {
        $html  = "<h2>General Settings</h2>";
        $html .= "<table align='left' width='100%'>\n";
        $html .= "<tr><td>Assignment&nbsp;Name</td><td><input type='text' name='assignmentName' id='assignmentName' value='".htmlentities($this->name, ENT_COMPAT|ENT_QUOTES)."' size='60'/></td></tr>\n";
        $html .= "</table>\n";
        $html .= "<h2>".$this->getAssignmentTypeDisplayName()." Settings</h2>";

        $html .= $this->_getFormHTML();

        return $html;
    }

    function __call($name, $arguments)
    {
        array_unshift($arguments, $this);
        return call_user_func_array(array($this->dataMgr, $name), $arguments);
    }

    function getValidationCode()
    {
        global $dataMgr;
        $code = "";
        //No validation here... there used to be
        $code .= $this->_getValidationCode();
        return $code;
    }

    function duplicate()
    {
        $duplicate = $this->_duplicate();
        $duplicate->name = NULL;
        $duplicate->assignmentID;
        return $duplicate;
    }

    function _getValidationCode() { return NULL; }
    function _getFormScripts() { return null; }
    function _getFormHTML() { return null; }

    function finalizeDuplicateFromBase(Assignment $baseAssignment) {}

    abstract function getHeaderHTML(UserID $userid);
    abstract protected function _duplicate();
    abstract protected function _loadFromPost($POST);
    abstract function getAssignmentTypeDisplayName();

    /** Determines if we should show this assignment to the specified user */
    abstract public function showForUser(UserID $userid);
};

class AssignmentHeader
{
    function __construct(AssignmentID $assignmentID, $name, $type, $displayPriority)
    {
        $this->assignmentID = $assignmentID;
        $this->name = $name;
        $this->assignmentType = $type;
        $this->displayPriority = $displayPriority;
    }
    public $assignmentID;
    public $name;
    public $assignmentType;
    public $displayPriority;
}

?>
