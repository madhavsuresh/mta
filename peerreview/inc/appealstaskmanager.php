<?php
require_once("inc/common.php");

function assignAppeal(Assignment $assignment, AppealMessage $appealmessage, Submission $submission)
{
	global $dataMgr;
		
	$markerToAppealedSubmissionsMap = $assignment->getMarkerToAppealedSubmissionsMap();
	
	$markers = $dataMgr->getMarkers();
	
	$markerTasks = array();
	
	//Fill-in marker tasks with current appeal assignments
	foreach($markers as $markerID)
	{
		if(array_key_exists($markerID, $markerToAppealedSubmissionsMap))
			$markerTasks[$markerID] = $markerToAppealedSubmissionsMap[$markerID];
		else
			$markerTasks[$markerID] = array();
	}
	
	//Load spot check map for avoiding spotchecking and answering appeals from the same submission
	$spotCheckMap = $assignment->getSpotCheckMap();
	
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
	
	//Create load defecit array to best select which marker is farthest from his target load and hence should be assigned this appeal
	$loadDefecits = array();
	$totalSubs = array_reduce($markerToAppealedSubmissionsMap, function($res, $item){return sizeof($item) + $res;});
	foreach($markers as $markerID)
	{
		if($targetLoads[$markerID] == 0)
			continue;
		$loadDefecits[$markerID] = $targetLoads[$markerID] - (1.0*sizeof($markerTasks[$markerID]))/$totalSubs;
	}
	
	while(1)
	{
		if(sizeof($loadDefecits) < 1)
		{	
			$markerTasks[0][$submissionID] = $appeals;
			break;
		}
		$res = array_keys($loadDefecits, max($loadDefecits));
		$markerID = $res[0];
		if(array_key_exists($submissionID, $markerToAppealedSubmissionsMap[$markerID]))
		{
			unset($loadDefecits[$markerID]);
			continue;
		}
		if((isset($spotCheckMap[$submissionID])) ? ($spotCheckMap[$submissionID]->checkerID->id == $markerID) : false)
		{
			unset($loadDefecits[$markerID]);
			continue;
		}
		
		$dataMgr->assignAppeal($appealmessage, new UserID($markerID));
		break;
	}

}

/*function getAppealsTaskMap(Assignment $assignment)
{
global $dataMgr;
	
$markerTasks = array();
foreach($markers as $markerID)
	$markerTasks[$markerID] = array();	

$appealMap = $assignment->getAppealMapBySubmission();
if(!(sizeof($appealMap)>0)) 
	return $markerTasks;

$spotCheckMap = $assignment->getSpotCheckMap();
$markerToSubmissionsMap = $assignment->getMarkerToSubmissionsMap();
//print_r($appealMap);
//print_r($markAppealMap);

$markers = $dataMgr->getMarkers();

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

$markerSubs = array();
foreach($markers as $markerID)
	$markerSubs[$markerID] = 0;

$assignedJobs = 0;
$loadDefecits = array();

foreach($appealMap as $submissionID => $appeals)
{
	foreach($markers as $markerID)
	{
		if($targetLoads[$markerID] == 0)
			continue;
		$loadDefecits[$markerID] = $targetLoads[$markerID] - 1.0*$markerSubs[$markerID]/($assignedJobs+1);
	}
	while(1)
	{
		if(!(sizeof($loadDefecits)>0))
		{
			//throw new Exception('Somehow a submission has been reviewed and/or spotchecked by all available markers');
			
			//Just going to leave it unassigned
			//break;
			
			$markerTasks[0][$submissionID] = $appeals;
			break;
		}
		$res = array_keys($loadDefecits, max($loadDefecits));
   		$markerID = $res[0];
		if(array_key_exists($submissionID, $markerToSubmissionsMap[$markerID]))
		{
			unset($loadDefecits[$markerID]);
			continue;
		}
		if((isset($spotCheckMap[$submissionID])) ? ($spotCheckMap[$submissionID]->checkerID->id == $markerID) : false)
		{
			unset($loadDefecits[$markerID]);
			continue;
		}
		
		$markerTasks[$markerID][$submissionID] = $appeals;

		$markerSubs[$markerID]++;
		$assignedJobs++;
		break;
	}
}

return $markerTasks;

}*/
	
?>