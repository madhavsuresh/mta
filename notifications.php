<?php

$notifications = $dataMgr->getNotifications();

$translation = array("autogradeandassign"=>"Autograded and Assigned");

foreach($notifications as $notification)
{
	if($notification->seen)
		continue;
	$bg = ($notification->success) ? '#ABFFB5' : 'FF8989';
	$age = ($NOW - $notification->dateRan);
	$units = "seconds";
	if($age > 6000)
	{
		$age = round($age/3600);
		$units = "hours";			
	}
	elseif($age > 60)
	{
		$age = round($age/60);
		$units = "minutes";
	}
	$content .= "<div class='notification' style='background-color:$bg;'>";
        $content .= "<table width='100%'><tr><td class='column1'><h4>".$dataMgr->getAssignmentHeader(new AssignmentID($notification->assignmentID))->name."</h4></td>
    	<td class='column2'>".$translation[$notification->job]."</td>
    	<td class='column3'><table width='100%'><td>".$notification->summary."</td> 
    	<td><a target='_blank' href='".get_redirect_url("notificationdetails.php?notificationID=$notification->notificationID")."'><button>Details</button></a></td></table></td>
    	<td class='column4'> $age $units ago</td></tr></table>\n";
    	$content .= "<form class='dismissform' action='dismissnotification.php' method='post'><input type='hidden' name='notificationID' value='$notification->notificationID'></input><input type='submit' value='Dismiss'></input></form>";
	$content .= "</div>";
}
$content .= "<script type='text/javascript'>
	$('.dismissform').submit(function(){
		$.post($(this).attr('action'), $(this).serialize(), function(response){},'json');
		$(this).parent().hide();
		return false;
	});
</script>";


?>