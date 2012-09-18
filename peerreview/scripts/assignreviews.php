<?php
require_once("peerreview/inc/common.php");

class AssignReviewsPeerReviewScript extends Script
{
    function getName()
    {
        return "Assign Reviews";
    }
    function getDescription()
    {
        return "Updates the reviewer assignment to assign reviewers to papers, keeping independents with one another";
    }
    function getFormHTML()
    {
        //TODO: Load the defaults from the config
        $assignment = get_peerreview_assignment();
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td width='300'>Window size to judge reviewer quality</td><td>";
        $html .= "<input type='text' name='windowsize' id='windowsize' value='4' size='10'/></td></tr>\n";
        $html .= "<tr><td>Num. Reviews to assign</td><td>";
        $html .= "<input type='text' name='numreviews' id='numreviews' value='3' size='10'/></td></tr>";
        $html .= "<tr><td>Max Assignment Attempts</td><td>";
        $html .= "<input type='text' name='maxattempts' id='maxattempts' value='20' size='10'/></td></tr>";
        $html .= "<tr><td>Score Noise</td><td>";
        $html .= "<input type='text' name='scorenoise' id='scorenoise' value='0.01' size='10'/></td></tr>";
        $html .= "<tr><td>Seed</td><td>";
        $html .= "<input type='text' name='seed' id='seed' value='$assignment->submissionStartDate' size='30'/></td></tr>";
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
        $this->numReviews = require_from_post("numreviews");
        $this->scoreNoise = require_from_post("scorenoise");
        $this->maxAttempts = require_from_post("maxattempts");
        $this->seed = require_from_post("seed");
        $this->scoreMap = array();

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
        $authors = $currentAssignment->getAuthorSubmissionMap();
        $assignmentIndependent = $currentAssignment->getIndependentUsers();

        $independents = array();
        $supervised = array();
        foreach($authors as $author => $essayID)
        {
            $scores = array();
            for($i = 0; $i < sizeof($assignments) && $i < $windowSize; $i++)
            {
                $assignment = $assignments[$i];
                foreach($assignment->getAssignedReviews(new UserID($author)) as $matchID)
                {
                    $scores[] = $assignment->getReviewMark($matchID)->getScore() * 1.0 / $assignment->maxReviewScore;
                }
            }
            if(sizeof($scores))
                $score = array_reduce($scores, function($a, $b) { return $a+$b; }) * 1.0 / sizeof($scores);
            else
                $score = 0;

            if(array_key_exists($author, $assignmentIndependent))
                $independents[$author] = $score;
            else
                $supervised[$author] = $score;
            $this->scoreMap[$author] = $score;
        }

        $reviewerAssignment = array();

        $independentAssignment = $this->getReviewAssignment($independents);
        $supervisedAssignment = $this->getReviewAssignment($supervised);

        //Build the HTML for this
        $html  = "<h2>Independent</h2>\n";
        $html .= $this->getTableForAssignment($independentAssignment, $independents);
        $html .= "<h2>Supervised</h2>\n";
        $html .= $this->getTableForAssignment($supervisedAssignment, $supervised);



        foreach($independentAssignment as $author => $reviewers)
            $reviewerAssignment[$authors[$author]->id] = $reviewers;
        foreach($supervisedAssignment as $author => $reviewers)
            $reviewerAssignment[$authors[$author]->id] = $reviewers;
        $currentAssignment->saveReviewerAssignment($reviewerAssignment);

        return $html;
    }

    private function getTableForAssignment($assignment, $scoreMap)
    {
        global $dataMgr;
        $nameMap = $dataMgr->getUserDisplayMap();
        $html = "<table width='100%'>\n";
        foreach($assignment as $author => $reviewers)
        {
            $html .= "<tr><td>".$nameMap[$author]." (".precisionFloat($this->getReviewerScores($reviewers) *1.0/ sizeof($reviewers)).")</td>";
            foreach($reviewers as $reviewer)
            {
                $html .= "<td>".$nameMap[$reviewer]." (".precisionFloat($scoreMap[$reviewer]).")</td>";
            }
            $html .= "</tr>\n";
        }

        $html .= "</table>\n";
        return $html;
    }

    private function getReviewAssignment($students)
    {
        mt_srand($this->seed);
        for($i = 0; $i < $this->maxAttempts; $i++)
        {
            try {
                return $this->_getReviewAssignment($students);
            }catch(Exception $e){
                //They didn't get it
            }
        }
        throw new Exception("Could not get a reviewer assignment - try increasing the number of attempts or the score noise. If that fails, play with your seeds and hope for the best.");
    }

    private function _getReviewAssignment($students)
    {
        //First, we need to build up our array of student/scores, such that we get a total ordering
        $reviewers = array();
        $randMax = mt_getrandmax();
        foreach($students as $student => $score)
        {
            for($i = 0; $i < $this->numReviews; $i++)
            {
                $obj = new stdClass;
                $obj->student = $student;
                $offset = 0;
                if($i)
                    $offset = pow(10, $i-1);
                $noise = (mt_rand()*1.0/$randMax * 2 - 1)*0.01;//$this->scoreNoise;
                $obj->score = max(0, min(1, ($score + $noise))) * pow(10, $i) + $offset;
                $reviewers[] = $obj;
            }
        }
        //Now, we need to sort these guys, so that good reviewers are at the top
        usort($reviewers, function($a, $b) { if( abs($a->score - $b->score) < 0.00001) { return $a->student < $b->student; } else { return $a->score < $b->score; } } );

        //Assemble the empty assignment
        $assignment = array();

        foreach($students as $student => $score)
        {
            $assignment[$student] = array();
        }
        shuffle_assoc($assignment);

        //Now start putting stuff in
        for($i = 0; $i < $this->numReviews; $i++)
        {
            foreach($assignment as $student => &$assigned)
            {
                $assigned[] = $this->popNextReviewer($student, $assigned, $reviewers);
            }
            //Reallocate the order of the assignment by the sum of reviewer scores
            uasort($assignment, function($a, $b) { return $this->getReviewerScores($a) > $this->getReviewerScores($b); });
        }
        return $assignment;
    }

    function getReviewerScores($array)
    {
        $score = 0;
        foreach($array as $reviewer)
        {
            $score += $this->scoreMap[$reviewer];
        }
        return $score;
    }

    private function popNextReviewer($author, $assigned, &$reviewers)
    {
        foreach($reviewers as $index => $obj)
        {
            if($obj->student != $author && !in_array($obj->student, $assigned))
            {
                unset($reviewers[$index]);
                return $obj->student;
            }
        }
        throw new Exception("Failed to find a valid author");
    }
}
