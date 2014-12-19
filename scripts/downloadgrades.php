<?php
require_once("peerreview/inc/zip.lib.php");
require_once("peerreview/inc/common.php");

class DownloadGradesScript extends Script
{
    function getName()
    {
        return "Download Grades";
    }
    function getDescription()
    {
        return "Gets a csv with all the grades in it";
    }
    function getFormHTML()
    {
        return "(None)";
    }
    function hasParams()
    {
        return false;
    }
    function executeAndGetResult()
    {
        global $dataMgr;
        #$assignment = get_peerreview_assignment();
        #$authors = $assignment->getAuthorSubmissionMap();
        #$userNameMap = $dataMgr->getUserDisplayMap();
        $assignmentNames = array();
        $assignmentGrades = array();
        foreach(array_reverse($dataMgr->getAssignments()) as $assignment)
        {
            $grades = $assignment->getGrades();
            if(!is_null($grades))
            {
                if(sizeof($grades->headers) == 0)
                    throw new Exception("There needs to be at least one header in ".$assignment->name."'s grades");
                $assignmentNames[] = $assignment->name;
                $assignmentGrades[] = $grades;
            }
        }

        //First, we need to pump out the first row
        $csv = ",,";
        for($i = 0; $i < sizeof($assignmentNames); $i++)
        {
            $csv .= ",".$assignmentNames[$i];
            for($j = 1; $j < sizeof($assignmentGrades[$i]->headers); $j++)
            {
                $csv.= ",";
            }
        }
        //Next, pump out the sub header row
        $csv .= "\nFirstName,LastName,StudentID";
        foreach($assignmentGrades as $grades)
        {
            foreach($grades->headers as $header)
            {
                $csv.= "," . $header;
            }
        }

        $students = $dataMgr->getActiveStudents();
        $studentIDs = $dataMgr->getStudentIDMap();
        $displayNames = $dataMgr->getUserDisplayMap2();
        foreach($students as $userID)
        {
            $csv .= "\n".$displayNames[$userID->id]->firstName.",".$displayNames[$userID->id]->lastName.",".$studentIDs[$userID->id];

            foreach($assignmentGrades as $grades)
            {
                if(!array_key_exists($userID->id, $grades->gradesForUsers))
                {
                    //This person has nothing
                    $userGrades = array();
                }
                else
                {
                    $userGrades = $grades->gradesForUsers[$userID->id];
                }
                for($i = 0; $i < sizeof($grades->headers); $i++)
                {
                    $csv.= ",";
                    if(isset($userGrades[$i]))
                        $csv .= $userGrades[$i];
                }
            }
        }

        header("Content-Disposition: attachment; filename=MTAGrades.csv");
        header("Content-Type: text/csv");
        echo $csv;
        exit();
    }
}

