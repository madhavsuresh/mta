<?php
require_once("inc/common.php");

function getAppealsTaskMap(Assignment $assignment)
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

foreach($appealMap as $submissionID => $reviewAppeals)
{
	foreach($markers as $markerID)
	{
		//if($targetLoads[$markerID] == 0)
		//	continue;
		$loadDefecits[$markerID] = $targetLoads[$markerID] - 1.0*$markerSubs[$markerID]/($assignedJobs+1);
	}
	while(1)
	{
		if(!(sizeof($loadDefecits)>0))
		{
			throw new Exception('Somehow a submission has been reviewed and/or spotchecked by all markers');
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
		
		$markerTasks[$markerID][$submissionID] = $reviewAppeals;

		$markerSubs[$markerID]++;
		$assignedJobs++;
		break;
	}
}

return $markerTasks;

}
	
?>