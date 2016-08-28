<?php
require '../vendor/autoload.php';
require_once("../inc/common.php");
require_once("default_values.php");
require_once("create_class.php");
require_once("fill_and_decode_json.php");
require_once("create_assignment.php"); 
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

$app->post('/createassignment/{courseName}', function (Request $request, Response $response) use ($dataMgr){
    $course_name = $request->getAttribute('courseName');
    $user_assignment_settings = $request->getBody();
    
    $default = get_assignment_defaults(); 
	#$response->getBody()->write($default->AssignmentType);
    $assignment_params = fill_and_decode_json($default, $user_assignment_settings);
    createAssignment($assignment_params,$course_name); 
});


$app->post('/createcourse/{num}', function(Request $request, Response $response) use ($dataMgr) {
	$num_students = $request->getAttribute('num');
	setupCourse($num_students);
	$response->getBody()->write("Hello, test");
	return $response;
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
