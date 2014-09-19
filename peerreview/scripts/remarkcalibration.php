<?php
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/calibrationutils.php");

class RemarkCalibrationPeerReviewScript extends Script
{
    function getName()
    {
        return "Remark Calibration Submissions";
    }
    function getDescription()
    {
        return "Re-marks the calibration submissions automatically";
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
        //Get all the assignments
        $currentAssignment = get_peerreview_assignment();

        $students = $dataMgr->getStudents();
        foreach($students as $student)
        {
            $reviewAssignments = $currentAssignment->getAssignedCalibrationReviews($student);
            foreach($reviewAssignments as $matchID){
                $matchID = new MatchID($matchID);
                $assignmentWithSubmission = $dataMgr->getAssignment($dataMgr->getAssignmentDataManager("peerreview")->getAssignmentIDForMatchID($matchID));
                if($assignmentWithSubmission->getReviewMark($matchID)->isValid)
                {
                    $review = $assignmentWithSubmission->getReview($matchID);
                    $instructorReview = $assignmentWithSubmission->getSingleCalibrationKeyReviewForSubmission($review->submissionID);
                    $mark = generateAutoMark($assignmentWithSubmission, $instructorReview, $review);
                    $currentAssignment->saveReviewMark($mark, $matchID);
                }
            }
        }
        return "Marking complete";
    }
}

