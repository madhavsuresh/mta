<?php
require_once ("../inc/common.php");
require_once("../inc/datamanager.php");
require_once("submit.php");
#require_once("try.php");
require_once("../inc/assignment.php");
require_once("../inc/ids.php");
require_once("setup_radio_question.php");


function create_course($course_params){
    global $dataMgr, $NOW;
    # $course_vars = (json_decode($json));

    $courseObj = new stdClass();
    $courseObj->name = $course_params['name'];
    echo $courseObj->name;
    $courseObj->displayName = $course_params['displayName'];
    $courseObj->authType = $course_params['authType'];
    $courseObj->registrationType = $course_params['registrationType'];
    $courseObj->browsable = isset_bool($course_params['browsable']);

    $db = $dataMgr->getDatabase();
    $db->beginTransaction();

    $sh = $db->prepare("SELECT MAX(courseID) AS course_id FROM COURSE;");
    $sh->execute();
    $new_course_id = $sh->fetchall();
    $new_course_id = $new_course_id[0]->course_id + 1;

    $dataMgr->createCourse($course_params['name'], $course_params['displayName'], $course_params['authType'], $course_params['registrationType'], isset_bool($course_params['browsable']));

    $dataMgr->setCourseFromID(new CourseID($new_course_id));
    $db->commit();
    return $new_course_id;
}

function addUserToCourse($course_name, $user_params) {
	global $dataMgr;
	$authMgr = $dataMgr->createAuthManager();
	$dataMgr->setCourseFromName($course_name);
	$dataMgr->addUser($user_params['username'], $user_params['first_name'], $user_params['last_name'], $user_params['user_id'], $user_params['type']);
	$authMgr->addUserAuthentication($user_params['username'], $user_params['password']);	

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

