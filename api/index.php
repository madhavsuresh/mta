<?php
require '../vendor/autoload.php';
require_once("../inc/common.php");
require_once("api_lib.php");

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \JsonSchema\Validator as JsonValidator;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
$config['determineRouteBeforeAppMiddleware'] = true;
$container = new \Slim\Container;
$container['dataMgr'] = $dataMgr;
$container['settings']['displayErrorDetails'] = true;
$app = new \Slim\App($container); //$container); 

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

function getSchema($route_name) { 
	list($data_type, $endpoint) = explode(":", $route_name, 2);
	$full_schema = decode_json_throw_errors(file_get_contents('./json/' . $data_type . '.json'));
	$request_schema ='';
	$response_schema = '';
	foreach ($full_schema->links as $endpoint_schema) { 
		//TODO: this is ugly
		if (strcmp(str_replace('"','',json_encode($endpoint_schema->title)), $endpoint) == 0) { 
		  $request_schema = $endpoint_schema->schema;
		  $response_schema = $endpoint_schema->targetSchema;
		}
	}
	return array($request_schema, $response_schema);
}

$jsonDecodeMW = function ($request, $response, $next) {
	$json_body = decode_json_throw_errors($request->getBody());
	$request = $request->withAttribute('requestDecodedJson', $json_body);
	$response = $next($request, $response);
	return $response;	
};

$jsonvalidateMW = function ($request, $response, $next) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$route_name = $request->getAttribute('route')->getName();
	//TODO: maybe surround this with a try/catch
	//$request_schema = decode_json_throw_errors(file_get_contents('./json/' . $route_name . 'request.json'));
	list($request_schema, $response_schema) = getSchema($route_name);
	$validator = new League\JsonGuard\Validator($json_body, $request_schema);
	if ($validator->fails()) {
		print_r($validator->errors());
		return NULL;
	}
	$next($request, $response);
	//$response_schema = decode_json_throw_errors(file_get_contents('./json/' . $route_name . 'response.json'));
	
	if(json_decode($response->getBody())){
		$validator = new League\JsonGuard\Validator(json_decode($response->getBody()), $response_schema);
		if ($validator->fails()) {
			print_r($validator->errors());
			return NULL;
		}
	}
	return $response;
};


################# COURSE ########################

$app->get('/course/get', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = json_decode($request->getBody());
    $params = (array) $json_body;
    if (isset($params['courseID'])) {	
		$courseID = new CourseID($params['courseID']);
		$dataMgr->setCourseFromID($courseID);
		$returnVal = $dataMgr->getCourseInfo($courseID);
	}
	else {
		$returnVal = $dataMgr->getCourses();
	}
	return $response->withJson($returnVal);
});

