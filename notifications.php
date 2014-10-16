<?php

//$notifications = $dataMgr->getNotifications();

foreach($notifications as $notification)
{
	$content .= "";
}

$content .= "<div style='margin-bottom:20px'>";
$content .= "<h1>Notifications</h1>\n";
if($reviewTasks)
{
	$bg = '';
	foreach($reviewTasks as $reviewTask)
	{
		$bg = ($bg == '#E0E0E0' ? '' : '#E0E0E0');
		$content .= "<div class='TODO' style='background-color:$bg;'>";
		$content .= $reviewTask->html;
		$content .= "</div>";
	}
}
else
	$content .= "You currently have no new notifications";
$content .= "</div>";
?>