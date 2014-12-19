<?php
require_once("peerreview/inc/calibrationutils.php");

require_once(MTA_ROOTPATH.'cronjobs/copyindependentsfromprevious.php');
require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromscores.php');
require_once(MTA_ROOTPATH.'cronjobs/computeindependentsfromcalibrations.php');
require_once(MTA_ROOTPATH.'cronjobs/disqualifyindependentsfromscores.php');
require_once(MTA_ROOTPATH.'cronjobs/assignreviews.php');

require_once(MTA_ROOTPATH.'cronjobs/autogradeandassignmarkers.php');

class TickTockScript extends Script
{
	
	function getName()
    {
        return "TickTock";
    }
    function getDescription()
    {
        return "Script to activate cron jobs";
    }
	function getFormHTML()
    {
        return "(None)";
    }
    function hasParams()
    {
        return false;
    }
    function executeAndGetResult()
    {
		try
		{
			global $dataMgr;
			$submissionStoppedAssignments = $dataMgr->getSubmissionStoppedAssignments();
			
			$assignReviewsPeerReviewJob = new AssignReviewsPeerReviewCronJob();
			$copyIndependentsFromPreviousJob = new CopyIndependentsFromPreviousCronJob();
			$computeIndependentsFromScoresJob = new ComputeIndependentsFromScoresCronJob();
			$computeIndependentsFromCalibrationsJob = new ComputeIndependentsFromCalibrationsCronJob();
			$disqualifyIndependentsFromScoresJob = new DisqualifyIndependentsFromScoresCronJob();
		
			foreach($submissionStoppedAssignments as $assignmentID)
			{
				$copyIndependentsFromPreviousJob->executeAndGetResult($assignmentID, $dataMgr);
				$computeIndependentsFromScoresJob->executeAndGetResult($assignmentID, $dataMgr);
				$computeIndependentsFromCalibrationsJob->executeAndGetResult($assignmentID, $dataMgr);
				$disqualifyIndependentsFromScoresJob->executeAndGetResult($assignmentID, $dataMgr);
				//$assignReviewsPeerReviewJob->executeAndGetResult($assignmentID, $dataMgr);
			}
			
			$reviewStoppedAssignments = $dataMgr->getReviewStoppedAssignments();

			$autogradeAndAssignMarkersJob = new AutogradeAndAssignMarkersCronJob();
			foreach($reviewStoppedAssignments as $assignmentID)
			{
				$autogradeAndAssignMarkersJob->executeAndGetResult($assignmentID, $dataMgr);
			}
			
		}catch(Exception $e) {
			
		}
	}
}
?>