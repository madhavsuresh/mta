<?php
require_once ("inc/common.php");
require_once("inc/datamanager.php");
require_once("submission_api.php");
#require_once("try.php");
require_once("inc/assignment.php");
require_once("inc/ids.php");



    function createTestCourse($courseName, $json){
        global $dataMgr, $NOW;


        $course_vars = (json_decode($json));


        $courseObj = new stdClass();

        $courseObj->name = "test";

        $courseObj->name .= substr(md5(microtime()),rand(0,26),5); #random 5 chr str to allow uniqueness
        echo $courseObj->name;

        #$courseObj->name = $courseName;

        $courseObj->displayName = $courseObj->name;
        $courseObj->authType = $course_vars->authType;
        $courseObj->registrationType = $course_vars->registrationType;
        $courseObj->browsable = isset_bool($course_vars->browsable);

        $db = $dataMgr->getDatabase();
        $db->beginTransaction();

        $sh = $db->prepare("SELECT MAX(courseID) AS course_id FROM COURSE;");
        $sh->execute();
        $new_course_id = $sh->fetchall();
        $new_course_id = $new_course_id[0]->course_id + 1;

        $dataMgr->createCourse($courseObj->name, $courseObj->displayName, $courseObj->authType, $courseObj->registrationType, $courseObj->browsable);

        $dataMgr->setCourseFromID(new CourseID($new_course_id));
        $db->commit();
        return $new_course_id;
    }


    function addStudentsToCourse($num_students){
        #assumes the dataMgr has been set to the right course
        global $dataMgr;
        $authMgr = $dataMgr->createAuthManager();

        $teacher_first_name = "teacher";
        $teacher_last_name = "teacher";
        $teacher_id = rand(0,10000);
        $type = "instructor";
        $username = $teacher_first_name;
        $dataMgr->addUser($username, $teacher_first_name, $teacher_last_name, $teacher_id, $type);

        $password = "123";
        $authMgr->addUserAuthentication($username, $password);

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



    function createAssignment(){
        global $dataMgr, $NOW;
        $db = $dataMgr->getDatabase();
        $assignmentType = 'peerreview';

        $new_assignment = $dataMgr->createAssignmentInstance(null, $assignmentType);

        $new_assignment->submissionQuestion = "What is the question?";
        $time = $dataMgr->from_unixtime($NOW);
        # for some reason I can't just put this value into the db. It will accept
        # the unix epoch offset. Need to loop through time and grab that value
        $unix_time = '';
        for ($i = 9; $i < 19; $i++){
            $unix_time .= $time[$i];
        }


        $late_unix_time = $unix_time;
        $late_unix_time[0] = "2"; # this puts the date ~32 years in the future so things won't be due



        $new_assignment->submissionStartDate = $unix_time;
        $new_assignment->submissionStopDate =$late_unix_time;

        $new_assignment->reviewStartDate = $unix_time;
        $new_assignment->reviewStopDate = $late_unix_time;

        $new_assignment->markPostDate =$unix_time;

        $new_assignment->appealStopDate = $late_unix_time;

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

        $new_assignment->calibrationMinCount = 0;
        $new_assignment->calibrationMaxScore = 0;
        $new_assignment->calibrationThresholdMSE = 0;
        $new_assignment->calibrationThresholdScore = 0;
        $new_assignment->extraCalibrations = 0;
        $new_assignment->calibrationStartDate = $unix_time; #set these equal so calibration doesn't happen ??
        $new_assignment->calibrationStopDate = $unix_time;

                #$new_assignment->assignmentID = 2;

        $new_assignment->submissionType = "essay";
        $new_assignment->name = "s";

        $submissionSettingsType = "essaySubmissionSettings";

        $new_assignment->autoAssignEssayTopic = 0;

        $new_assignment->submissionSettings = new $submissionSettingsType();

        $new_assignment->submissionSettings->essayWordLimit = 1000;
        $dataMgr->saveAssignment($new_assignment, $assignmentType);
        $assignmentID = $db->lastInsertId();
        return $assignmentID;
    }


function setupCourse($num_students=10, $courseName='test_course'){



    $json = '{
        "name": "thename",
        "displayName": "thedisplayname",
        "authType": "pdo",
        "registrationType": "Open",
        "browsable" : "true"}' ;


    $new_course_id = createTestCourse($courseName, $json);




    addStudentsToCourse($num_students);


    $assignmentID = createAssignment();

    mockSubmissions($new_course_id,$assignmentID);
}
    ?>

