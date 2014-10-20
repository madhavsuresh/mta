<?php
require_once("inc/common.php");
try
{
	$content = "";
	
    if(array_key_exists("notificationID", $_GET))
    {
		$notification = $dataMgr->getNotification($_GET["notificationID"]);
		if($notification->details)
			$content .= $notification->details;
		else
			$content .= "There are no further details to report from this notification."; 
	}
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
