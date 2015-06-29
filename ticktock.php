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
	$globalDataMgr = new PDODataManager();
	$submissionStoppedAssignments = $globalDataMgr->getSubmissionStoppedAssignments();
	
	$assignReviewsPeerReviewJob = new AssignReviewsPeerReviewCronJob();
	$copyIndependentsFromPreviousJob = new CopyIndependentsFromPreviousCronJob();
	$computeIndependentsFromScoresJob = new ComputeIndependentsFromScoresCronJob();
	$computeIndependentsFromCalibrationsJob = new ComputeIndependentsFromCalibrationsCronJob();
	$disqualifyIndependentsFromScoresJob = new DisqualifyIndependentsFromScoresCronJob();

	foreach($submissionStoppedAssignments as $assignmentID)
	{
		/*$copyIndependentsFromPreviousJob->executeAndGetResult($assignmentID, $globalDataMgr);
		$computeIndependentsFromScoresJob->executeAndGetResult($assignmentID, $globalDataMgr);
		$computeIndependentsFromCalibrationsJob->executeAndGetResult($assignmentID, $globalDataMgr);
		$disqualifyIndependentsFromScoresJob->executeAndGetResult($assignmentID, $globalDataMgr);*/
		$assignReviewsPeerReviewJob->executeAndGetResult($assignmentID, $globalDataMgr);
	}

	$reviewStoppedAssignments = $globalDataMgr->getReviewStoppedAssignments();

	$autogradeAndAssignMarkersJob = new AutogradeAndAssignMarkersCronJob();
	
	foreach($reviewStoppedAssignments as $assignmentID)
	{
		$autogradeAndAssignMarkersJob->executeAndGetResult($assignmentID, $globalDataMgr);
	}
	
}catch(Exception $e) {
	
}

?>

