<?php
require_once("../peerreview/inc/common.php");


function setup_radio_question($assignment, $review_params){
    #assumes new radio/peer review rubric. 

    # sets question id to null bc auto gen. 
    $question = new RadioButtonQuestion(NULL, 
                                        $review_params["name"],
                                        $review_params["question_text"]); 
    $radio_button_options = $review_params["radio_button_options"];
    foreach($radio_button_options as $key => $value){
        $option = new RadioButtonOption($key, $value);
        $question->options[] = $option; 
    }

    $assignment->saveReviewQuestion($question);
    
    }
?>
