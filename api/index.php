<?php
require '../vendor/autoload.php';
require_once("../inc/common.php");
require_once("default_values.php");
require_once("create_class.php");
require_once("fill_and_decode_json.php");
require_once("create_assignment.php"); 
require_once("update_review_question.php");
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
$app = new \Slim\App(["settings" => $config]);

$app->get('/', function (Request $request, Response $response) {
	$response->getBody()->write("Hello, $rootUri");
});
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, esstsefjsdf");

    return $response;
});

$app->get('/getallstudents/{course}', function (Request $request, Response $response) use ($dataMgr) {
	$dataMgr->setCourseFromName($request->getAttribute('course'));
	$students = $dataMgr->getStudents();
	$newResponse = $response->withJson($students);
	return $newResponse;
});

$app->get('/getallsubmissions/{course}/{assignmentID}', function (Request $request, Response $response) use ($dataMgr) {
	$dataMgr->setCourseFromName($request->getAttribute('course'));	
});

$app->get('/course/get', function (Request $request, Response $response) use ($dataMgr) {
	# needs JSON validation
	$params = $request->getBody();
	$params = json_decode($params, true);
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);

	$courseInfo = $dataMgr->getCourseInfo($courseID);
	return $response->withJson($courseInfo);
});

$app->post('/course/create', function (Request $request, Response $response) use ($dataMgr) {	
	# needs JSON validation
	$params = $request->getBody();
	$params = json_decode($params, true);

	$dataMgr->createCourse($params['name'], $params['displayName'], $params['authType'], $params['registrationType'], isset_bool($params['browsable']));
	return $response;
});


$app->post('/course/update', function (Request $request, Response $response) use ($dataMgr) {	
	# needs JSON validation
	$params = $request->getBody();
	$params = json_decode($params, true);
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);

	
});

$app->post('/course/delete', function (Request $request, Response $response) use ($dataMgr) {	
	# needs JSON validation
	$params = $request->getBody();
	$params = json_decode($params, true);
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);

	$dataMgr->deleteCourse($courseID);
	return $response;	
});

$app->get('/getallassignments/{courseName}', function (Request $request, Response $response) use ($dataMgr){
    $dataMgr->setCourseFromName($request->getAttribute("courseName"));
    #$db = $dataMgr->getDatabase();

    #$db->beginTransaction();
    #$sh = $db->prepare("SELECT name, assignmentID FROM assignments WHERE courseID = ?;");
    #$sh->execute(array($dataMgr->courseID));
    #$assignments = $sh->fetchall();
    $assignments = $dataMgr->getAssignments();
    $newResponse = $response->withJson($assignments);
    return $newResponse;
});

$app->get('/rubrics/get/{assignmentID}',function (Request $request, Response $response) use ($dataMgr){

    $assignment = $dataMgr->getAssignment(new AssignmentID($request->getAttribute("assignmentID")));
    $questions = $assignment->getReviewQuestions($assignment);
    $newResponse = $response->withJson($questions);
    return $newResponse;
});

$app->post('/rubrics/update/{assignmentID}/{questionID}',function(Request $request, Response $response) use($dataMgr){
    //TODO Make this a patch
    $params = $request->getBody();
    $params = json_decode($params,true);
    $assignment = $dataMgr->getAssignment(new AssignmentID($request->getAttribute("assignmentID")));
    update_review_question($assignment, $params, $request->getAttribute("questionID"));
    $response->getBody()->write("finished");
});

$app->post('/makesubmissions/', function (Request $request, Response $response) use
    ($dataMgr){#takes in course name and assignment id
        $params = $request->getBody();
        $params = json_decode($params,true);
        mockSubmissions($params["courseID"], $params["assignmentID"]); 
});



$app->post('/createrubric/{courseName}/{assignmentID}',function(Request $request, Response $response) use ($dataMgr){
    $course_name = $request->getAttribute('courseName');
    $dataMgr->setCourseFromName($course_name);
    #actually don't think you even need the course name, but not 100% about the whole mta system so its here
    $assignment_id = $request->getAttribute('assignmentID');
    $assignment = $dataMgr->getAssignment(new AssignmentID($assignment_id)); 
    
    $params = $request->getBody();
    $default = get_rubric_defaults();
    $rubric_params = fill_and_decode_json($default, $params);

    setup_radio_question($assignment, $rubric_params);

});
$app->post('/createassignment/{courseName}', function (Request $request, Response $response) use ($dataMgr){
    $course_name = $request->getAttribute('courseName');
    $dataMgr->setCourseFromName($course_name);
    $user_assignment_settings = $request->getBody();
    
    $default = get_assignment_defaults(); 
	#$response->getBody()->write($default->AssignmentType);
    $assignment_params = fill_and_decode_json($default, $user_assignment_settings);
    createAssignment($assignment_params,$course_name); 
});

$app->post('assignment/update/{courseID}/{assignmentID}', function( Request $request, Response $response) use ($dataMgr){
    
});


//TODO: proper exception handling. 
$app->post('/uploadpeermatch/{course}/{assignmentID}', function (Request $request, Response $response) use ($dataMgr) {
	//probably should check type information. 
	$assignmentID = $request->getAttribute('assignmentID');
	$body = $request->getBody();
	$db = $dataMgr->getDatabase();
	$matchings = json_decode($body, $assoc = true);

	//TODO:change this into middleware
	switch (json_last_error()) {
	case JSON_ERROR_NONE:
		break;
	case JSON_ERROR_DEPTH:
		echo ' - Maximum stack depth exceeded';
		break;
	case JSON_ERROR_STATE_MISMATCH:
		echo ' - Underflow or the modes mismatch';
		break;
	case JSON_ERROR_CTRL_CHAR:
		echo ' - Unexpected control character found';
		break;
	case JSON_ERROR_SYNTAX:
		echo ' - Syntax error, malformed JSON';
		break;
	case JSON_ERROR_UTF8:
		echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
		break;
	default:
		echo ' - Unknown error';
		break;
	}
	$getSubmissionIds = $db->prepare("SELECT submissionid FROM PEER_REVIEW_ASSIGNMENT_SUBMISSIONS where authorID = ? and assignmentID = ?");
	$checkForMatch = $db->prepare("SELECT matchID FROM PEER_REVIEW_ASSIGNMENT_MATCHES where submissionID=? AND reviewerID = ?;");
	$insertMatch = $db->prepare("INSERT INTO PEER_REVIEW_ASSIGNMENT_MATCHES (submissionID, reviewerID, instructorForced, calibrationState) values (?,?,0,'none')");
	foreach ($matchings as $studentID => $matchingMap) {
			$db->beginTransaction();
			$getSubmissionIds->execute(array($studentID, $assignmentID));
			$res  = $getSubmissionIds->fetch();
			//TODO: do we really need this?? check the type output for pdo object fetch()
			//TODO: this should probably be an error, return an error code
			if ($res) {
				$submissionID = $res->submissionID;
			} else {
				$db->commit();
				continue;
			}

			foreach($matchingMap as $matchedReviewer) {
				$checkForMatch->execute(array($submissionID, $matchedReviewer));
				
				if (!$checkForMatch->fetch()){
					$insertMatch->execute(array($submissionID, $matchedReviewer));
				}
			}
			$db->commit();
	}
});

$app->run();
