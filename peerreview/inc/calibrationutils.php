<?php
require_once(dirname(__FILE__)."/common.php");

function generateAutoMark(PeerReviewAssignment $assignment, Review $instructorReview, Review $review)
{
    //get an array of all the differences
    $squarederrors = array(); 
    foreach($assignment->getReviewQuestions() as $question)
    {
        $id = $question->questionID->id;
        $squarederrors[] = pow(abs($question->getScore($instructorReview->answers[$id]) - $question->getScore($review->answers[$id]) ), 2);
    }

    $sum = array_reduce($squarederrors , function($u, $v) { return $u + $v; } );
	
	$meansquarederror = $sum / count($squarederrors);

	/*
    //yay for hard coded crap
    if(max($differences) <= 1 && $sumDiff <= 1){
        $points = 1;
    }else if(max($differences) <= 1 && $sumDiff <= 2){
        $points = 0.5;
    }else if(max($differences) <= 2 && $sumDiff <= 4){
        $points = -0.25;
    }else{
        $points = -1;
    }
	*/

    /* At some point, we should actually honour this stuff
    if(sizeof(array_filter($differences, function($x) use($assignment) { return $x > $assignment->reviewScoreMaxDeviationForGood; })) <= $assignment->reviewScoreMaxCountsForGood && max($differences) <= $assignment->reviewScoreMaxDeviationForGood)
        $points = 1;
    else if(sizeof(array_filter($differences, function($x) use($assignment) { return $x >= $assignment->reviewScoreMaxDeviationForPass; })) <= $assignment->reviewScoreMaxCountsForPass &&
                 max($differences) <= $assignment->reviewScoreMaxDeviationForPass)
        $points = -0.25;
    else
        $points = -1;
    */

    return new ReviewMark(0, null, true, $meansquarederror);
}


function computeReviewPointsForAssignments(UserID $student, $assignments)
{
    $points = array();
    foreach($assignments as $assignment)
    {
        foreach($assignment->getAssignedCalibrationReviews($student) as $matchID)
        {
            $mark = $assignment->getReviewMark($matchID);
            if($mark->isValid)
                $points[$matchID->id] = $mark->getReviewPoints();
        }
    }
    ksort($points);
    return array_reduce($points, function($v, $w) { return max($v+$w, 0); });
}


function computeWeightedAverage($scores)
{
	krsort($scores);
	
	$total = 0;
	$totalweights = 0;
	$i = 0;
	
    foreach($scores as $score)
    {
    	$weight = pow(0.5, $i);
    	$total += $score * $weight;
		$totalweights += $weight;
    	$i++;
    }
	
	return $total/ $totalweights; 
}

function convertTo10pointScale($weightedaveragescore, Assignment $assignment)
{
	if(!is_numeric($weightedaveragescore))
		//throw new Exception('Non-numeric argument past as weighted average score');
		return "--";
	elseif($weightedaveragescore < 0)
		return 0;
	
	$maxScore = $assignment->calibrationMaxScore;
	$thresholdMSE = $assignment->calibrationThresholdMSE;
	$thresholdScore = $assignment->calibrationThresholdScore;
	
	return max(0, precisionFloat( (($thresholdScore-$maxScore) / $thresholdMSE) * $weightedaveragescore + $maxScore));
}

function topicHash(UserID $userID, $topics)
{
	global $dataMgr;
	
	if(!$dataMgr->isStudent($userID))
		throw new Exception('User is not a student');
	
	if($assignment->submissionSettings->topics)
		throw new Exception('Assignment is not an essay with multiple topics');
	
	$numTopics = sizeof($topics);
	$UserStudentID = $dataMgr->getUserInfo($userID)->studentID;
	$topicsString = ""; 
	foreach($topics as $topic)
	{
		$topicsString .= $topic;
	}
	$hash = sha1($UserStudentID.$topicsString);
	$trimmed_converted = hexdec(substr($hash, 0, 8));
	$index = $trimmed_converted % $numTopics;
	return $index;
}

function calibrationHistory(UserID $studentID, Assignment $latestCalibrationAssignment)
{	
	$result = new stdClass;
	$result->hasReached = false;
	$result->score = NULL;
	$result->reviewNum = NULL;
	
	if($latestCalibrationAssignment != NULL)
	{
		global $dataMgr;
		
		$threshold = $latestCalibrationAssignment->calibrationThresholdMSE;
		$minimum = $latestCalibrationAssignment->calibrationMinCount;
		$scores = $dataMgr->getCalibrationScores($studentID);
		$calibReviews = $dataMgr->numCalibrationReviews($studentID);

		for($i = $minimum; $i <= $calibReviews; $i++)
		{
			//get oldest $i reviews and maintain order from newest to oldest
			$sampleScores = array_slice($scores, -$i, $i, true);
			$weightedAverage = computeWeightedAverage($sampleScores);
			if($weightedAverage <= $threshold)
			{
				$result->hasReached = true;
				$result->score = convertTo10pointScale($weightedAverage, $latestCalibrationAssignment);
				$result->reviewNum = $i;
				break;
			}	
		}
	}

	return $result; 
}

function isFlaggedIndependent(UserID $studentID)
{
	global $dataMgr;
	
	if($dataMgr->latestAssignmentWithFlaggedIndependents())
	{
		$latestAssignmentID = new AssignmentID($dataMgr->latestAssignmentWithFlaggedIndependents());
		$latestAssignment = $dataMgr->getAssignment($latestAssignmentID);
		return array_key_exists($studentID->id, $latestAssignment->getIndependentUsers());
	} 
	return false;
}

//Maybe can eliminate second argument by calling latestCalibrationAssignment function
function isIndependent(UserID $studentID, Assignment $latestCalibrationAssignment)
{
	return calibrationHistory($studentID, $latestCalibrationAssignment)->hasReached || isFlaggedIndependent($studentID);
}

//Calls the assignment with latest reviewEndDate and has at least one submission that has a calibration match ie. a calibrated submission
function latestCalibrationAssignment()
{
	global $dataMgr;
	
	$latestCalibrationAssignment = NULL;
	$assignments = $dataMgr->getAssignments();
	foreach($assignments as $assignment)
	{
		if($assignment->getCalibrationSubmissionIDs())
		{
			if($latestCalibrationAssignment == NULL)
				$latestCalibrationAssignment = $assignment;
			elseif($latestCalibrationAssignment->calibrationStopDate < $assignment->calibrationStopDate)
				$latestCalibrationAssignment = $assignment;
		}
	}
	return $latestCalibrationAssignment;
}

function getWeightedAverage(UserID $userid, Assignment $assignment=NULL)
{
	global $dataMgr;
	
	$scores = $dataMgr->getCalibrationScores($userid);
	
	if($scores)
		$average = computeWeightedAverage($scores);
	else 
		$average = "--";

	if($assignment!=NULL)
		$average = convertTo10pointScale($average, $assignment);

	return $average;
}
