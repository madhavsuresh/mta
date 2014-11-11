<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

require_once(MTA_ROOTPATH.'cronjobs/copyindependentsfromprevious.php');
require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromscores.php');
require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromcalibrations.php');
require_once(MTA_ROOTPATH.'cronjobs/disqualifyindependentsfromscores.php');
//require_once(MTA_ROOTPATH.'cronjobs/oldassignreviews.php');
require_once(MTA_ROOTPATH.'cronjobs/newassignreviews.php');

require_once(MTA_ROOTPATH.'cronjobs/autogradeandassignmarkers.php');

try
{
	$globalDataMgr = new PDODataManager();
	
	$assignReviewsPeerReviewJob = new AssignReviewsPeerReviewCronJob();

	$results = array();
	
	for($i = 0; $i < 100; $i++)
	{
		$results[] = $assignReviewsPeerReviewJob->executeAndGetResult(new AssignmentID(116), $globalDataMgr);
	}
	
	$content = "<h1>Results</h1>";
	
	foreach($results as $result)
		//$content .= $result->success." - ".$result->details."<br>";
		$content .= $result->success."<br>";
	
	render_page();
}catch(Exception $e) {
	
}

?>

