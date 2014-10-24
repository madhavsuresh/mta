<?php

class CourseConfiguration
{
	public $windowSize;
	public $numReviews;
	public $scoreNoise;
	public $maxAttempts;
	public $numCovertCalibrations;
	public $exhaustedCondition;
	
	public $minReviews;
	public $spotCheckProb;
	public $spotCheckThreshold;
	public $highMarkBias;
	public $calibThreshold;
	public $calibBias;
	
	function __construct()
    {
    	$courseID = $dataMgr->courseID;
		$windowSize = 0;
		$numReviews = 0;
		$scoreNoise = 0;
		$maxAttempts = 0;
		$numCovertCalibrations = 0;
		$exhaustedCondition = "";
		
		$minReviews= 0;
		$spotCheckProb= 0;
		$spotCheckThreshold= 0;
		$highMarkBias= 0;
		$calibThreshold= 0;
		$calibBias= 0;
	}
}

