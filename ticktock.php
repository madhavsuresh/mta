<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

try
{
	$globalDataMgr = new PDODataManager();	
	require_once(MTA_ROOTPATH.'autogradeandassignmarkers.php');
}catch(Exception $e) {
    render_exception_page($e);
}

?>

