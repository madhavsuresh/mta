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

/*
function computeReviewPointsForAssignments(UserID $student, $assignments)
{
    $points = array();
    foreach($assignments as $assignment)
    {
        foreach($assignment->getAssignedCalibrationReviews($student) as $matchID)
        {
            $mark = $assignment->getReviewMark($matchID);
            if($mark->isValid)
                $points[$matchID->id] = $mark->getReviewPoints(); //Use timestamp as key
        }
    }
    
    krsort($points);
    //return array_reduce($points, function($v, $w) { return max($v+$w, 0); });
    
	$total = 0;
	$totalweights = 0;
	$i = 0;
    foreach($points as $point)
    {
    	$weight = pow(0.5, $i);
    	$total += $point * $weight;
		$totalweights += $weight;
    	$i++;
    }
	
	return $total/ $totalweights; 
}
*/

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
	$maxScore = $assignment->calibrationMaxScore;
	$thresholdMSE = $assignment->calibrationThresholdMSE;
	$thresholdScore = $assignment->calibrationThresholdScore;
	
	return max(0, precisionFloat( -( ($maxScore - $thresholdScore) / $thresholdMSE) * $weightedaveragescore + $maxScore));
}

function convertTo10pointScale_($weightedaveragescore, PeerReviewAssignment $assignment)
{
	$maxScore = $assignment->calibrationMaxScore;
	$thresholdMSE = $assignment->calibrationThresholdMSE;
	$thresholdScore = $assignment->calibrationThresholdScore;
	
	return max(0, precisionFloat( -( ($maxScore - $thresholdScore) / $thresholdMSE) * $weightedaveragescore + $maxScore));
}

function topicHash(UserID $userID, $topics)
{
	global $dataMgr;
	
	if(!$dataMgr->isStudent($userID))
		throw new Exception('User is not a student');
	
	if($assignment->submissionSettings->topics)
		throw new Exception('Assignment is not an essay with multiple topics');
	
	$k = sizeof($topics);
	$UserStudentID = $dataMgr->getUserInfo($userID)->studentID;
	$topicsString = ""; 
	foreach($topics as $topic)
	{
		$topicsString .= $topic;
	}
	$hash = sha1($UserStudentID.$topicsString);
	#print_r($UserStudentID.$topicsString);
	#print_r(" --> ".$hash);
	#print_r(" % $k = ".($hash % $k));
	return $hash % $k;
}
