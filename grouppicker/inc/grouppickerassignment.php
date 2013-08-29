<?php

require_once(dirname(__FILE__)."/../../inc/common.php");
require_once("inc/assignment.php");

class GroupPickerAssignment extends Assignment
{
    public $groups = array();
    public $startDate;
    public $stopDate;

    public $dateFormat = "MMMM Do YYYY, HH:mm";

    function __construct(AssignmentID $id = NULL, $name, AssignmentDataManager $dataMgr)
    {
        parent::__construct($id, $name, $dataMgr);
        global $NOW;
        $this->startDate = $NOW;
        $this->stopDate = $NOW;
    }

    protected function _duplicate()
    {
        global $NOW;
        $obj = clone $this;
        $obj->startDate = $NOW;
        $obj->stopDate = $NOW;
        return $obj;
    }

    protected function _loadFromPost($POST)
    {
        #Validate the essay times
        $this->startDate = intval($POST['startDateSeconds']);
        $this->stopDate = intval($POST['stopDateSeconds']);
        $this->setGroupsFromText($POST['groups']);
    }

    function _getFormHTML()
    {
        $html  = "<table align='left' width='100%'>";
        $html .= "<tr><td>Groups</td><td><textarea name='groups' id='groups' cols='80' rows='20'/>".$this->getGroupText()."</textarea></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "<tr><td>Start&nbsp;Date</td><td><input type='text' name='startDate' id='startDate'/></td></tr>\n";
        $html .= "<tr><td>Stop&nbsp;Date</td><td><input type='text' name='stopDate' id='stopDate'/></td></tr>\n";
        $html .= "</table>";
        $html .= "<input type='hidden' name='startDateSeconds' id='startDateSeconds' />\n";
        $html .= "<input type='hidden' name='stopDateSeconds' id='stopDateSeconds' />\n";
        return $html;
    }

    function _getFormScripts()
    {
        $code  = "<script type='text/javascript'> $('#startDate').datetimepicker({ defaultDate : new Date(".($this->startDate*1000).")}); </script>\n";
        $code .= "<script type='text/javascript'> $('#stopDate').datetimepicker({ defaultDate : new Date(".($this->stopDate*1000).")}); </script>\n";
        $code .= set_element_to_date("startDate", $this->startDate);
        $code .= set_element_to_date("stopDate", $this->stopDate);
        return $code;
    }

    function _getValidationCode()
    {
        $code  = "$('#startDateSeconds').val(moment($('#startDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        $code .= "$('#stopDateSeconds').val(moment($('#stopDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        return $code;
    }

    function _showForUser(UserID $userID)
    {
        global $NOW, $dataMgr;
        //Only show this if it is an instructor or if it is after the start date
        return ($dataMgr->isInstructor($userID) || $NOW > $this->startDate);
    }

    function _getHeaderHTML(UserID $userID)
    {
        global $dataMgr, $NOW;
        if($dataMgr->isInstructor($userID))
        {
            //Show them how many people have been assigned, and give them the option to view the assignment
            $html  = "<table width='100%'>\n";
            $html .= "<td>";
            $html .= "Total Assigned: ".$this->numAssigned()."/".$dataMgr->numStudents()."\n";
            $html .= "</td><td>";
            $html .= "Start: <span id='startDate$this->assignmentID'></span><br>\n";
            $html .= "Stop: <span id='stopDate$this->assignmentID'></span><br>\n";
            $html .= "</td><td>\n";
            $html .= "<a href='".get_redirect_url("grouppicker/viewgroups.php?assignmentid=$this->assignmentID")."'>View Groups</a><br>\n";
            $html .= "<a href='".get_redirect_url("grouppicker/editgroups.php?assignmentid=$this->assignmentID")."'>Edit Groups</a>\n";
            $html .= "</td></tr></table>\n";
            $html .= set_element_to_date("startDate$this->assignmentID", $this->startDate, "html", $this->dateFormat);
            $html .= set_element_to_date("stopDate$this->assignmentID", $this->stopDate, "html", $this->dateFormat);
            return $html;
        }
        else
        {
            //Check to see if they have been assigned
            if($this->stopDate < $NOW && !$this->hasGroupIndex($userID) )
            {
                //We don't have a group/index
                return "Group assignment has finished";
            }
            $index = $this->getGroupIndex($userID);
            if(!isset($this->groups[$index]))
            {
                return "Something odd has happened - contact your TA, as you have been assigned to a group that does not exist!";
            }
            return cleanString($this->groups[$index]);
        }
    }

    function getGroupText()
    {
        $joinGroups = '';
        for($i = 0; $i < sizeof($this->groups); $i++)
        {
            $joinGroups .= $this->groups[$i];
            if($i < sizeof($this->groups)-1)
                $joinGroups .= "\n//\n";
        }
        return $joinGroups;
    }

    function setGroupsFromText($text)
    {
        $this->groups = array();
        $splits = explode("//", $text);
        foreach($splits as $group)
        {
            $this->groups[] = trim($group);
        }
    }

    function getAssignmentTypeDisplayName()
    {
        return "Group Picker";
    }
}

