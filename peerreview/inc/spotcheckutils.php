<?php

function pickSpotChecks(/*StdClass*/ $submissions, $fraction)
{
	if($fraction > 1)
		throw new Exception('Spot check fraction is greater than 1');
	$num = sizeof($submissions);
	$count = (int) $num * $fraction;
	
	$subsToSpotCheck = array();
	
	$hotpotato_init = $num*2;
	$hotpotato = $hotpotato_init;
	
	$output = "";
	
	$output .= "We need $count spot checks <table>";
	while($count > 0)
	{
		//visualization
		foreach($submissions as $key => $submission)
		{
			$output .= "<tr><td>";
			foreach($submissions as $key_ => $submission_)
			{
				$output .= "$submission_->submissionID => $submission_->weight, ";
			}
			$output .= "</td>";
			$output .= "<td> $hotpotato - $submission->weight = ".($hotpotato - $submission->weight)."</td></tr>";
			
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