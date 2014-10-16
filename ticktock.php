<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

try
{
	$globalDataMgr = new PDODataManager();	
	require_once(MTA_ROOTPATH.'autogradeandassignmarkers.php');
}catch(Exception $e) {
	$html = "Something's wrong";
	$globalDataMgr->createNotification($assignmentID, 'autogradeandassign', 0, $html);
}

?>

