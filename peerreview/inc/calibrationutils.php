<?php
require_once(dirname(__FILE__)."/common.php");

function generateAutoMark(PeerReviewAssignment $assignment, Review $instructorReview, Review $review)
{
    //get an array of all the differences
    $differences = array(); 
    foreach($assignment->getReviewQuestions() as $question)
    {
        $id = $question->questionID->id;
        $differences[] = abs($question->getScore($instructorReview->answers[$id]) - $question->getScore($review->answers[$id]) );
    }

    if(sizeof(array_filter($differences, function($x) use($assignment) { return $x > $assignment->reviewScoreMaxDeviationForGood; })) <= $assignment->reviewScoreMaxCountsForGood && max($differences) <= $assignment->reviewScoreMaxDeviationForGood)
        $points = 1;
    else if(sizeof(array_filter($differences, function($x) use($assignment) { return $x >= $assignment->reviewScoreMaxDeviationForPass; })) <= $assignment->reviewScoreMaxCountsForPass &&
                 max($differences) <= $assignment->reviewScoreMaxDeviationForPass)
        $points = -0.25;
    else
        $points = -1;

    return new ReviewMark(0, null, true, $points);
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
