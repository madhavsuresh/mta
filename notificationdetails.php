<?php
require_once("inc/common.php");

$jobnames = array("autogradeandassign"=>"Autograde and Assign", "copyindependentsfromprevious"=>"Copy independents from previous", "computeindependentsfromscores"=>"Compute independents from scores", "computeindependentsfromcalibrations"=>"Compute independents from calibrations", "disqualifyindependentsfromscores"=>"Disqualify independents from scores", "assignreviews"=>"Assign reviews");

try
{
	$title .= " | Job Details";
	$dataMgr->requireCourse();
    $authMgr->enforceInstructor();
	
	$content = "";
	
    if(array_key_exists("notificationID", $_GET))
    {
		$notification = $dataMgr->getNotification($_GET["notificationID"]);
		
		$content .= "<h1>".$dataMgr->getAssignmentHeader($notification->assignmentID)->name."</h1>";
		
		if(array_key_exists($notification->job, $jobnames))
			$content .= "<h4>Job Type: ".$jobnames[$notification->job]."</h4>";
		else
			throw new Exception('Unknown notification job name');
		
		$content .= "<h4>Date ran: ".phpDate($notification->dateRan)."</h4>";
		
		$content .= "<h1>Job Details:</h1>";
		
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
