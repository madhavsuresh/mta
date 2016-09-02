<?php
require '../vendor/autoload.php';
require_once("../inc/common.php");
require_once("create_class.php");
require_once("api_lib.php");
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
$app = new \Slim\App(["settings" => $config]);

####################### COURSE ########################

$app->get('/course/get', function (Request $request, Response $response) use ($dataMgr) {
	//TODO ADD ERROR CATCHING
	# $schema = json_decode(file_get_contents('./json/course/get/request.json'));
	$json_body = json_decode($request->getBody());
	/*$validator = new League\JsonGuard\Validator($json_body, $schema);
	if($validator->fails()) {
		print_r($validator->errors());
		return NULL;
		throw new Exception("RIP");
	}*/
	$params = (array) $json_body;	
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);

	$courseInfo = $dataMgr->getCourseInfo($courseID);
	return $response->withJson($courseInfo);
});

$app->post('/course/create', function (Request $request, Response $response) use ($dataMgr) {	
	# needs JSON validation
	//TODO ADD ERROR CATCHING
	$params = $request->getBody();
	$params = json_decode($params, true);

	$dataMgr->createCourse($params['name'], $params['displayName'], $params['authType'], $params['registrationType'], isset_bool($params['browsable']));
	return $response;
});


$app->post('/course/update', function (Request $request, Response $response) use ($dataMgr) {	
	# needs JSON validation
	//TODO ADD ERROR CATCHING
	$params = $request->getBody();
	$params = json_decode($params, true);
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);
	$courseInfo = (array) $dataMgr->getCourseInfo($courseID);
	
	foreach($params as $key => $value) {
		$temp = $params[$key];	
		$courseInfo[$key] = $temp;
	}
	$dataMgr->setCourseInfo($courseID, $courseInfo['name'], $courseInfo['displayName'], $courseInfo['authType'], $courseInfo['registrationType'], isset_bool($courseInfo['browsable']));

	return $response->withJson($dataMgr->getCourseInfo($courseID));
});

$app->post('/course/delete', function (Request $request, Response $response) use ($dataMgr) {	
	# needs JSON validation
	//TODO ADD ERROR CATCHING
	$params = $request->getBody();
	$params = json_decode($params, true);
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);

	$dataMgr->deleteCourse($courseID);
	return $response;	
});

########################### GRADES #######################

$app->get('/assignments/get/all', function (Request $request, Response $response) use ($dataMgr){


    $params = json_decode($request->getBody(), true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignments = $dataMgr->getAssignments();
    $newResponse = $response->withJson($assignments);
    return $newResponse;
});

$app->get('/rubrics/get',function (Request $request, Response $response) use ($dataMgr){

    $params = json_decode($request->getBody(),true);

    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    $questions = $assignment->getReviewQuestions($assignment);
    $newResponse = $response->withJson($questions);
    return $newResponse;
});

$app->post('/rubric/update',function(Request $request, Response $response) use($dataMgr){
    //TODO Make this a patch
    $params = $request->getBody();
    $params = json_decode($params,true);
    $assignment = $dataMgr->getAssignment(new AssignmentID($params["assignmentID"]));
    update_review_question($assignment, $params); 
    $response->getBody()->write("finished");
});

$app->post('/makesubmissions', function (Request $request, Response $response) use
    ($dataMgr){#takes in course name and assignment id
        $params = $request->getBody();
        $params = json_decode($params,true);
        mockSubmissions($params["courseID"], $params["assignmentID"]); 
});


$app->post('/rubric/create',function(Request $request, Response $response) use ($dataMgr){
    
    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    #actually don't think you even need the course name, but not 100% about the whole mta system so its here
    unset($params['courseID']); #get rid of it cause not needed for the rubric
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID'])); 
    setup_radio_question($assignment, $params);

});

$app->get('/peerreviewscores/get', function(Request $request, Response $response) use($dataMgr){

    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    $review = $assignment->getReview(new MatchID($params['matchID']));
    print_r($review);
    $newResponse = $response->withJson($review);
    return $newResponse;
});


$app->post('/peerreviewscores/create', function(Request $request, Response $response) use($dataMgr){

    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    make_peer_review($assignment, $params);
});


$app->post('/assignment/create', function (Request $request, Response $response) use ($dataMgr){
    
    $params = $request->getBody();
    $params = json_decode($params, true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = createAssignment($params); 
});

$app->post('/assignment/update', function( Request $request, Response $response) use ($dataMgr){
    $json_params = $request->getBody();
    $params = json_decode($json_params,true); 
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = createAssignment($params);
    
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
