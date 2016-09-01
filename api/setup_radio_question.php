<?php
require_once("../peerreview/inc/common.php");


function setup_radio_question($assignment, $rubric_params){
    #assumes new radio/peer review rubric. 

    # sets question id to null bc auto gen. 
    $question = new RadioButtonQuestion(NULL, 
                                        $rubric_params["name"],
                                        $rubric_params["question_text"]); 
    $radio_button_options = $rubric_params["radio_button_options"];
    foreach($radio_button_options as $key => $value){
        $option = new RadioButtonOption($key, $value);
        $question->options[] = $option; 
    }

    $assignment->saveReviewQuestion($question);
    
    }
?>
