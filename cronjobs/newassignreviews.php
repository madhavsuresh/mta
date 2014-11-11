<?php
require_once("peerreview/inc/common.php");

class AssignReviewsPeerReviewCronJob
{
    function executeAndGetResult(AssignmentID $assignmentID, PDODataManager $globalDataMgr)
    {
    	try{
	    	//First check if the job has already been done
			//if($globalDataMgr->isJobDone($assignmentID, 'assignreviews'))
				//return;
			
			$configuration = $globalDataMgr->getCourseConfiguration($assignmentID);
				
	        $currentAssignment = $globalDataMgr->getAssignment($assignmentID);
			
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
			
	        /*$independents = array();
	        $supervised = array();
			$numStudents = rand(50,100);
			$students = array();
			for($s = 0; $s < $numStudents; $s++)
			{
				$score = rand(500,1000)/1000;
				if($score > 0.8)
					$independents[$s] = $score;
				else {
					$chance = rand(0,10);
					if($chance < 3)
						$independents[$s] = $score;
					else 
						$supervised[$s] = $score;
				}
			}*/
			
			$supervised = array ( "3" => 0.515 , "14" => 0.768 , "19" => 0.705 , "26" => 0.527 , "30" => 0.681 , "34" => 0.632 , "37" => 0.555 , "40" => 0.725 , "49" => 0.602 ); 
			$independents = array ( "1" => 0.573 , "12" => 0.774 , "16" => 0.7 , "19" => 0.574 , "21" => 0.739 , "23" => 0.601 , "25" => 0.546 , "27" => 0.775 , "28" => 0.624 , "30" => 0.657 , "34" => 0.769 , "36" => 0.699 , "37" => 0.75 , "38" => 0.515 , "41" => 0.564 , "42" => 0.784 , "44" => 0.712 , "53" => 0.55 , "59" => 0.666 ); 
	
	        $html = "";
			$html .= "Score noise used: ".$this->scoreNoise."<br>";
	        $html .= "Max attempts used: ".$this->maxAttempts."<br>";
	        $html .= "Number of covert calibrations assigned: ".$this->numCovertCalibrations."<br>";
	        $html .= "Condition for exhausted condition used: ".$this->exhaustedCondition."<br>";
	        
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
	        $html .= $this->getTableForAssignment($independentAssignment, $independents, $userNameMap);
	        $html .= "<h2>Supervised</h2>\n";
	        $html .= $this->getTableForAssignment($supervisedAssignment, $supervised, $userNameMap);
	
	        foreach($covertAssignment as $author => $reviewers)
	            $reviewerAssignment[$authors[$author]->id] = $reviewers;
	        foreach($independentAssignment as $author => $reviewers)
	            $reviewerAssignment[$authors[$author]->id] = $reviewers;	
	        foreach($supervisedAssignment as $author => $reviewers)
	            $reviewerAssignment[$authors[$author]->id] = $reviewers;
			
	        /*$currentAssignment->saveReviewerAssignment($reviewerAssignment);
			
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
				$summary .= "For ".sizeof($independents)." in the independents group: ".sizeof($independents)." have ".$this->numReviews." peer reviews, ";
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
				$summary .= "<br>For " . sizeof($supervised) . " in the supervised group: " . sizeof($supervised) . " have " . ($this->numReviews + $this->numCovertCalibrations) . " peer reviews";
			//End of summary*/
			$result = new stdClass;
			$result->success = 1;
			$result->details = $html;
			return $result;
		}catch(Exception $exception){
			$result = new stdClass;
			$result->success = 0;
			$result->details = cleanString($exception->getMessage());
			return $result;
		}	
    }


    private function getTableForAssignment($assignment, $scoreMap, $nameMap)
    {
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
		//print_r($students);
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

		$neworder = array();
		$vanderCorput = array(); $i = 0; $j = 0; $limit = 1; $k = 0;
		for($a = 0; $a < sizeof($reviewers) + 1; $a++)
		{
			$vanderCorput[] = $i * pow(10, $k) + $j * pow(10, $k-1); 
			$i++;
			if($i == $limit){ $i = 0; $j++;} 
			if($j == 10){ $i = 0; $limit *= 10; $j = 1; $k--;}
		}
		$i = 1;
		foreach($reviewers as $index => $obj)
		{
			$obj->var = $vanderCorput[$i];  
			$neworder[] = $obj;
			$i++;
		}

		usort($neworder, function($a, $b) {if ($a->var == $b->var) return 0; return ($a->var < $b->var) ? -1 : 1;});

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
                $assigned[] = $this->popNextReviewer($student, $assigned, $neworder);
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
