<?php
require_once("../peerreview/inc/common.php");


function setup_radio_question($assignment, $num_options){
    
    $json = '{"name": "testname", 
    "question_text" : "please answer question?",
    "class" : "RadioButtonQuestion",
    "hiden" : "0"}';
    $json = json_decode($json);

    # sets question id to null bc auto gen. other params are set to default
    $question = new RadioButtonQuestion(NULL, $json->name, $json->question_text); 

    for ($i = 1; $i <= $num_options; $i++){
        $option = new RadioButtonOption(chr($i+64), $i);
        $question->options[] = $option; 

    } 
    $assignment->saveReviewQuestion($question);
    


    }








?>
