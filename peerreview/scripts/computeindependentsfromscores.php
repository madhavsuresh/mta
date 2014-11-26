<?php
require_once("peerreview/inc/common.php");

class ComputeIndependentsFromScoresPeerReviewScript extends Script
{
    function getName()
    {
        return "Compute Independents From Scores";
    }
    function getDescription()
    {
        return "Determines which users should be in the independent pool for this assignment as determined by the average of their scores";
    }

    function getFormHTML()
    {
        //TODO: Load the defaults from the config
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td width='200'>Assignment Window Size</td><td>";
        $html .= "<input type='text' name='windowsize' id='windowsize' value='4' size='10'/></td></tr>\n";
        $html .= "<tr><td>Review Score Threshold</td><td>";
        $html .= "<input type='text' name='threshold' id='threshold' value='70' size='10'/>%</td></tr>";
        $html .= "<tr><td>Keep Already Independent</td><td>";
        $html .= "<input type='checkbox' name='keep' value='keep' checked/></td></tr>";
        $html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        //Get all the assignments
        $assignmentHeaders = $dataMgr->getAssignmentHeaders();

        $currentAssignment = get_peerreview_assignment();

        $windowSize = require_from_post("windowsize");
        $independentThreshold = require_from_post("threshold");

        $assignments = $currentAssignment->getAssignmentsBefore($windowSize);
        $userNameMap = $dataMgr->getUserDisplayMap();
        $students = $dataMgr->getActiveStudents();
        if(array_key_exists("keep", $_POST)){
            $independents = $currentAssignment->getIndependentUsers();
        }else{
            $independents = array();
        }

        $html = "<h2>Used Assignments</h2>";
        foreach($assignments as $asn){
            $html .= $asn->name . "<br>";
        }

        $html .= "<table width='100%'>\n";
        $html .= "<tr><td><h2>Student</h2></td><td><h2>Review Avg</h2></td><td><h2>Status</h2></td></tr>\n";
        $currentRowType = 0;
        foreach($students as $student)
        {
            $html .= "<tr class='rowType$currentRowType'><td>".$userNameMap[$student->id]."</td><td>";
            $score = compute_peer_review_score_for_assignments($student, $assignments) * 100;
            $html .= precisionFloat($score);
            $html .= "</td><td>\n";
            if($score >= $independentThreshold && !array_key_exists($student->id, $independents))
            {
                $independents[] = $student;
                $html .= "Independent";
            }
            $html .= "</td></tr>\n";
            $currentRowType = ($currentRowType+1)%2;
        }
        $html .= "</table>\n";

        $currentAssignment->saveIndependentUsers($independents);
        return $html;
    }
}
