<?php

$notifications = $dataMgr->getNewNotifications();

$jobnames = array("autogradeandassign"=>"Autograde and Assign", "copyindependentsfromprevious"=>"Copy independents from previous", "computeindependentsfromscores"=>"Compute independents from scores", "computeindependentsfromcalibrations"=>"Compute independents from calibrations", "disqualifyindependentsfromscores"=>"Disqualify independents from scores", "assignreviews"=>"Assign reviews");

$content .= "<a target='_blank' href='".get_redirect_url("allnotifications.php")."'>All Notifications</a>";

foreach($notifications as $notification)
{
	$bg = ($notification->success) ? '#ABFFB5' : '#F6CED8';
	$age = ($NOW - $notification->dateRan);
	$unit = "second";
	if($age > 82800)
	{
		$age = round($age/86400);
		$unit = "day";	
	}
	elseif($age > 6000)
	{
		$age = round($age/3600);
		$unit = "hour";			
	}
	elseif($age > 60)
	{
		$age = round($age/60);
		$unit = "minute";
	}
	$s = ($age > 1) ? "s" : "";
	$content .= "<div class='notification' style='background-color:$bg;'>";
        $content .= "<table width='100%'><tr><td class='column1'><h4>".$dataMgr->getAssignmentHeader($notification->assignmentID)->name."</h4></td>
    	<td class='column2'>".$jobnames[$notification->job]."</td>
    	<td class='column3'><table width='100%'><td>".$notification->summary."</td> 
    	<td><a target='_blank' href='".get_redirect_url("notificationdetails.php?notificationID=$notification->notificationID")."'><button>Details</button></a></td></table></td>
    	<td class='column4'> $age $unit$s ago</td></tr></table>\n";
    	$content .= "<form class='dismissform' action='dismissnotification.php' method='post'><input type='hidden' name='notificationID' value='$notification->notificationID'><input type='hidden' name='dismiss' value='dismiss'></input><input type='submit' value='Dismiss'></input></form>";
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