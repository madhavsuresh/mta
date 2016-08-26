<?php
require_once("inc/common.php");
require_once("inc/datamanager.php");
require_once("peerreview/inc/datamanagers/pdoassignmentdatamanager.php");
require_once("config.php");

function mockSubmissions($courseID, $assignmentID){
    global $dataMgr, $NOW;
    $text_to_add = "The ouick brown fox jumped over the fence";
    $DELETE_FLAG = FALSE;
    try{

        $db = $dataMgr->getDatabase();
        $db->beginTransaction();

        if ($DELETE_FLAG == TRUE )
            {
                $sh = $db->prepare("DELETE FROM peer_review_assignment_submissions;");
                $sh->execute();
                $sh = $db->prepare("DELETE FROM peer_review_assignment_essays;");
                $sh->execute();

            }

        $sh = $db->prepare("SELECT userID FROM users WHERE userType = 'student' AND courseID = :courseID;");
        $sh->execute(array('courseID' => $courseID));
        $students = $sh->fetchall();

        foreach ($students as $student){
            $sh= $db->prepare("Insert INTO peer_review_assignment_submissions (assignmentID, authorID, noPublicUse, submissionTimestamp) Values(?,?,?, ".$dataMgr->from_unixtime("?").");");
            $sh->execute(array($assignmentID,$student->userID, 0, $NOW ));
            $lastID = $db->lastInsertId();

            $sh = $db->prepare("Insert INTO peer_review_assignment_essays  Values(?,?,?);");
            $sh->execute(array($lastID, $text_to_add, NULL));
        }

    $db->commit();

    }
    catch (Exception $e){
       echo $e->getMessage();
    }
}
?>



