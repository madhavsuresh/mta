<?php
function mock_peer_reviews(){
    global $NOW;
    
    $db = $dataMgr->getDatabase();



    $db->beginTransaction();

    $sample_review = array("answerInt" => "0");
    $sh = $this->prepareQuery("insertReviewAnswerQuery", "INSERT INTO peer_review_assignment_review_answers (matchID, questionID, answerInt, answerText, reviewTimestamp) VALUES (?, ?, ?, ?, ".$this->from_unixtime("?").");");



}




?>
