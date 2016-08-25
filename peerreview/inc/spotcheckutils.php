<?php

function pickSpotChecks(/*StdClass[]*/ $submissions, $fraction)
{
	if($fraction > 1)
		throw new Exception('Spot check fraction is greater than 1');
	$num = sizeof($submissions);
	$count = ceil($num * $fraction);
	
	$subsToSpotCheck = array();
	
	$weightsum = 0;
	foreach($submissions as $submission)
		$weightsum += $submission->weight;
	
	$hotpotato = rand(0, ceil($weightsum));
	
	$output = "";
	$output .= "Total weight is $weightsum. ";
	$output .= "We need $count spot checks out of $num submissions<table>";	
	global $dataMgr;
	
	while($count > 0)
	{
		foreach($submissions as $key => $submission)
		{
			//visualization
			$output .= "<tr><td>";
			foreach($submissions as $key_ => $submission_)
			{
				if($key == $key_)
					$output .= "<strong>".$dataMgr->getUserDisplayName(new UserID($submission_->authorID))." => $submission_->weight</strong>, ";
				else
					$output .= $dataMgr->getUserDisplayName(new UserID($submission_->authorID))." => $submission_->weight, ";
				
				//$output .= "$submission_->authorID => $submission_->weight, ";
			}
			$output .= "</td>";
			$output .= "<td style='border-left: 1px solid #000000;'> $hotpotato - $submission->weight = ".($hotpotato - $submission->weight)."</td></tr>";
			
			$hotpotato -= $submission->weight;
			if($hotpotato <= 0)
			{
				$hotpotato = rand(0, $weightsum);
				$subToSpotCheck = new stdClass();
				$subToSpotCheck->submissionID = $submission->submissionID;
				$subToSpotCheck->authorID = $submission->authorID;
				$subsToSpotCheck[] = $subToSpotCheck;
				unset($submissions[$key]);
				$count--;
				if($count <= 0)
					break;
			}
		}
	}
	
	$output .= "</table>";
	$output .= "We will spot check submissions from ";
	foreach($subsToSpotCheck as $subToSpotCheck)
		$output .= $dataMgr->getUserDisplayName(new UserID($subToSpotCheck->authorID)).", ";
	//print_r($output);
	
	return $subsToSpotCheck;
}

?>

