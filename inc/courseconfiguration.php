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
	public $highMarkThreshold;
	public $highMarkBias;
	public $calibrationThreshold;
	public $calibrationBias;
	
	public $scoreWindowSize;
	public $scoreThreshold;
	
	public $disqualifyWindowSize;
	public $disqualifyThreshold;
	
	function __construct()
    {
    	global $dataMgr;
		$this->courseID = $dataMgr->courseID;
		$this->windowSize = 4;
		$this->numReviews = 3;
		$this->scoreNoise = 0.01;
		$this->maxAttempts = 20;
		$this->numCovertCalibrations = 0;
		$this->exhaustedCondition = "extrapeerreview";
		
		$this->minReviews = 3;
		$this->spotCheckProb = 0.25;
		$this->highMarkThreshold = 80;
		$this->highMarkBias = 2;
		$this->calibrationThreshold = 7.5;
		$this->calibrationBias = 1.5;
	
		$this->scoreWindowSize = 4;
		$this->scoreThreshold = 80;
		
		$this->disqualifyWindowSize = 4;
		$this->disqualifyThreshold = 80;
	}
}

