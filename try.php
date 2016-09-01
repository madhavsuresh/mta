<?php 
require_once("inc/common.php");

try {
	print $dataMgr->courseName;
	$assignNum = $_GET["num"];
	$assignments = $dataMgr->getAssignments();
	$db = $dataMgr->getDatabase();
	print gettype($assignments);
	print count($assignments);
	foreach ($assignments as $assignment){
		print gettype($assignment);
		print "<br/>";
	}
	print "hello";
	$db->beginTransaction();
	$randstr = strval(rand());
	$testing = $db->prepare("INSERT into status values (?);");
	$testing->execute(array($randstr));
	$db->commit();


	$sh = $db->prepare("SELECT authorid, submissionid from peer_review_assignment_submissions where assignmentID=?");
	$sh->execute(array($assignNum));
	$submissions = $sh->fetchall();
	$authorIDs = array();
	$submissionMap = array();
	$submissionIDs = array();
	foreach($submissions as $submission){
		array_push($authorIDs, $submission->authorID);
		$submissionMap[$submission->submissionID] = $submission->authorID;
		array_push($submissionIDs, $submission->submissionID);
	}
	print_r($authorIDs);

	$shuffledIDs = $authorIDs;
	$matchMap = array();
	foreach($submissionIDs as $submissionID){
		shuffle($shuffledIDs);
		if ($shuffledIDs[0] == $submissionID) {
			$shuffledIDs[0] = $shuffledIDs[1];
		}
		$matchMap[$submissionID][0]  = $shuffledIDs[0];
	}

	/*
	$db->beginTransaction();
	$db->commit();
	 */
	print "<br/>";
	print "<br/>**";
	print_r($submissionIDs);

	foreach ($submissionIDs as $submissionID) {
		foreach ($matchMap[$submissionID] as $match) {
			$db->beginTransaction();
			$insert = $db->prepare("INSERT into peer_review_assignment_matches (submissionID, reviewerID, instructorForced, calibrationState) values (?,?,0,'none')");
			$insert->execute(array($submissionID, $match));
			$db->commit();

		}
	}
	foreach($matchMap as $match) {
		print_r($match);
	}


	
	$numResults = count($authorIDs);
	$authorIDSorted = $authorIDs;
	asort($authorIDSorted);

	foreach (range(0, $numResults-1) as $index) {

		shuffle($shuffledIDs);
		$matchArray[$index][0] = $shuffledIDs[0];
		$matchArray[$index][1] = $shuffledIDs[1];
		//$matchArray[$index][2] = $shuffledIDs[2];
	}
	print "<br/>";
	print $numResults;
	print "<br/>";
	print_r($matchArray);

} catch (Exception $e) {
	print $e->getMessage();
}



?>
