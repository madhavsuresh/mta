<?php

$notifications = $dataMgr->getNotifications();

$content .= "<div style='margin-bottom:20px'>";
$content .= "<h1>Notifications</h1>\n";
if($notifications)
{
	foreach($notifications as $notification)
	{
		$bg = ($notification->success) ? '#ABFFB5' : 'FF8989';
		$success = ($notification->success) ? "Successful" : "Unsuccessful";
		$age = ($NOW - $notification->dateRan)/60;
		if($age > 100)
		{
			$age = round($age);
			$units = "minutes";
		}
		else
		{
			$age = round($age/60);
			$units = "hours";
		}
		$content .= "<div class='TODO' style='background-color:$bg;'>";
            $content .= "<table width='100%'><tr><td class='column1'><h4>".$notification->job."</h4></td>
        	<td class='column2'>".$notification->assignmentID."</td>
        	<td class='column3'><table wdith='100%'><td>".$success."</td> 
        	<td><a title='New' target='_blank'><button>Details</button></a></td></table></td>
        	<td class='column4'> $age $units ago</td></tr></table>\n";
		$content .= "</div>";
	}
}
else
	$content .= "You currently have no new notifications";
$content .= "</div>";

?>