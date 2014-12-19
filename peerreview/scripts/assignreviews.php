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
        $html = "";
        if(sizeof($assignment->getReviewerAssignment()))
        {
            $html .= "<h1 style='color:red;'>WARNING: About to overwrite existing review assignments</h1>\n";
            $html .= "If students have already started to submit answers, it is likely that you will delete them by running this script<br><br><br>\n";
        }
        $html .= "<table width='100%'>\n";
        $html .= "<tr><td width='300'>Window size to judge reviewer quality</td><td>";
        $html .= "<input type='text' name='windowsize' id='windowsize' value='4' size='10'/></td></tr>\n";
        $html .= "<tr><td>Num. Reviews to assign</td><td>";
        $html .= "<input type='text' name='numreviews' id='numreviews' value='$assignment->defaultNumberOfReviews' size='10'/></td></tr>";
        $html .= "<tr><td>Max Assignment Attempts</td><td>";
        $html .= "<input type='text' name='maxattempts' id='maxattempts' value='20' size='10'/></td></tr>";
        $html .= "<tr><td>Score Noise</td><td>";
        $html .= "<input type='text' name='scorenoise' id='scorenoise' value='0.01' size='10'/></td></tr>";
        $html .= "<tr><td>Seed</td><td>";
        $html .= "<input type='text' name='seed' id='seed' value='$assignment->submissionStartDate' size='30'/></td></tr>";
        $html .= "<tr><td>Number of covert reviews to assign</td><td>";
        $html .= "<input type='text' name='numCovertCalibrations' id='numCovertCalibrations' value='0' size='10'/></td></tr>";
		$html .= "<tr><td>When covert reviews are exhausted</td><td>";
		$html .= "<input type='radio' name='exhaustedCondition' id='exhaustedCondition' value='peerreview' checked='checked'>Assign peer review if available<br>";
		$html .= "<input type='radio' name='exhaustedCondition' id='exhaustedCondition' value='error'>Stop and report error";
		$html .= "</td></tr>";
		$html .= "</table>\n";
        return $html;
    }
    function executeAndGetResult()
    {
        global $dataMgr;

        $currentAssignment = get_peerreview_assignment();

        $windowSize = require_from_post("windowsize");
        $this->numReviews = require_from_post("numreviews");
        $this->scoreNoise = require_from_post("scorenoise");
        $this->maxAttempts = require_from_post("maxattempts");
        $this->seed = require_from_post("seed");
        $this->numCovertCalibrations = require_from_post("numCovertCalibrations");
		if($this->numCovertCalibrations > 0)
        	$this->exhaustedCondition = require_from_post("exhaustedCondition");
        $this->scoreMap = array();

        $assignments = $currentAssignment->getAssignmentsBefore($windowSize);
        $userNameMap = $dataMgr->getUserDisplayMap();
		$authors = $currentAssignment->getAuthorSubmissionMap();
        $activeAuthors = $currentAssignment->getActiveAuthorSubmissionMap_();
        $assignmentIndependent = $currentAssignment->getIndependentUsers();
		
		if($this->numCoverCalibrations > sizeof($currentAssignment->getCalibrationSubmissionIDs()))//Check that there are at least as many calibration submissions as covert reviews to be assigned
				throw new Exception("There are more covert calibrations requested for each independent student than there are available calibration submissions");
		
        //First delete old covert calibration reviews
		foreach($currentAssignment->getStudentToCovertReviewsMap() as $student => $covertReviews)
		{
			foreach($covertReviews as $matchID)
			{
				$currentAssignment->removeMatch(new MatchID($matchID));
			}
		}
		
        $independents = array();
        $supervised = array();
        foreach($activeAuthors as $author => $essayID)
        {
            $score = compute_peer_review_score_for_assignments(new UserID($author), $assignments);

            if(array_key_exists($author, $assignmentIndependent))
                $independents[$author] = $score;
            else
                $supervised[$author] = $score;
            $this->scoreMap[$author] = $score;
        }

        $html = "";
        $reviewerAssignment = array();
        
        # If the independent pool is too small, we move all of its users into the supervised pool.
        # If the supervised pool is too small, then we move just enough independent users into the supervised pool.
        if((count($independents) <= $this->numReviews && count($independents) > 0) ||
           (count($supervised) <= ($this->numReviews + $this->numCovertCalibrations) && count($supervised) > 0))
        {
          $numIndep = count($independents);
          $keys = array_keys($independents);
          mt_shuffle($keys);

          foreach($keys as $idx => $author)
          {
            $supervised[$author] = $independents[$author];
            unset($independents[$author]);

            if((count($independents) == 0 || count($independents) > $this->numReviews) &&
               (count($supervised) == 0 || count($supervised) > ($this->numReviews + $this->numCovertCalibrations))) {
              break;
            }
          }
          $html .= "<p><b style='color:red'>Warning: Topped up supervised pool with ".($numIndep-count($independents))." independent students.</b>";
        }
		
        $independentAssignment = $this->getReviewAssignment($independents, $this->numReviews);
        $supervisedAssignment = $this->getReviewAssignment($supervised, $this->numReviews + $this->numCovertCalibrations);
		
		$covertAssignment = array();
		for($j = 0; $j < $this->numCovertCalibrations; $j++)
		{
			foreach($independents as $independent => $_)
			{
				$newSubmissionID = $currentAssignment->getNewCalibrationSubmissionForUser(new UserID($independent));
				if($newSubmissionID)
				{
					$submission = $currentAssignment->getSubmission($newSubmissionID);
					$authorID = $submission->authorID;
					if(!array_key_exists($authorID->id, $covertAssignment))
						$covertAssignment[$authorID->id] = array();
					$covertAssignment[$authorID->id][] = $independent;
				}
				else
				{
					if($this->exhaustedCondition == 'peerreview')
					{
						//TODO: Fix this algorithm. Doesn't work for case where the candidate(s) with the fewest reviewers are already reviewed by the current independent. Although unlikely
						$reviewersForEach = array_map(function($item){ return sizeof($item); }, $independentAssignment);
						$minimum_reviewers = min($reviewersForEach);
						$candidates = array_filter($independentAssignment, function($x) use ($minimum_reviewers){return (sizeof($x) == $minimum_reviewers); });
						$candidates = array_keys($candidates);
						shuffle($candidates);
						foreach($candidates as $candidate)
						{
							if(!in_array($independent, $independentAssignment[$candidate]) && $candidate != $independent)
							{
								$independentAssignment[$candidate][] = $independent;
								break;
							}
						}
					}
					else
						throw new Exception("Some independent student(s) has exhausted all calibration reviews and thus cannot be assigned a covert peer review.");
				}
			}
		}
		
        //Build the HTML for this

        $html .= "<h2>Independent</h2>\n";
        $html .= $this->getTableForAssignment($independentAssignment, $independents);
        $html .= "<h2>Supervised</h2>\n";
        $html .= $this->getTableForAssignment($supervisedAssignment, $supervised);

        foreach($covertAssignment as $author => $reviewers)
            $reviewerAssignment[$authors[$author]->id] = $reviewers;
        foreach($independentAssignment as $author => $reviewers)
            $reviewerAssignment[$authors[$author]->id] = $reviewers;	
        foreach($supervisedAssignment as $author => $reviewers)
            $reviewerAssignment[$authors[$author]->id] = $reviewers;
		
        $currentAssignment->saveReviewerAssignment($reviewerAssignment);
		
		if($this->numCovertCalibrations > 0)
		{
        	$studentToCovertReviewsMap = $currentAssignment->getStudentToCovertReviewsMap();
			
	        $html .= "<h2>Covert Reviews</h2>";
			$html .= "<table>";
			foreach($studentToCovertReviewsMap as $reviewer => $covertReviews)
			{
	        	$html .= "<tr><td>".$userNameMap[$reviewer]."</td><td><ul style='list-style-type: none;'>";
				foreach($covertReviews as $covertMatch)
				{
					$submission = $currentAssignment->getSubmission(new MatchID ($covertMatch));
					$html .= "<li>".$userNameMap[$submission->authorID->id]."</li>";
				}
				$html .= "</ul></td></tr>";
			}
			$html .= "</table>";
		}
		
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

    private function getReviewAssignment($students, $numReviews)
    {
        mt_srand($this->seed);
        #print_r($students);
        for($i = 0; $i < $this->maxAttempts; $i++)
        {
            try {
                $res = $this->_getReviewAssignment($students, $numReviews);
                return $res;
            }catch(Exception $e){
                //They didn't get it
            }
        }
        throw new Exception("Could not get a reviewer assignment - try increasing the number of attempts or the score noise. If that fails, play with your seeds and hope for the best.");
    }

    private function _getReviewAssignment($students, $numReviews)
    {
        //First, we need to build up our array of student/scores, such that we get a total ordering
        $reviewers = array();
        $randMax = mt_getrandmax();
		
        foreach($students as $student => $score)
        {
            for($i = 0; $i < $numReviews; $i++)
            {
                $obj = new stdClass;
                $obj->student = $student;
                $offset = 0;
                if($i)
                    $offset = pow(10, $i-1);
                $noise = (mt_rand()*1.0/$randMax * 2 - 1)*$this->scoreNoise;

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
        for($i = 0; $i < $numReviews; $i++)
        {
            foreach($assignment as $student => &$assigned)
            {
                $assigned[] = $this->popNextReviewer($student, $assigned, $reviewers);
            }
            //Reallocate the order of the assignment by the sum of reviewer scores
            uasort($assignment, array($this, "compareReviewerScores"));
		}
		
        return $assignment;
    }

    private function compareReviewerScores($a, $b)
    {
        return $this->getReviewerScores($a) > $this->getReviewerScores($b);
    }

    private function getReviewerScores($array)
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
