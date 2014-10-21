<?php
require_once("peerreview/inc/common.php");

foreach($recentPeerReviewAssignments as $assignmentID)
{
	try{
		if($globalDataMgr->isJobDone($assignmentID, 'assignreviews'))
			continue;

		$currentAssignment = $globalDataMgr->getAssignment($assignmentID);
		
		$windowSize = 4;//$windowSize = require_from_post("windowsize");
		$numReviews = 2;//$this->numReviews = require_from_post("numreviews");
		$scoreNoise = 0.01;//$this->scoreNoise = require_from_post("scorenoise");
		$maxAttempts = 20;//$this->maxAttempts = require_from_post("maxattempts");
		$seed = $currentAssignment->submissionStartDate;//$this->seed = require_from_post("seed");
		$numCovertCalibrations = 1;//$this->numCovertCalibrations = require_from_post("numCovertCalibrations");
		if($numCovertCalibrations > 0)
			$exhaustedCondition = 'peerreview';//$this->exhaustedCondition = require_from_post("exhaustedCondition");
		$scoreMap = array();//$this->scoreMap = array();

		$assignments = $currentAssignment->getAssignmentsBefore($windowSize);
		$userNameMap = $globalDataMgr->getUserDisplayMapByAssignment($assignmentID);
		$authors = $currentAssignment->getAuthorSubmissionMap();
		$authors_ = $currentAssignment->getAuthorSubmissionMap_();
		$assignmentIndependent = $currentAssignment->getIndependentUsers();

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
		    $scoreMap[$author] = $score;
		}
	
		$html = "";
		$reviewerAssignment = array();
		
		# If the independent pool is too small, we move all of its users into the supervised pool.
		# If the supervised pool is too small, then we move just enough independent users into the supervised pool.
		if((count($independents) <= $numReviews && count($independents) > 0) ||
		   (count($supervised) <= $numReviews && count($supervised) > 0))
		{
		  $numIndep = count($independents);
		  $keys = array_keys($independents);
		  mt_shuffle($keys);
		
		  foreach($keys as $idx => $author)
		  {
		    $supervised[$author] = $independents[$author];
		    unset($independents[$author]);
		
		    if((count($independents) == 0 || count($independents) > $numReviews) &&
		       (count($supervised) == 0 || count($supervised) > $numReviews)) {
		      break;
		    }
		  }
		  $html .= "<p><b style='color:red'>Warning: Topped up supervised pool with ".($numIndep-count($independents))." independent students.</b>";
		}
		
		$independentAssignment = getReviewAssignment($independents, $numReviews);
		$supervisedAssignment = getReviewAssignment($supervised, $numReviews + $numCovertCalibrations);
		
		$covertAssignment = array();
		for($j = 0; $j < $numCovertCalibrations; $j++)
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
					if($exhaustedCondition == 'peerreview')
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
		$html .= getTableForAssignment($independentAssignment, $independents);
		$html .= "<h2>Supervised</h2>\n";
		$html .= getTableForAssignment($supervisedAssignment, $supervised);
		
		foreach($covertAssignment as $author => $reviewers)
		    $reviewerAssignment[$authors[$author]->id] = $reviewers;
		foreach($independentAssignment as $author => $reviewers)
		    $reviewerAssignment[$authors[$author]->id] = $reviewers;	
		foreach($supervisedAssignment as $author => $reviewers)
		    $reviewerAssignment[$authors[$author]->id] = $reviewers;
		
		$currentAssignment->saveReviewerAssignment($reviewerAssignment);
		
		if($numCovertCalibrations > 0)
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

		$summary = "Test";
		
		$globalDataMgr->createNotification($assignmentID, 'assignreviews', 1, $summary, $html);
	}catch(Exception $exception){
		$globalDataMgr->createNotification($assignmentID, 'assignreviews', 0, cleanString($exception->getMessage()), "");
	}

}

function getTableForAssignment($assignment, $scoreMap)
{
    global $globalDataMgr, $assignmentID;
    $nameMap = $globalDataMgr->getUserDisplayMapByAssignment($assignmentID);
    $html = "<table width='100%'>\n";
    foreach($assignment as $author => $reviewers)
    {
        $html .= "<tr><td>".$nameMap[$author]." (".precisionFloat(getReviewerScores($reviewers) *1.0/ sizeof($reviewers)).")</td>";
        foreach($reviewers as $reviewer)
        {
            $html .= "<td>".$nameMap[$reviewer]." (".precisionFloat($scoreMap[$reviewer]).")</td>";
        }
        $html .= "</tr>\n";
    }

    $html .= "</table>\n";
    return $html;
}

function getReviewAssignment($students, $numReviews)
{
	print_r($seed); print_r($maxAttempts); 
    mt_srand($seed);
    for($i = 0; $i < $maxAttempts; $i++)
    {
        try {
            $res = _getReviewAssignment($students, $numReviews);
            return $res;
        }catch(Exception $e){
            //They didn't get it
        }
    }
    throw new Exception("Could not get a reviewer assignment - try increasing the number of attempts or the score noise. If that fails, play with your seeds and hope for the best.");
}

function _getReviewAssignment($students, $numReviews)
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
            $noise = (mt_rand()*1.0/$randMax * 2 - 1)*$scoreNoise;

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
            $assigned[] = popNextReviewer($student, $assigned, $reviewers);
        }
        //Reallocate the order of the assignment by the sum of reviewer scores
        uasort($assignment, "compareReviewerScores");
	}
	
    return $assignment;
}

function compareReviewerScores($a, $b)
{
    return getReviewerScores($a) > getReviewerScores($b);
}

function getReviewerScores($array)
{
	global $scoreMap;
	
    $score = 0;
    foreach($array as $reviewer)
    {
        $score += $scoreMap[$reviewer];
    }
    return $score;
}

function popNextReviewer($author, $assigned, &$reviewers)
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

?>