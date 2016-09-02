<?php
require '../vendor/autoload.php';
require_once("../inc/common.php");
require_once("api_lib.php");

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \JsonSchema\Validator as JsonValidator;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
$container = new \Slim\Container;
$app = new \Slim\App(["settings"=>$config]);



function decode_json_throw_errors($inputString) {
	$json_body = json_decode($inputString);
	switch (json_last_error()) {
	case JSON_ERROR_NONE:
		break;
	case JSON_ERROR_DEPTH:
		throw new Exception('JSON Maximum stack depth exceeded');
		break;
	case JSON_ERROR_STATE_MISMATCH:
		throw new Exception('Underflow or the modes mismatch');
		break;
	case JSON_ERROR_CTRL_CHAR:
		throw new Exception('Unexpected control character found');
		break;
	case JSON_ERROR_SYNTAX:
		throw new Exception('Syntax error, malformed JSON');
		break;
	case JSON_ERROR_UTF8:
		throw new Exception('Malformed UTF-8 characters, possibly incorrectly encoded');
		break;
	default:
		throw new Exception('Unknown Error');
		break;
	}
	return $json_body;
}

$jsonDecodeMW = function ($request, $response, $next) {
	$json_body = decode_json_throw_errors($request->getBody());
	$request = $request->withAttribute('requestDecodedJson', $json_body);
	$response = $next($request, $response);
	return $response;	
};

$jsonvalidatemw = function ($request, $response, $next) {
	$json_body = json_decode($request->getBody());
	$validator = new JsonValidator;
	$validator->check($data, (object)['$ref' => 'file://' . realpath('./peermatch_get.json')]);
};

$app->post('/validate', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$schema = decode_json_throw_errors(file_get_contents('./peermatch/get/request.json'));
	$validator = new League\JsonGuard\Validator($json_body, $schema);
	if ($validator->fails()) {
		print_r($validator->errors());
	} else {
		echo "not";
	}
	//$schemaArray = $myService->schemaArray;
})->add($jsonDecodeMW);


################# COURSE ########################

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

