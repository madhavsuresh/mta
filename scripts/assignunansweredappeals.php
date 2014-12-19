<?php
require_once("inc/common.php");

class AssignUnansweredAppealsScript extends Script
{

	function getName()
    {
        return "Assign Unanswered Appeals";
    }
    function getDescription()
    {
        return "Assign unanswered appeals from all assignments";
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
		global $dataMgr;
			
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
		
		$unansweredappeals = $dataMgr->getOldUnansweredAppeals();
		
		$markerJobs = array();
		foreach($markers as $markerID)
		{
			$markerJobs[$markerID] = 0; 
		}
		$totalJobs = 0;
		
		$unassignedappeals = array();
		
		foreach($unansweredappeals as $assignmentID => $submissions)
		{
			$assignment = $dataMgr->getAssignment(new AssignmentID($assignmentID));
			
			$spotCheckMap = $assignment->getSpotCheckMap();
			$markerToSubmissionsMap = $assignment->getMarkerToSubmissionsMap();
			
			foreach($submissions as $key => $submissionID)
			{
				//Create load defecit array to best select which marker is farthest from his target load and hence should be assigned this appeal
				$loadDefecits = array();
				foreach($markers as $key => $markerID)
				{
					if($targetLoads[$markerID] == 0) continue; //under no circumstances should marker with 0 be assigned an appeal even if there is no other non-conflicting marker
					$loadDefecits[$markerID] = $targetLoads[$markerID] - (1.0*$markerJobs[$markerID])/($totalJobs+1);
				}
				while(1)
				{
					if(sizeof($loadDefecits) < 1)
					{
						if(!array_key_exists($assignmentID, $unassignedappeals))
							$unassignedappeals[$assignmentID] = array();
						$unassignedappeals[$assignmentID][] = $submissionID; 	
						break;
					}	
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
					$markerJobs[$markerID]++;
					$totalJobs++;
					$dataMgr->assignAppeal($submissionID, new UserID($markerID));
					break;
				}
			}
		}
		$userDisplayMap = $dataMgr->getUserDisplayMap();

		$html = "<h1>Unanswered Appeals found from submissions by:</h1>";
		if(empty($unansweredappeals)) $html .= "None.";
		foreach($unansweredappeals as $assignmentID => $submissions)
		{
			$assignment = $dataMgr->getAssignment(new AssignmentID($assignmentID));
			$html .= "<h4>Assignment: ".$assignment->name."</h4><ul>";
			foreach($submissions as $submissionID)
				$html .= "<li>".$userDisplayMap[$assignment->getSubmission($submissionID)->authorID->id]."</li>";
			$html .= "</ul>";
		}
		$html .= "<h1>... and the submissions that could not be assigned</h1>";
		if(empty($unassignedappeals)) $html .= "None.";
		foreach($unassignedappeals as $assignmentID => $submissions)
		{
			$assignment = $dataMgr->getAssignment(new AssignmentID($assignmentID));
			$html .= "<h3>Assignment: ".$assignment->name."</h3><ul>";
			foreach($submissions as $submissionID)
				$html .= "<li>".$userDisplayMap[$assignment->getSubmission($submissionID)->authorID->id]."</li>";
			$html .= "</ul>";
		}
		
		return $html;
	}

}
?>

