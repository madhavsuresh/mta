<?php
require '../vendor/autoload.php';
require_once("../inc/common.php");
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \JsonSchema\Validator as JsonValidator;

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;
$container = new \Slim\Container;
/*
$container['schemaArray'] =  function() {
	$schemaArray = [];
//	$schemaArray['/peermatch/get'] = decode_json_throw_errors(file_get_contents('schemas/peermatch/get.schema'));
	return $schemaArray;
};
 */
//$app = new \Slim\App($container,["settings" => $config]);
$app = new \Slim\App(["settings"=>$config]);



$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
});

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
	$schema = json_decode(file_get_contents('./peermatch/get/request.json'));
	$validator = new League\JsonGuard\Validator($json_body, $schema);
	if ($validator->fails()) {
		print_r($validator->errors());
	} else {
		echo "not";
	}
	//$schemaArray = $myService->schemaArray;
})->add($jsonDecodeMW);

$app->get('/peermatch/create', function (Request $request, Response $response) {

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
