<?php
require_once("inc/common.php");
require_once("inc/datamanagers/pdodatamanager.php");

require_once(MTA_ROOTPATH.'cronjobs/copyindependentsfromprevious.php');
require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromscores.php');
require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromcalibrations.php');
require_once(MTA_ROOTPATH.'cronjobs/disqualifyindependentsfromscores.php');
require_once(MTA_ROOTPATH.'cronjobs/assignreviews.php');

require_once(MTA_ROOTPATH.'cronjobs/autogradeandassignmarkers.php');

try
{
	//$dataMgr = new PDODataManager();
	global $dataMgr;
	$submissionStoppedAssignments = $dataMgr->getSubmissionStoppedAssignments();
	
	$assignReviewsPeerReviewJob = new AssignReviewsPeerReviewCronJob();
	$copyIndependentsFromPreviousJob = new CopyIndependentsFromPreviousCronJob();
	$computeIndependentsFromScoresJob = new ComputeIndependentsFromScoresCronJob();
	$computeIndependentsFromCalibrationsJob = new ComputeIndependentsFromCalibrationsCronJob();
	$disqualifyIndependentsFromScoresJob = new DisqualifyIndependentsFromScoresCronJob();

	foreach($submissionStoppedAssignments as $assignmentID)
	{
		/*$copyIndependentsFromPreviousJob->executeAndGetResult($assignmentID, $dataMgr);
		$computeIndependentsFromScoresJob->executeAndGetResult($assignmentID, $dataMgr);
		$computeIndependentsFromCalibrationsJob->executeAndGetResult($assignmentID, $dataMgr);
		$disqualifyIndependentsFromScoresJob->executeAndGetResult($assignmentID, $dataMgr);*/
		$assignReviewsPeerReviewJob->executeAndGetResult($assignmentID, $dataMgr);
	}

	$reviewStoppedAssignments = $dataMgr->getReviewStoppedAssignments();

	$autogradeAndAssignMarkersJob = new AutogradeAndAssignMarkersCronJob();
	
	foreach($reviewStoppedAssignments as $assignmentID)
	{
		$autogradeAndAssignMarkersJob->executeAndGetResult($assignmentID, $dataMgr);
	}
	
}catch(Exception $e) {
	
}

?>