####################### ASSIGNMENTS ###########################3
$app->get('/assignment/get', function (Request $request, Response $response) use ($dataMgr){


    $params = json_decode($request->getBody(), true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    
    if ($params["assignmentIDs"] == "all"){
    
        $assignments = $dataMgr->getAssignments();
        $newResponse = $response->withJson($assignments);
        return $newResponse;
    }
    else {
        $assignment = array(); 
        foreach ($params["assignmentIDs"] as $id){
            $assignments[] = $dataMgr->getAssignment(new AssignmentID($id));
        }
        $newResponse = $response->withJson($assignments);
        return $newResponse;
    }

});

$app->post('/assignment/create', function (Request $request, Response $response) use ($dataMgr){
    
    $params = $request->getBody();
    $params = json_decode($params, true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = create_assignment($params); 
});

$app->post('/assignment/update', function( Request $request, Response $response) use ($dataMgr){
    $json_params = $request->getBody();
    $params = json_decode($json_params,true); 
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = update_assignment($params);
    
});



#################### PEERREVIEWS ######################333
$app->get('/rubric/get',function (Request $request, Response $response) use ($dataMgr){

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
    update_radio_question($assignment, $params); 
    $response->getBody()->write("finished");
});

$app->post('/rubric/create',function(Request $request, Response $response) use ($dataMgr){
    
    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    #actually don't think you even need the course name, but not 100% about the whole mta system so its here
    unset($params['courseID']); #get rid of it cause not needed for the rubric
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID'])); 
    create_radio_question($assignment, $params);

});

$app->get('/peerreviewscores/get', function(Request $request, Response $response) use($dataMgr){

    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    $review = $assignment->getReview(new MatchID($params['matchID']));
    $newResponse = $response->withJson($review);
    return $newResponse;
});


############################ STUDENT SIDE TESTING ###############
$app->post('/makesubmissions', function (Request $request, Response $response) use
    ($dataMgr){#takes in course name and assignment id
        $params = $request->getBody();
        $params = json_decode($params,true);
        mockSubmissions($params["courseID"], $params["assignmentID"]); 
});

$app->post('/peerreviewscores/create', function(Request $request, Response $response) use($dataMgr){

    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    make_peer_review($assignment, $params);
});

$app->get('/getcourseidfromname', function (Request $request, Response $response) use ($dataMgr) {
	$params = $request->getBody();
    $params = json_decode($params,true);
	$dataMgr->setCourseFromName($params['courseName']);
	return $response->withJson($dataMgr->courseID);
});

########################### GRADES #######################

# NEXT SECTION OF ENDPOINTS TO ADD

$app->get('/peermatch/get/',  function (Request $request, Response $response) use ($dataMgr) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$schema = decode_json_throw_errors(file_get_contents('./peermatch_json/get/request.json'));
	$validator = new League\JsonGuard\Validator($json_body, $schema);
	if ($validator->fails()) {
		print_r($validator->errors());
		return NULL;
		throw new Exception(sprintf("[peermatch][get] Validation failed %s", $stringempty));
	} 
	$assignmentID = $json_body->assignmentID;
	$db = $dataMgr->getDatabase();
	$sh = $db->prepare("SELECT  peer_review_assignment_submissions.authorID, peer_review_assignment_matches.reviewerID
       			FROM peer_review_assignment_submissions JOIN peer_review_assignment_matches ON
       		peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID
       		WHERE peer_review_assignment_submissions.assignmentID = ?
       		ORDER BY peer_review_assignment_submissions.authorID");
	$sh->execute(array($assignmentID));
        $reviewerAssignment = array();
        while($res = $sh->fetch())
        {
            if(!array_key_exists($res->authorID, $reviewerAssignment))
            {
                $reviewerAssignment[$res->authorID] = array();
            }
            array_push($reviewerAssignment[$res->authorID], $res->reviewerID);
        }
	$return_array['matchesMap'] = $reviewerAssignment;
	$newResponse = $response->withJson($return_array);
	return $newResponse;
})->add($jsonDecodeMW);

$app->post('/peermatch/create', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$schema = decode_json_throw_errors(file_get_contents('./peermatch_json/create/request.json'));
	$validator = new League\JsonGuard\Validator($json_body, $schema);
	if ($validator->fails()) {
		print_r($validator->errors());
		return NULL;
	}
	$db = $dataMgr->getDatabase();
	$assignmentID = $json_body->assignmentID;
	$peerMatches = $json_body->peerMatches;
	$getSubmissionIds = $db->prepare("SELECT submissionid FROM PEER_REVIEW_ASSIGNMENT_SUBMISSIONS where authorID = ? and assignmentID = ?");
	$checkForMatch = $db->prepare("SELECT matchID FROM PEER_REVIEW_ASSIGNMENT_MATCHES where submissionID=? AND reviewerID = ?;");
	$insertMatch = $db->prepare("INSERT INTO PEER_REVIEW_ASSIGNMENT_MATCHES (submissionID, reviewerID, instructorForced, calibrationState) values (?,?,0,'none')");
	foreach ($peerMatches as $peerMatch) {
			$db->beginTransaction();
			$studentID = $peerMatch->studentID;
			$getSubmissionIds->execute(array($studentID, $assignmentID));
			$matchingList = $peerMatch->matchList;
			$res  = $getSubmissionIds->fetch();
			//TODO: do we really need this?? check the type output for pdo object fetch()
			//TODO: this should probably be an error, return an error code
			if ($res) {
				$submissionID = $res->submissionID;
			} else {
				$db->commit();
				continue;
			}

			foreach($matchingList as $matchedReviewer) {
				$checkForMatch->execute(array($submissionID, $matchedReviewer));
				
				if (!$checkForMatch->fetch()){
					$insertMatch->execute(array($submissionID, $matchedReviewer));
				}
			}
			 
			$db->commit();
	}

})->add($jsonDecodeMW);


/*
$app->post('/peermatch/delete', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$schema = decode_json_throw_errors(file_get_contents('./peermatch_json/delete/request.json'));
	$validator = new League\JsonGuard\Validator($json_body, $schema);
	/*
	if ($validator->fails()) {
		print_r($validator->errors());
		return NULL;
	}
	$db = $dataMgr->getDatabase();
	$assignmentID = $json_body->assignmentID;
	$getAllMatches = $db->prepare("SELECT * from peer_review_assignment_submissions where assignmentID = ?");
	$getSubmissionIds = $db->prepare("SELECT submissionid FROM PEER_REVIEW_ASSIGNMENT_SUBMISSIONS where authorID = ? and assignmentID = ?");
	$checkForMatch = $db->prepare("SELECT matchID FROM PEER_REVIEW_ASSIGNMENT_MATCHES where submissionID=? AND reviewerID = ?;");
}
 */

$app->run();
