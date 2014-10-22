<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

try
{
	$globalDataMgr = new PDODataManager();
	$recentPeerReviewAssignments = $globalDataMgr->getReviewStoppedAssignments();
	require_once(MTA_ROOTPATH.'cronjobs/autogradeandassignmarkers.php');
}catch(Exception $e) {
	
}

?>

