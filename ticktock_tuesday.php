<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

try
{
	$globalDataMgr = new PDODataManager();
	$recentPeerReviewAssignments = $globalDataMgr->getSubmissionStoppedAssignments();
	require_once(MTA_ROOTPATH.'cronjobs/copyindependentsfromprevious.php');
	require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromscores.php');
	require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromcalibrations.php');
	require_once(MTA_ROOTPATH.'cronjobs/disqualifyindependentsfromscores.php');
	require_once(MTA_ROOTPATH.'cronjobs/assignreviews.php');
}catch(Exception $e) {
	
}

?>

