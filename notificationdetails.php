<?php
require_once("inc/common.php");
try
{
	$content = "";
	
    if(array_key_exists("notificationID", $_GET))
    {
		$notification = $dataMgr->getNotification($_GET["notificationID"]);
		$content .= $notification->details;
	}
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
