<?php
require_once("inc/common.php");
try
{
    if(array_key_exists("notificationID", $_POST))
    {
    	if(array_key_exists("renew", $_POST))
			$dataMgr->renewNotification($_POST["notificationID"]);
		elseif(array_key_exists("dismiss", $_POST))
			$dataMgr->dismissNotification($_POST["notificationID"]);
		else
			throw new Exception("This page was accessed incorrectly");
	}
	throw new Exception("Missing notification ID in post");
}catch(Exception $e){
    render_exception_page($e);
}


?>
