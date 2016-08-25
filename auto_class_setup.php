<?php
require_once ("inc/common.php");
require_once("inc/datamanager.php");
require_once("submission_api.php");
#require_once("try.php");
require_once("inc/assignment.php");
require_once("inc/ids.php");

function createCourse(){
    global $dataMgr, $NOW,$json;


    $course_vars = (json_decode($json));


    $courseObj = new stdClass();

    $courseObj->name = "test";

    $courseObj->name .= substr(md5(microtime()),rand(0,26),5); #random 5 chr str to allow uniqueness

    echo $dataMgr->from_unixtime($NOW);

    $courseObj->displayName = $courseObj->name;
    $courseObj->authType = $course_vars->authType;
    $courseObj->registrationType = $course_vars->registrationType;
    $courseObj->browsable = isset_bool($course_vars->browsable);

    $db = $dataMgr->getDatabase();
    $db->commit();
    $db->beginTransaction();

    $sh = $db->prepare("SELECT MAX(courseID) AS course_id FROM COURSE;");
    $sh->execute();
    $new_course_id = $sh->fetchall();
    $new_course_id = $new_course_id[0]->course_id + 1;
    print_r($new_course_id);

    $dataMgr->createCourse($courseObj->name, $courseObj->displayName, $courseObj->authType, $courseObj->registrationType, $courseObj->browsable);
    $dataMgr->setCourseFromID(new CourseID($new_course_id));
    return $new_course_id;
}


$json = '{
    "name": "thename",
    "displayName": "thedisplayname",
    "authType": "pdo",
    "registrationType": "Open",
    "browsable" : "true"}' ;


$new_course_id = createCourse();

#    $authMgr = new PDOAuthManager("open", $dataMgr);

function addStudentsToCourse($num_students){
    #assumes the dataMgr has been set to the right course
    global $dataMgr;
    $authMgr = $dataMgr->createAuthManager();
    $teacher_first_name = "teacher";
    $teacher_last_name = "teacher";
    $teacher_id = rand(0,10000);
    $type = "instructor";

    for ($i = 0; $i < $num_students; $i++){
        $first_name = "william" . strval($i);
        $last_name = "wallace" . strval($i);
        $student_id = rand(0, 10000);
        $username = $first_name;
        $type = "student";
        $dataMgr->addUser($username, $first_name, $last_name, $student_id, $type);

        $password = "123";
        $authMgr->addUserAuthentication($username, $password);
    }
}

addStudentsToCourse(5);

$assignment_vars = json_decode($json);
$assignment_vars->assignmentType = "peerreview";
$assignment_vars->courseID = $new_course_id;


$new_assignment = $dataMgr->createAssignmentInstance(null, $assignment_vars->assignmentType);

            $new_assignment->submissionQuestion = "What is the question?";

            $new_assignment->submissionStartDate = $dataMgr->from_unixtime($NOW);
            $new_assignment->submissionStopDate = $dataMgr->from_unixtime($NOW);

            $new_assignment->reviewStartDate = $dataMgr->from_unixtime($NOW);
            $new_assignment->reviewStopDate = $dataMgr->from_unixtime($NOW);

            $new_assignment->markPostDate = $dataMgr->from_unixtime($NOW);

            $new_assignment->appealStopDate = $dataMgr->from_unixtime($NOW);

            $new_assignment->maxSubmissionScore = 0;
            $new_assignment->maxReviewScore = 10;
            $new_assignment->defaultNumberOfReviews = 3;
            $new_assignment->allowRequestOfReviews = 0;
            $new_assignment->showMarksForReviewsReceived = 0;
            $new_assignment->showOtherReviewsByStudents = 0;
            $new_assignment->showOtherReviewsByInstructors = 0;
            $new_assignment->showMarksForOtherReviews = 0;
            $new_assignment->showMarksForReviewedSubmissions = 0;
            $new_assignment->showPoolStatus = 0;
            /* $%$
            $assignment->reviewScoreMaxDeviationForGood,
            $assignment->reviewScoreMaxCountsForGood,
            $assignment->reviewScoreMaxDeviationForPass,
            $assignment->reviewScoreMaxCountsForPass,
 */

			$new_assignment->calibrationMinCount = 0;
			$new_assignment->calibrationMaxScore = 0;
			$new_assignment->calibrationThresholdMSE = 0;
			$new_assignment->calibrationThresholdScore = 0;
			$new_assignment->extraCalibrations = 0;
			$new_assignment->calibrationStartDate = $dataMgr->from_unixtime($NOW);
            $new_assignment->calibrationStopDate =  $dataMgr->from_unixtime($NOW);


            $new_assignment->assignmentID = 2;

$new_assignment->submissionType = "essay";
#$new_assignment->name = "s";
$new_assignment->autoAssignEssayTopic = 0;
#$new_assignment->courseID = $new_course_id;
$dataMgr->saveAssignment($new_assignment, $assignment_vars->assignmentType);



$db->commit();
?>
