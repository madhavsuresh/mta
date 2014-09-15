<?php

function pickSpotChecks(/*StdClass*/ $submissions, $fraction)
{
	if($fraction > 1)
		throw new Exception('Spot check fraction is greater than 1');
	$num = sizeof($submissions);
	$count = ceil($num * $fraction);
	
	$subsToSpotCheck = array();
	
	$hotpotato_init = $num*3;
	$hotpotato = $hotpotato_init;
	
	$output = "";
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
				$hotpotato = $hotpotato_init;
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
	print_r($output);
	
	print_r($subsToSpotCheck);
	
	return $subsToSpotCheck;
}

?>