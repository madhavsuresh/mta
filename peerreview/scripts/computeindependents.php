<?php
require_once("peerreview/inc/common.php");

class ComputeIndependentsPeerReviewScript extends Script
{
    function getName()
    {
        return "Compute Independents";
    }
    function getDescription()
    {
        return "Determines which users should be in the independent pool for this assignment";
    }

    function getFormHTML()
    {
        //TODO: Load the defaults from the config
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td width='200'>Assignment Window Size</td><td>";
        $html .= "<input type='text' name='windowsize' id='windowsize' value='4' size='10'/></td></tr>\n";
        $html .= "<tr><td>Review Grade Threshold</td><td>";
        $html .= "<input type='text' name='threshold' id='threshold' value='70' size='10'/>%</td></tr>";
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

        $assignments = array();
        foreach($assignmentHeaders as $header)
        {
            if($header->assignmentType == "peerreview")
            {
                $assignment = $dataMgr->getAssignment($header->assignmentID, "peerreview");
                if($assignment->reviewStopDate < $currentAssignment->reviewStartDate)
                    $assignments[] = $assignment;
            }
        }
        //Sort the assignments based on their date
        usort($assignments, function($a, $b) { return $a->reviewStopDate < $b->reviewStopDate; } );

        $userNameMap = $dataMgr->getUserDisplayMap();
        $students = $dataMgr->getStudents();
        $independents = array();

        $html = "<table width='100%'>\n";
        $html .= "<tr><td><h2>Student</h2></td><td><h2>Review Avg</h2></td><td><h2>Status</h2></td></tr>\n";
        $currentRowType = 0;
        foreach($students as $student)
        {
            $html .= "<tr class='rowType$currentRowType'><td>".$userNameMap[$student->id]."</td><td>";
            $scores = array();
            for($i = 0; $i < sizeof($assignments) && $i < $windowSize; $i++)
            {
                $assignment = $assignments[$i];
                foreach($assignment->getAssignedReviews($student) as $matchID)
                {
                    $scores[] = $assignment->getReviewMark($matchID)->getScore() * 1.0 / $assignment->maxReviewScore;
                }
            }
            if(sizeof($scores))
                $score= array_reduce($scores, function($a, $b) { return $a+$b; })*100.0 / sizeof($scores);
            else
                $score = 0;
            $html .= precisionFloat($score);
            $html .= "</td><td>\n";
            if($score >= $independentThreshold)
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
