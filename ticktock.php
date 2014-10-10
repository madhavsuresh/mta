<?php
require_once("inc/common.php");
try
{
	$globalDataMgr = new GlobalDataManager();
		
	//require_once(MTA_ROOTPATH.'autogradeandassignmarkers.php');
}catch(Exception $e) {
    render_exception_page($e);
}

?>

