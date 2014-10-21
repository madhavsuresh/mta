<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

try
{
	$globalDataMgr = new PDODataManager();
	$recentPeerReviewAssignments = $globalDataMgr->getRecentPeerReviewAssignments();
	require_once(MTA_ROOTPATH.'cronjobs/copyindependentsfromprevious.php');
	require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromscores.php');
}catch(Exception $e) {
	
}

?>

