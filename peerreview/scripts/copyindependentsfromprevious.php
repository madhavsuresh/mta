<?php
require_once("peerreview/inc/common.php");

class CopyIndependentsFromPreviousPeerReviewScript extends Script
{
    function getName()
    {
        return "Copy independents from previous";
    }

    function getDescription()
    {
        return "Copies the set of independent users from the previous assignment";
    }

    function getFormHTML()
    {
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td width='200'>Copy Mode</td><td>";
        $html .= "<select name='mode'><option value='copy'>Copy Both</option><option value='copyIndependents'>Copy Independents</option><option value='copySupervised'>Copy Supervised</option></select></td></tr>\n";
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        //Get all the assignments
        $assignmentHeaders = $dataMgr->getAssignmentHeaders();

        $currentAssignment = get_peerreview_assignment();
        $assignments = $currentAssignment->getAssignmentsBefore(1);

        if(sizeof($assignments) != 1){
            throw new Exception("Could not find exactly one previous assignment!");
        }

        $userNameMap = $dataMgr->getUserDisplayMap();
        $students = $dataMgr->getActiveStudents();
        $currentIndependents = $currentAssignment->getIndependentUsers();
        $previousIndependents = $assignments[0]->getIndependentUsers();
        $independents = array();

        $mode = require_from_post("mode");

        $html = "<table width='100%'>\n";
        $html .= "<tr><td><h2>Student</h2></td><td><h2>Status</h2></td></tr>\n";
        $currentRowType = 0;
        foreach($students as $student)
        {
            $html .= "<tr class='rowType$currentRowType'><td>".$userNameMap[$student->id]."</td><td>";

            $html .= "</td><td>\n";

            $inCurrent = array_key_exists($student->id, $currentIndependents);
            $inPrevious = array_key_exists($student->id, $previousIndependents);
            if($mode == "copy"){
                if($inCurrent != $inPrevious){
                    if($inPrevious){
                        $html .= "Added to independents";
                        $independents[$student->id] = $student;
                    }else{
                        $html .= "Removed from independents";
                    }
                }else if($inPrevious){
                    $independents[$student->id] = $student;
                }
            }else if ($mode == "copyIndependents") {
                if($inPrevious){
                    $independents[$student->id] = $student;
                    if(!$inCurrent){
                        $html .= "Added to independents";
                    }
                }else if($inCurrent){
                    $independents[$student->id] = $student;
                }
            } else { //$mode == "copySupervised"
                if($inPrevious){
                    if($inCurrent){
                        $independents[$student->id] = $student;
                    }
                }else{
                    if($inCurrent){
                        $html .= "Removed from independents";
                    }
                }
            }

            $html .= "</td></tr>\n";
            $currentRowType = ($currentRowType+1)%2;
        }
        $html .= "</table>\n";

        $currentAssignment->saveIndependentUsers($independents);
        return $html;
    }
}

