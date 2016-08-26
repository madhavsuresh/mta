<?php
require_once("../inc/common.php");

//TODO figure out better dates
$time = $dataMgr->from_unixtime($NOW);

$unix_time = '';
for ($i=9;$i<19;$i++){
    $unix_time .= $time[$i];
}
$late_unix_time = $unix_time;
$late_unix_time[0] = "2";

$assignment_defaults_json = json_encode(array( 
    "AsignmentType"=> "peerreview",
    "submissionQuestion"=> "Default Question",
    "submissionStartDate"=> $unix_time,
    "submissionStopDate"=> $late_unix_time,
    "reviewStartDate"=> $unix_time,
    "reviewStopDate"=> $late_unix_time,
    "markPostDate"=> $unix_time,
    "appealStopDate"=> $late_unix_time,
    "maxSubmissionScore"=> "10",
    "maxReviewScore"=> "5",
    "defaultNumberOfReviews"=> "3",
    "allowRequestOfReviews"=> "0",
    "showmarksForReviewsReceived"=> "0",
    "showOtherReviewsByStudents"=> "0",
    "showOtherReviewsByInstructor"=> "0",
    "showMarksForOtherReviews"=> "0",
    "showPoolStatus"=> "0",
    "calibrationMinCount"=> "0",
    "calibrationMaxScore"=> "0",
    "calibrationThresholdMSE"=> "0",
    "calibrationThresholdScore"=> "0",
    "extraCalibrations"=> "0",
    "calibrationStartDate"=> $unix_time,
    "calibrationStopDate"=> $unix_time,
    "submissionType"=> "essay",
    "assignmentName"=> "test assignment",
    "autoAssignEssayTopic"=> "0"));

    
    

?>
