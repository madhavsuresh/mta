<?php
require_once("peerreview/inc/common.php");

$obj = new AssignReviewsPeerReviewCronScript();

foreach($recentPeerReviewAssignments as $assignmentID)
{
	try{
		if($globalDataMgr->isJobDone($assignmentID, 'assignreviews'))
			continue;
		$obj->executeAndGetResult($assignmentID, $globalDataMgr);
	}catch(Exception $exception){
		$globalDataMgr->createNotification($assignmentID, 'assignreviews', 0, cleanString($exception->getMessage()), "");
	}
}

class AssignReviewsPeerReviewCronScript
{
    function executeAndGetResult(AssignmentID $assignmentID, PDODataManager $globalDataMgr)
    {	
        $currentAssignment = $globalDataMgr->getAssignment($assignmentID);
		
		try{
			$configuration = $globalDataMgr->getCourseConfiguration($assignmentID);
		} catch(Exception $e){
			return;	
		}
		
        $windowSize = $configuration->windowSize;//$windowSize = require_from_post("windowsize");
        if($configuration->numReviews < 0)
		{
			$this->numReviews = $currentAssignment->defaultNumberOfReviews;
		}
		else
		{
        	$this->numReviews = $configuration->numReviews;//$this->numReviews = require_from_post("numreviews");
		}
        $this->scoreNoise = $configuration->scoreNoise;//$this->scoreNoise = require_from_post("scorenoise");
        $this->maxAttempts = $configuration->maxAttempts;//$this->maxAttempts = require_from_post("maxattempts");
        $this->seed = $currentAssignment->submissionStartDate;//$this->seed = require_from_post("seed");
        $this->numCovertCalibrations = $configuration->numCovertCalibrations;//$this->numCovertCalibrations = require_from_post("numCovertCalibrations");
        $this->exhaustedCondition = $configuration->exhaustedCondition;//set in course configuration
        $this->scoreMap = array();

        $assignments = $currentAssignment->getAssignmentsBefore($windowSize);
        $userNameMap = $globalDataMgr->getUserDisplayMapByAssignment($assignmentID);
		$authors = $currentAssignment->getAuthorSubmissionMap();
        $authors_ = $currentAssignment->getAuthorSubmissionMap_();
        $assignmentIndependent = $currentAssignment->getIndependentUsers();
        	
		if($this->numCoverCalibrations >= sizeof($currentAssignment->getCalibrationSubmissionIDs()))//Check that there are at least as many calibration submissions as covert reviews to be assigned
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
        foreach($authors_ as $author => $essayID)
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
		
		//For reporting how many independents got x covert reviews
		$covertReviewsHistogram = array();
		//For reporting how many independents got x extra peer reviews
		$extraPeerReviewsHistogram = array();
		
		$covertAssignment = array();
		foreach($independents as $independent => $_)
		{
			$j = 0;
			$cr = 0;
			$epr = 0;
			while($j < $this->numCovertCalibrations)
			{
				$newSubmissionID = $currentAssignment->getNewCalibrationSubmissionForUser(new UserID($independent));
				if($newSubmissionID)
				{
					$submission = $currentAssignment->getSubmission($newSubmissionID);
					$authorID = $submission->authorID;
					if(!array_key_exists($authorID->id, $covertAssignment))
						$covertAssignment[$authorID->id] = array();
					$covertAssignment[$authorID->id][] = $independent;
					$cr++;
				}
				else
				{
					if($this->exhaustedCondition == 'extrapeerreview')
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
								$epr++;
								break;
							}
						}
					}
					else
						throw new Exception("Some independent student(s) has exhausted all calibration reviews and thus cannot be assigned a covert peer review.");
				}
				$j++;
			}
			$covertReviewsHistogram[$cr]++;
			$extraPeerReviewsHistogram[$epr]++;
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
		
		if($this->numCovertCalibrations > 0 && sizeof($independents) > 0)
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
		
		//For summary
		$summary = "";
		if(sizeof($independents)>0)
		{
			$summary .= "For ".sizeof($independents)." independents: ".sizeof($independents)." have ".$this->numReviews." peer reviews, ";
			if($this->numCovertCalibrations > 0 && sizeof($independents) > 0)
			{	
				$k = 0;
				while($k <= $this->numCovertCalibrations)
				{
					if($covertReviewsHistogram[$k] > 0)
					{
						$summary .= $covertReviewsHistogram[$k] . " have $k covert reviews, ";
					}
					$k++;
				}
				$k = 0;
				while($k <= $this->numCovertCalibrations)
				{
					if($extraPeerReviewsHistogram[$k] > 0)
						$summary .= $extraPeerReviewsHistogram[$k] . " have $k extra peer reviews, ";
					$k++;
				}
			}
		}
		if(sizeof($supervised)>0)
			$summary .= "<br>For " . sizeof($supervised) . " supervised: " . sizeof($supervised) . " have " . ($this->numReviews + $this->numCovertCalibrations) . " peer reviews";
		
		$globalDataMgr->createNotification($assignmentID, 'assignreviews', 1, $summary, $html);
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
