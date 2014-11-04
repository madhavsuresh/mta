<?php
require_once("inc/common.php");

try
{
	$dataMgr->requireCourse();
	$authMgr->enforceInstructor();
	
	$markers = $dataMgr->getMarkers();
	//Load target loads for all markers
	$markingLoadMap = array();
	$sumLoad = 0;
	foreach($markers as $markerID)
	{
		$markerLoad = $dataMgr->getMarkingLoad(new UserID($markerID));
		$markingLoadMap[$markerID] = $markerLoad;
		$sumLoad += $markerLoad;
	}
	$targetLoads = array();
	foreach($markers as $markerID)
		$targetLoads[$markerID] = precisionFloat($markingLoadMap[$markerID]/$sumLoad);
	
	print_r("Target Loads is ");
	print_r($targetLoads);
	print_r("<br>");
	
	$unansweredappeals = $dataMgr->assignOldUnansweredAppeals();
	
	print_r("Unanswered appeals are ");
	print_r($unansweredappeals);
	print_r("<br>");
	
	$markerJobs = array();
	foreach($markers as $markerID)
	{
		$markerJobs[$markerID] = 0; 
	}
	$totalJobs = 0;
	
	assignAppeals($unansweredappeals);
	
	print_r($unansweredappeals);

	$content = "";

	render_page();
}catch(Exception $e) {
	render_exception_page($e);
}

function assignAppeals(&$unansweredappeals)
{
	global $dataMgr;
	foreach($unansweredappeals as $assignmentID => $submissions)
	{
		$assignment = $dataMgr->getAssignment(new AssignmentID($assignmentID));
		
		$spotCheckMap = $assignment->getSpotCheckMap();
		$markerToSubmissionsMap = $assignment->getMarkerToSubmissionsMap();
		
		foreach($submissions as $key => $submissionID)
		{
			//Create load defecit array to best select which marker is farthest from his target load and hence should be assigned this appeal
			$loadDefecits = array();
			$totalJobs = array_reduce($markerTasks, function($res, $item){return sizeof($item) + $res;});
			foreach($markers as $key => $markerID)
			{
				if($targetLoads[$markerID] == 0) continue; //under no circumstances should marker with 0 be assigned an appeal even if there is no other non-conflicting marker
				$loadDefecits[$markerID] = $targetLoads[$markerID] - (1.0*sizeof($markerJobs[$markerID]))/($totalJobs+1);
			}
			
			while(sizeof($loadDefecits) < 1)
			{	
				$res = array_keys($loadDefecits, max($loadDefecits));
				$markerID = $res[0];
				//Ensure that the marker to assign the appeal is not the marker of the submission
				if(array_key_exists($submissionID->id, $markerToSubmissionsMap[$markerID]))
				{
					unset($loadDefecits[$markerID]);
					continue;
				}
				//Ensure that the marker to assign the appeal is not the spotchecker of the submission
				if(isset($spotCheckMap[$submissionID->id]) ? ($spotCheckMap[$submissionID->id]->checkerID->id == $markerID) : false)
				{
					unset($loadDefecits[$markerID]);
					continue;
				}
				$dataMgr->assignAppealQuery2 = $dataMgr->db->prepare("assignAppealQuery", "INSERT INTO appeal_assignment (markerID, submissionID) VALUES (:markerID, :submissionID);");
				$dataMgr->assignAppealQuery2->execute(array("submissionID"=>$submissionID->id, "markerID"=>$markerID));
				unset($unansweredappeals[$assignmentID][$key]);
				break;
			}
		}
	}
}
?>

