<?php
require_once ("../inc/common.php");
require_once("../inc/datamanager.php");
require_once("submit.php");
#require_once("try.php");
require_once("../inc/assignment.php");
require_once("../inc/ids.php");
require_once("setup_radio_question.php");


function createTestCourse($json){
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


function setupCourse($num_students=10){
    $json = '{
        "name": "thename",
        "displayName": "thedisplayname",
        "authType": "pdo",
        "registrationType": "Open",
        "browsable" : "true"}' ;

    $new_course_id = createTestCourse($json);
    addStudentsToCourse($num_students);


    $assignment_and_id = createAssignment();
    $assignment = $assignment_and_id[1];
    $assignmentID = $assignment_and_id[0];
    
    mockSubmissions($new_course_id,$assignmentID);
    
    setup_radio_question($assignment, 5);


}
    ?>
