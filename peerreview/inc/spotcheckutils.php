<?php

function spotCheck(/*StdClass*/ $submissions, $fraction)
{
	if($fraction > 1)
		throw new Exception('Spot check fraction is greater than 1');
	$num = sizeof($submissions);
	$count = (int) $num * $fraction;
	
	$subsToSpotCheck = array();
	
	$hotpotato_init = $num;
	$hotpotato = $hotpotatoinit;
	$index = 0;
	
	while($count > 0)
	{
		foreach($submissions as $key => $submission)
		{
			$hotpotato -= $submissions[$index]->weight;
			if($hotpotato <= 0)
			{
				$hotpotato = $hotpotato_init;
				$subsToSpotCheck[] = $submission;
				unset($submissions[$key]);
				$count--;
			}
		}
	}
}

?>