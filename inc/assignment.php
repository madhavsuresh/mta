<?php

require_once("inc/assignmentgrades.php");

abstract class Assignment
{
    public $assignmentID;
    public $name;
    public $assignmentType;
    public $password = NULL;
    public $passwordMessage = NULL;
    public $visibleToStudents = true;
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

        if(array_key_exists("assignmentUsePassword", $POST))
        {
            $this->password = require_from_array("assignmentPassword", $POST);
            $this->passwordMessage = require_from_array("assignmentPasswordMessage", $POST);
        }
        else
        {
            $this->password = null;
            $this->passwordMessage = null;
        }
        $this->visibleToStudents = isset_bool($POST["visibleToStudents"]);

        //Pass it off to the subclass
        $this->_loadFromPost($POST);
    }

    function getFormHTML()
    {
        $html  = "<h2>General Settings</h2>";
        $html .= "<table align='left' width='100%'>\n";
        $html .= "<tr><td>Assignment&nbsp;Name</td><td><input type='text' name='assignmentName' id='assignmentName' value='".htmlentities($this->name, ENT_COMPAT|ENT_QUOTES)."' size='60'/></td></tr>\n";
        $tmp = "";
        if($this->visibleToStudents)
            $tmp = "checked";
        $html .= "<tr><td>Visible&nbsp;to&nbsp;students</td><td><input type='checkbox' name='visibleToStudents' id='visibleToStudents' $tmp /></td></tr>\n";
        $tmp = "";
        if($this->password)
            $tmp = "checked";
        $html .= "<tr><td>Require&nbsp;Password</td><td><input type='checkbox' name='assignmentUsePassword' id='assignmentUsePassword' $tmp /></td></tr>\n";
        $html .= "</table>\n";
        $html .= "<div id='assignmentPasswordDiv'>";
        $html .= "<table align='left' width='100%'>\n";
        $html .= "<tr><td>Password</td><td><input type='text' name='assignmentPassword' id='assignmentPassword' value='".htmlentities($this->password, ENT_COMPAT|ENT_QUOTES)."' size='60'/></td></tr>\n";
        $html .= "<tr><td>Password Message</td><td></td></tr>\n";
        $html .= "<tr><td colspan='2'>";
        $html .= "<textarea name='assignmentPasswordMessage' cols='60' rows='40' class='mceEditor'>\n";
        $html .= htmlentities($this->passwordMessage, ENT_COMPAT|ENT_HTML401,'UTF-8');
        $html .= "</textarea><br>\n";
        $html .= "</table>\n";
        $html .= "</div>";
        $html .= "<h2>".$this->getAssignmentTypeDisplayName()." Settings</h2>";

        $html .= $this->_getFormHTML();

        return $html;
    }

    function getFormScripts()
    {
        $code  = "<script type='text/javascript'> $(document).ready(function(){\n";
        if(!$this->password)
            $code .= "$('#assignmentPasswordDiv').css('display','none');\n";
        $code .= "$('#assignmentUsePassword').click(function(){";
        $code .= "if ($('#assignmentUsePassword').is(':checked')) {";
        $code .= "   $('#assignmentPasswordDiv').show();";
        $code .= "} else {";
        $code .= "   $('#assignmentPasswordDiv').hide();";
        $code .= "} });";
        $code .= "});</script>\n";
        $code .= $this->_getFormScripts();
        return $code;
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
        $duplicate->visibleToStudents = $this->visibleToStudents;
        return $duplicate;
    }

    function getHeaderHTML(UserID $userID)
    {
        global $dataMgr;
        if($this->password && $dataMgr->isStudent($userID) && !$dataMgr->hasEnteredPassword($this->assignmentID, $userID))
        {
            //Show a link to the password button
            $html = $this->getPasswordLockedHTML();;
            $html .= "<a href='".get_redirect_url("enterpassword.php?assignmentid=$this->assignmentID")."'>Enter Password</a><br><br>";
            return $html;
        }
        return $this->_getHeaderHTML($userID);
    }
    
    function showForUser(UserID $userid)
    {
        global $dataMgr;
        if($dataMgr->isStudent($userid) && !$this->visibleToStudents)
            return false;
        return $this->_showForUser($userid);
    }

    function getPasswordLockedHTML() { }

    function _getValidationCode() { return NULL; }
    function _getFormScripts() { return null; }
    function _getFormHTML() { return null; }

    function finalizeDuplicateFromBase(Assignment $baseAssignment) {}

    function getGrades() { return null; }

    abstract function _getHeaderHTML(UserID $userid);
    abstract protected function _duplicate();
    abstract protected function _loadFromPost($POST);
    abstract function getAssignmentTypeDisplayName();

    /** Determines if we should show this assignment to the specified user */
    abstract public function _showForUser(UserID $userid);
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

