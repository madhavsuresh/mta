<?php
require_once("inc/common.php");

try
{
	global $MTA_DATAMANAGER_PDO_CONFIG;
	if(!isset($MTA_DATAMANAGER_PDO_CONFIG["dsn"])) { die("PDO Data manager needs a DSN"); }
	if(!isset($MTA_DATAMANAGER_PDO_CONFIG["username"])) { die("PDODataManager needs a database user name"); }
	if(!isset($MTA_DATAMANAGER_PDO_CONFIG["password"])) { die("PDODataManager needs a database user password"); }
	
	$db = new PDO($MTA_DATAMANAGER_PDO_CONFIG["dsn"],
                $MTA_DATAMANAGER_PDO_CONFIG["username"],
                $MTA_DATAMANAGER_PDO_CONFIG["password"],
                array(PDO::ATTR_PERSISTENT => true));
		
	require_once(MTA_ROOTPATH.'autogradeandassignmarkers.php');
	
}catch(Exception $e) {
    render_exception_page($e);
}

?>

