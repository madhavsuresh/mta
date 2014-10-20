<?php
require_once("inc/common.php");
try
{
    if(array_key_exists("notificationID", $_POST))
    {
		$dataMgr->dismissNotification($_POST["notificationID"]);
	}
}catch(Exception $e){
    render_exception_page($e);
}


?>