$app->post('/course/create', function (Request $request, Response $response) use ($dataMgr) {
    # needs JSON validation
    //TODO ADD ERROR CATCHING
    $params = $request->getBody();
	$params = json_decode($params, true);
 
	$dataMgr->createCourse($params['name'], $params['displayName'], $params['authType'], $params['registrationType'], isset_bool($params['browsable']), $params['gracePeriod']);
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
    $dataMgr->setCourseInfo($courseID, $courseInfo['name'], $courseInfo['displayName'], $courseInfo['authType'], $courseInfo['registrationType'], isset_bool($courseInfo['browsable'], 
	    $courseInfo['gracePeriod']));
	 
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

####################### USERS #################################

$app->get('/user/get_tas_from_courseid', function (Request $request, Response $response) {
    $json_body = $request->getAttribute('requestDecodedJson');
    $dataMgr = $this->dataMgr;
    $courseID = $json_body->courseID;
    $markingLoad = 0;
    if (isset($json_body->markingLoad)) {
	    $markingLoad = $json_body->markingLoad;
    }
    $db = $dataMgr->getDatabase();
    $sh = $db->prepare("SELECT userID FROM users WHERE  userType='instructor'and markingLoad>=? AND courseID=?;");
    $sh->execute(array($markingLoad, $courseID));
    $markers = array();
    while($res = $sh->fetch()) {
      $markers[] = (int)$res->userID;
    }
    $return_array['taIDs'] = $markers;
    $new_response = $response->withJson($return_array);
    return $new_response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('user:tacourseid');

$app->post('/user/create', function (Request $request, Response $response) use ($dataMgr) {
    //TODO ADD ERROR CATCHING
    $params = $request->getBody();
    $params = json_decode($params, true);
    $courseID = new CourseID($params['courseID']);
    $dataMgr->setCourseFromID($courseID);
	$authMgr = $dataMgr->createAuthManager();

	foreach($params['users'] as $user) {
		# markingLoad?
		$dataMgr->addUser($user['username'], $user['firstName'], $user['lastName'], $user['studentID'], $user['userType']);
		#$authMgr->addUserAuthentication($user['username'], $user['password']);
	}
 
    return $response->withJson($dataMgr->getUsers());
});

$app->post('/user/update', function (Request $request, Response $response) use ($dataMgr) {
    //TODO ADD ERROR CATCHING
    $params = $request->getBody();
	$params = json_decode($params, true);
    $courseID = new CourseID($params['courseID']);
    $dataMgr->setCourseFromID($courseID);
	
	foreach ($params['users'] as $user) {
		if($dataMgr->isUserByName($user['username'])) {
			$user_id = $dataMgr->getUserID($user['username']);
			$student_info = (array) $dataMgr->getUserInfo($user_id);
			foreach ($user as $key => $value) {
				if($key != 'username' && !empty($user[$key])) {
					$student_info[$key] = $user[$key];	
				}
			}
			$dataMgr->updateUser($user_id, $student_info['username'], $student_info['firstName'], $student_info['lastName'], $student_info['studentID'], $student_info['userType']);
		}
	}
 
    return $response->withJson($dataMgr->getUsers());
});


$app->post('/user/delete', function (Request $request, Response $response) use ($dataMgr) {
    //TODO ADD ERROR CATCHING
    $params = $request->getBody();
	$params = json_decode($params, true);
    $courseID = new CourseID($params['courseID']);
    $dataMgr->setCourseFromID($courseID);

	for($x = 0; $x < count($params['users']); $x++) {
		if($dataMgr->isUserByName($params['users'][$x])) {
			$user_id = $dataMgr->getUserID($params['users'][$x]);
			$dataMgr->dropUser($user_id);
		}
	}
    return $response->withJson($dataMgr->getUsers());
});

$app->get('/user/get', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = json_decode($request->getBody());
    $params = (array) $json_body;
	$student_info = array();
	$courseID = new CourseID($params['courseID']);
	$dataMgr->setCourseFromID($courseID);
	
	if (isset($params['users'])) {
		for($x = 0; $x < count($params['users']); $x++) {
			if($dataMgr->isUser(new UserID((int)$params['users'][$x]))) {
				#$user_id = $dataMgr->getUserID($params['users'][$x]);
				$student_info[] = $dataMgr->getUserInfo(new UserID((int)$params['users'][$x]));
			}
		}
		$return_val	= $student_info;
	}
	else {
		$return_val = $dataMgr->getUsers();
		
	}
	return $response->withJson($return_val);
});


####################### ASSIGNMENTS ###########################

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

})->add($jsonDecodeMW);

$app->post('/assignment/create', function (Request $request, Response $response) use ($dataMgr){
    
    $params = $request->getBody();
    $params = json_decode($params, true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
		$assignment = create_assignment($params);

		$db = $dataMgr->getDatabase();
		//TODO: could be a race condition
		$sh = $db->prepare("SELECT MAX(assignmentID) AS id FROM assignments");
		$sh->execute();
		$newID = $sh->fetch();
		$return_array['assignmentID'] = (int) $newID->id;
	
		return $response->withJson($return_array);

})->setName('assignment:create')->add($jsonvalidateMW)->add($jsonDecodeMW);

$app->post('/assignment/update', function( Request $request, Response $response) use ($dataMgr){
    $json_params = $request->getBody();
    $params = json_decode($json_params,true); 
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = update_assignment($params);
    
})->setName('assignment:update')->add($jsonvalidateMW)->add($jsonDecodeMW);

$app->get('/assignment/courseID_from_assignmentID', function (Request $request, Response $response) { 
  $json_body = $request->getAttribute('requestDecodedJson');
  $dataMgr = $this->dataMgr;
  $db = $dataMgr->getDatabase();
  $assignmentID = $json_body->assignmentID;
  $courseID = getCourseIDFromAssignmentID($db, $assignmentID);
  $return_array['courseID'] = $courseID;
  $new_response = $response->withJson($return_array);
  return $new_response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('assignment:courseIDassignmentID');


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

/*$app->get('/peerreviewscores/get', function(Request $request, Response $response) use($dataMgr){
    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    $review = $assignment->getReview(new MatchID($params['matchID']));
    $newResponse = $response->withJson($review);
    return $newResponse;
});*/

$app->get('/peerreviewscores/get', function(Request $request, Response $response) use($dataMgr){
    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    # $review = $assignment->getReview(new MatchID($params['matchID']));
	$db = $dataMgr->getDatabase();
    $submissionIDs = getSubmissionIDsForAssignment($db, new AssignmentID($params['assignmentID']));
	
	$reviews = array();
	foreach($submissionIDs as $id) {
		$reviews[$id] = $assignment->getReviewsForSubmission(new SubmissionID($id));
	}
    $newResponse = $response->withJson($reviews);
    return $newResponse;
});

############################ GRADES #############################

$app->post('/grades/create', function(Request $request, Response $response) use($dataMgr){
    $params = json_decode($request->getBody(),true);
    $dataMgr->setCourseFromID(new CourseID($params['courseID']));
    $assignment = $dataMgr->getAssignment(new AssignmentID($params['assignmentID']));
    
	foreach($params['grades'] as $value) {
		$assignment->saveSubmissionMark(new Mark($value[1], null, true), new SubmissionID($value[0]));
	}
	
    return $response;
});

$app->get('/grades/submissions', function(Request $request, Response $response) { 
    $dataMgr = $this->dataMgr;
    $db = $dataMgr->getDatabase();
    $res = getAllSubmissionGrades($db);
    $ret_array['scores'] = $res;
    $newResponse = $response->withJson($ret_array);
    return $newResponse;
})->add($jsonvalidateMW)->setName('grades:submissions');

$app->get('/grades/peerreviews', function(Request $request, Response $response) {
    $dataMgr = $this->dataMgr;
    $db = $dataMgr->getDatabase();
    $res = getAllPeerReviewGrades($db);
    $ret_array['scores'] = $res;
    $newResponse = $response->withJson($ret_array);
    return $newResponse;
})->add($jsonvalidateMW)->setName('grades:peerreviews');

############################ STUDENT SIDE TESTING ############### 
$app->post('/makesubmissions', function (Request $request, Response $response) use
    ($dataMgr){#takes in course name and assignment id
        $params = $request->getBody();
        $params = json_decode($params,true);
        mock_submissions($params["courseID"], $params["assignmentID"]); 
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

#/peermatch/get
#TODO: This should be under the users endpoint, not the peermatch endpoint
$app->get('/peermatch/get_submission_ids', function (Request $request, Response $response) {
   $json_body = $request->getAttribute('requestDecodedJson');
	 $dataMgr = $this->dataMgr;
	 $db = $dataMgr->getDatabase();
   $assignmentID = $json_body->assignmentID;
   $submissionIDList =  get_submissions_from_assignment($db, $assignmentID);
   $return_array['submissionList'] = $submissionIDList;
   $new_response = $response->withJson($return_array);
  return $new_response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:get_submission_ids');

$app->get('/peermatch/get_peer_ids', function (Request $request, Response $response) {
   $json_body = $request->getAttribute('requestDecodedJson');
	 $dataMgr = $this->dataMgr;
	 $db = $dataMgr->getDatabase();
   $assignmentID = $json_body->assignmentID;
   $peerIDList =  get_peers_from_assignment($db, $assignmentID);
   $return_array['peerList'] = $peerIDList;
	 $new_response = $response->withJson($return_array);
	 return $new_response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:get_peer_ids');

$app->get('/peermatch/get_peer_and_submission_ids', function (Request $request, Response $response) {
   $json_body = $request->getAttribute('requestDecodedJson');
	 $dataMgr = $this->dataMgr;
	 $db = $dataMgr->getDatabase();
   $assignmentID = $json_body->assignmentID;
   $peerSubmissionPairs =  get_peers_submission_from_assignment($db, $assignmentID);
   $return_array['peerSubmissionPairs'] = $peerSubmissionPairs;
	 #print_r($peerSubmissionPairs);
	 $new_response = $response->withJson($return_array);
	 return $new_response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:get_peer_and_submission_ids');

$app->get('/getmatchesforsubmission', function (Request $request, Response $response) use ($dataMgr) {
	$params = $request->getBody();
	$params = json_decode($params,true);
	$dataMgr->setCourseFromID(new courseID($params['courseID']));
	
	$match_ids = getMatchIDsForSubmission($params["assignmentID"], $params["submissionID"]);
		
	return $response->withJson($match_ids);

});

$app->get('/peermatch/get',  function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$assignmentID = $json_body->assignmentID;
	//TODO: handle exceptions and errors around the database calls
	$db = $dataMgr->getDatabase();
	$sh = $db->prepare("SELECT  peer_review_assignment_submissions.submissionID, peer_review_assignment_matches.reviewerID,
		peer_review_assignment_matches.matchID
       			FROM peer_review_assignment_submissions JOIN peer_review_assignment_matches ON
       		peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID
       		WHERE peer_review_assignment_submissions.assignmentID = ?
       		ORDER BY peer_review_assignment_submissions.submissionID");
	$sh->execute(array($assignmentID));
	//Note, authorID is authorID for submission, 
	//reviewerID is person matched to review submission, by authorID, on assignmentID
	$peerMatches = array();
	while($res = $sh->fetch()) {
		$peerMatches[] = array('submissionID' =>
			(int)$res->submissionID,
			'reviewerID' => (int)$res->reviewerID,
			'matchID' => (int)$res->matchID);
	}
	$return_array['peerMatches'] = $peerMatches;
	$return_array['assignmentID'] = $assignmentID;
	$new_response = $response->withJson($return_array);
	return $new_response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:get');

$app->post('/peermatch/test', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = $request->getAttribute('requestDecodedJson');
 	$peerMatches = $json_body->peerMatches;
	print_r($peerMatches);
	foreach ($peerMatches as $peerMatch) { 
		//verify that submissionID is valid, userID is valid
	}
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:create_bulk');

$app->post('/peermatch/create', function (Request $request, Response $response) use ($dataMgr) {
 	//TODO: change from reviewerID to peerID
	$json_body = $request->getAttribute('requestDecodedJson');
	$db = $dataMgr->getDatabase();
	$assignmentID = $json_body->assignmentID;
	$peerMatch = $json_body->peerMatch;
	$submissionID = $peerMatch->submissionID;  
	$reviewerID = $peerMatch->reviewerID;
	insertSinglePeerMatch($db, $submissionID, $reviewerID, $assignmentID);
	return $response;
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:create');

$app->post('/peermatch/create_bulk', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$db = $dataMgr->getDatabase();
	$assignmentID = $json_body->assignmentID;
	$peerMatches = $json_body->peerMatches;
	foreach ($peerMatches as $peerMatch) { 
		$submissionID = $peerMatch->submissionID;  
		$reviewerID = $peerMatch->reviewerID;
		insertSinglePeerMatch($db, $submissionID, $reviewerID, $assignmentID);
	}
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:create_bulk');

$app->post('/peermatch/delete_all', function (Request $request, Response $response) use ($dataMgr) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$db = $dataMgr->getDatabase();
	$assignmentID = $json_body->assignmentID;
	$db->beginTransaction();
	//$getAllMatches = $db->prepare("SELECT * from PEER_REVIEW_ASSIGNMENT_MATCHES where assignmentID = ?");

	$deleteAllMatches = $db->prepare("DELETE FROM peer_review_assignment_matches
			WHERE submissionID in (
				SELECT peer_review_assignment_submissions.submissionID 
				FROM peer_review_assignment_submissions 
				INNER JOIN peer_review_assignment_matches ON (peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID) 
				WHERE peer_review_assignment_submissions.assignmentID = ?)");
	//$getAllMatches->execute(array($assignmentID));
	//$allMatches = $getAllMatches->fetchAll();
	$deleteAllMatches->execute(array($assignmentID));
	$db->commit();
})->setName('peermatch:delete_all')->add($jsonvalidateMW)->add($jsonDecodeMW);

$app->post('/peermatch/delete_match_bulk', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$matchIDList = $json_body->matchIDList;
	foreach ($matchIDList as $matchID) {
		deleteMatchByID($db, $matchID);
	}
})->add($jsonvalidateMW)->add($jsonDecodeMW)->setName('peermatch:delete_match_bulk');

$app->post('/peermatch/insert_review_mark', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$matchID = $json_body->matchID;
	$score = $json_body->grade;
	insertSingleReviewMark($db, $matchID, $score);
})->add($jsonDecodeMW);


$app->post('/peermatch/insert_review_marks_bulk', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$reviewMarks = $json_body->reviewMarks;
	foreach ($reviewMarks as $reviewMark) {
		$matchID = $reviewMark->matchID;
		$score = $reviewMark->grade;
		insertSingleReviewMark($db, $matchID, $score);
	}
})->add($jsonDecodeMW);

$app->post('/peermatch/swap_peer_review', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$matchID = $json_body->matchID;
	$reviewerToSwapID = $json_body->reviewerToSwapID;
	swapReviewer($db, $matchID, $reviewerToSwapID);
})->add($jsonDecodeMW);

$app->post('/peerreviewscores/create_int', function(Request $request, Response $response) {
	$json_body  = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$matchID = $json_body->matchID;
	$answerInt= $json_body->answerInt;
	$questionID = $json_body->questionID;
	$db = $dataMgr->getDatabase();
        $sh = $db->prepare("INSERT or IGNORE INTO peer_review_assignment_review_answers (matchID, questionID, answerInt, answerText, reviewTimestamp) VALUES (?, ?, ?, ?, ?)");
	$sh->execute(array($matchID, $questionID, $answerInt, NULL, time()));
        $sh = $db->prepare("UPDATE peer_review_assignment_review_answers set answerInt=?, reviewTimestamp=? where matchID = ? and questionID=?");
	$sh->execute(array($answerInt, time(), $matchID, $questionID));
})->add($jsonDecodeMW);

$app->post('/peerreviewscores/create_text', function(Request $request, Response $response) {
	$json_body  = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$matchID = $json_body->matchID;
	$answerText= $json_body->answerText;
	$questionID = $json_body->questionID;
	$db = $dataMgr->getDatabase();
        $sh = $db->prepare("INSERT or IGNORE INTO peer_review_assignment_review_answers (matchID, questionID, answerInt, answerText, reviewTimestamp) VALUES (?, ?, ?, ?, ?)");
	$sh->execute(array($matchID, $questionID, NULL, $answerText, time()));
        $sh = $db->prepare("UPDATE peer_review_assignment_review_answers set answerText=?, reviewTimestamp=? where matchID = ? and questionID=?");
	$sh->execute(array($answerText, time(), $matchID, $questionID));
})->add($jsonDecodeMW);

$app->get('/appeals/get', function(Request $request, Response $response) {
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$sh = $db->prepare('SELECT * from peer_review_assignment_appeal_messages');
	$sh->execute();
	$results = $sh->fetchall();
	$ret = [];
	foreach ($results as $appeal) {
		$appeal->appealMessageID = (int)$appeal->appealMessageID;
		$appeal->matchID = (int)$appeal->matchID;
		$appeal->authorID = (int)$appeal->authorID;
		$appeal->viewedByStudent = (int)$appeal->viewedByStudent;
		$ret[] = $appeal;
	}
	$ret_array = [];
	$ret_array['appeals'] = $ret;
	$new_response = $response->withJson($ret_array);
	return $new_response;
});

$app->post('/appeals/create_appeal_type', function(Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$sh = $db->prepare('insert or ignore into appealtype (value) values (?)');
	$sh->execute(array($json_body->appealType));
})->add($jsonDecodeMW);

########################EVENTS################################################
//TODO: make this not plural
$app->get('/event/get', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$courseID = $json_body->courseID;
    $assignmentID = null;
    if (property_exists($json_body, "assignmentID")) {
        $assignmentID = $json_body->assignmentID;
    }
    $eventList = getEvents($db, $courseID, $assignmentID);
	$return_array['eventList'] = $eventList;
	$new_response = $response->withJson($return_array);
	return $new_response;
})->setName('event:get')->add($jsonvalidateMW)->add($jsonDecodeMW);

$app->post('/event/create', function (Request $request, Response $response) {
	$dataMgr = $this->dataMgr;
	$json_body = $request->getAttribute('requestDecodedJson');
	$j = $json_body;
	$dataMgr->createNotification(new AssignmentID($j->assignmentID), $j->job, $j->success, 
		$j->summary, $j->details);

})->setName('event:create')->add($jsonvalidateMW)->add($jsonDecodeMW);

#################PARTNERS######################

$app->get('/partner/get', function (Request $request, Response $response) {
	$json_body = $request->getAttribute('requestDecodedJson');
	$dataMgr = $this->dataMgr;
	$db = $dataMgr->getDatabase();
	$assignmentID = $json_body->assignmentID;
	$return_array = getPartnerPairsForAssignemnt($db, $assignmentID);
	$new_response = $response->withJson($return_array);
	return $new_response;
})->setName('partner:get')->add($jsonvalidateMW)->add($jsonDecodeMW);


$app->run();
