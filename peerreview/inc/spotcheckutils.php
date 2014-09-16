<?php

function pickSpotChecks(/*StdClass*/ $submissions, $fraction)
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
	
	while($count > 0)
	{
		foreach($submissions as $key => $submission)
		{			
			$hotpotato -= $submission->weight;
			if($hotpotato <= 0)
			{
				$hotpotato = rand(0, ceil($weightsum));
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
	
	return $subsToSpotCheck;
}

?>