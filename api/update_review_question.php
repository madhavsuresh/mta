<?php
require_once("../inc/common.php");
require_once("../peerreview/inc/common.php");
    function update_review_question(PeerReviewAssignment $assignment, $new_question_params, $question_id){
        #assuming $new_question_params doesn't have question id in it, you'll have to get it.
        #because want the param format for an update to be the same as a create
        #assuming completly filled in and validated. 
        $newQuestionID = new QuestionID($question_id);# must be in class form
        $old_review_question = $assignment->getReviewQuestion($newQuestionID);
        $old_review_question->options = array();
        foreach($old_review_question as $key => $value){
            if ($key == "questionID"){
                continue;
            }
            elseif ($key == "options"){
                foreach($new_question_params["options"] as $key => $value){
                    $score = $value["score"];
                    $label = $value["label"];
                    $option = new RadioButtonOption($label, $score);
                    $old_review_question->options[] = $option;
                }        

            }
            else{
                $old_review_question->$key = $new_question_params[$key];
            }
        }
        $assignment->saveReviewQuestion($old_review_question);
    }
    
?>
