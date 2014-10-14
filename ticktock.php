<?php
require_once("inc/common.php");
require_once("inc/datamanagers/globaldatamanager.php");

try
{
	require_once(MTA_ROOTPATH.'autogradeandassignmarkers.php');
}catch(Exception $e) {
    render_exception_page($e);
}

?>

