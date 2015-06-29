<?php
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/calibrationutils.php");

class ExportCalibrationsPeerReviewScript extends Script
{
    function getName()
    {
        return "Export Calibration Submissions";
    }
    function getDescription()
    {
        return "Dumps out the calibration data for analysis";
    }
	function getFormHTML()
    {
        $html  = "<table width='100%'>\n";
        $html .= "<tr><td>Include dropped students</td><td>";
        $html .= "<input type='checkbox' name='includedropped' value='includedropped' checked/></td></tr>";
        $html .= "</table>\n";
        return $html;
    }
    /*function getFormHTML()
    {
        return "(None)";
    }
    function hasParams()
    {
        return false;
    }*/
    function executeAndGetResult()
    {
        global $dataMgr;
        //Get all the assignments
        $currentAssignment = get_peerreview_assignment();

		if(array_key_exists("includedropped", $_POST)){
            $students = $dataMgr->getStudents();
        }else{
            $students = $dataMgr->getActiveStudents();
        }

        $instructorReviews = array();
        $assignments = array();
        $studentReviews = array();

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

                    //If we've never seen this review before, we need to store it's assignment and the gold standard review
                    if(!array_key_exists($instructorReview->submissionID->id, $instructorReviews)){
                        $instructorReviews[$instructorReview->submissionID->id] = $instructorReview;
                        $assignments[$instructorReview->submissionID->id] = $assignmentWithSubmission;
                    }
                    //Push back the array if we need it for this review
                    if(!array_key_exists($review->submissionID->id, $studentReviews)){
                        $studentReviews[$review->submissionID->id] = array();
                    }
                    $studentReviews[$review->submissionID->id][] = $review;
                }
            }
        }

        $displayNames = $dataMgr->getUserDisplayMap();

        $csv = "";
        foreach($instructorReviews as $id => $instructorReview)
        {
            $assignment = $assignments[$id];
            $reviews = $studentReviews[$id];

            $csv .= "Assignment," . $assignment->name . ",Author," . $displayNames[$assignment->getSubmission(new SubmissionID($id))->authorID->id] . "\n"; 
            
            $questions = array_filter($assignment->getReviewQuestions(), function($x) { return $x instanceOf RadioButtonQuestion; });

            $csv .= "Question";
            foreach($questions as $question){
                $csv .= "," . $question->name;
            }
            $csv .= "\n";

            $csv .= "Instructor";
            foreach($questions as $question){
                //Dunno if you want the text or the number..... change it from ->label to ->value
                $csv .= "," . $question->options[$instructorReview->answers[$question->questionID->id]->int]->label;
            }
            $csv .= "\n";

            foreach($reviews as $review){
                $csv .= $displayNames[$review->reviewerID->id];
                foreach($questions as $question){
                    //Dunno if you want the text or the number..... change it from ->label to ->value
                    $csv .= "," . $question->options[$review->answers[$question->questionID->id]->int]->label;
                }
                $csv .= "\n";
            }
            $csv .= "\n";
        }

        header("Content-Disposition: attachment; filename=calibrationgrades.csv");
        header("Content-Type: text/csv");
        //Swap this header out if you just want to see it in the browser 
        #header("Content-Type: text/plain");
        echo $csv;
        exit();
    }
}

