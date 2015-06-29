<?php
require_once("inc/common.php");
try
{
	$title .= " | Edit Course Configuration";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();
	
	$content = "<h1>Edit Course Configuration</h1>";
	
	if(array_key_exists("save", $_GET)){
		$configuration = new CourseConfiguration();
        
        //Assign Reviews 
        $configuration->windowSize = require_from_post("windowsize");
		if(array_key_exists("assignmentdefaultnumreviews", $_POST))
			$configuration->numReviews = -1;
		else
			$configuration->numReviews = intval(require_from_post("numreviews"));
        $configuration->scoreNoise = floatval(require_from_post("scorenoise"));
        $configuration->maxAttempts = intval(require_from_post("maxattempts"));
		$configuration->numCovertCalibrations = intval(require_from_post("numCovertCalibrations"));
       	$configuration->exhaustedCondition = require_from_post("exhaustedCondition");
		
		//Autograde and assign markers
        $configuration->minReviews = intval(require_from_post("minReviews"));
		$configuration->spotCheckProb = floatval(require_from_post("spotCheckProb"));
        $configuration->highMarkThreshold = floatval(require_from_post("highMarkThreshold"));
        $configuration->highMarkBias = $highMarkBias = floatval(require_from_post("highMarkBias"));
		$configuration->calibrationThreshold = floatval(require_from_post("calibrationThreshold"));
       	$configuration->calibrationBias = floatval(require_from_post("calibrationBias"));
		
		//Comput independents from scores
		$configuration->scoreWindowSize = intval(require_from_post("scorewindowsize"));
		$configuration->scoreThreshold = floatval(require_from_post("scorethreshold"));
		
		//Disqualify independents
		$configuration->disqualifyWindowSize = intval(require_from_post("disqualifywindowsize"));
		$configuration->disqualifyThreshold = floatval(require_from_post("disqualifythreshold"));
		
       	$dataMgr->saveCourseConfiguration($configuration);
		
		foreach($dataMgr->getMarkers() as $markerID)
		{
			$dataMgr->setMarkingLoad(new UserID($markerID), require_from_post("load$markerID"));
		}
		
		$content .= "<span style='color:green'>Course configuration saved.</span>";
	}
	
	try{
		$configuration = $dataMgr->getCourseConfiguration();
	}catch(Exception $e){
		$content .= "<span style='color:red'>You have not set a course configuration yet.</span>";
		$configuration = new CourseConfiguration();
	}
	
	$content .= "<form action='?save=1' method='post'>";
	
	$content .= "<h3>Workflow</h3>";
	$content .= "<h4>Copy independents from previous</h4>";
	$content .= "<h4>Compute independents from scores</h4>";
	$content .= "<h4>Compute independents from calibrations</h4>";
	$content .= "<h4>Disqualify independents from scores</h4>";
	$content .= "<h4>Assign Reviews</h4>";
	$content .= "<h4>Autograde and Assign</h4>";
	
	$content .= "<h3>Compute Independents From Scores</h3>";
    
    $content .= "<table width='100%'>\n";
    $content .= "<tr><td width='200'>Assignment Window Size</td><td>";
    $content .= "<input type='text' name='scorewindowsize' id='scorewindowsize' value='$configuration->scoreWindowSize' size='10'/></td></tr>\n";
    $content .= "<tr><td>Review Score Threshold</td><td>";
    $content .= "<input type='text' name='scorethreshold' id='scorethreshold' value='$configuration->scoreThreshold' size='10'/>%</td></tr>";
    $content .= "</table>\n";
	
	$content .= "<h3>Disqualify Independents</h3>";
	
    $content .= "<table width='100%'>\n";
    $content .= "<tr><td width='200'>Assignment Window Size</td><td>";
    $content .= "<input type='text' name='disqualifywindowsize' id='disqualifywindowsize' value='$configuration->disqualifyWindowSize' size='10'/></td></tr>\n";
    $content .= "<tr><td>Review Score Threshold</td><td>";
    $content .= "<input type='text' name='disqualifythreshold' id='disqualifythreshold' value='$configuration->disqualifyThreshold' size='10'/>%</td></tr>";
    $content .= "</table>\n";
	
	$content .= "<h3>Assign Reviews</h3>";
	
    $content .= "<table width='100%'>\n";
    $content .= "<tr><td width='300'>Window size to judge reviewer quality</td><td>";
    $content .= "<input type='text' name='windowsize' id='windowsize' value='$configuration->windowSize' size='10'/></td></tr>\n";
    $content .= "<tr><td>Num. Reviews to assign</td><td>";
    $content .= "<input type='text' name='numreviews' id='numreviews' value='$configuration->numReviews' size='10'/>&nbsp<input type='checkbox' name='assignmentdefaultnumreviews' id='assignmentdefaultnumreviews' value='assignmentdefaultnumreviews'>Use assignment default</td></tr>";
    $content .= "<tr><td>Max Assignment Attempts</td><td>";
    $content .= "<input type='text' name='maxattempts' id='maxattempts' value='$configuration->maxAttempts' size='10'/></td></tr>";
    $content .= "<tr><td>Score Noise</td><td>";
    $content .= "<input type='text' name='scorenoise' id='scorenoise' value='$configuration->scoreNoise' size='10'/></td></tr>";
    $content .= "<tr><td>Number of covert reviews to assign</td><td>";
    $content .= "<input type='text' name='numCovertCalibrations' id='numCovertCalibrations' value='$configuration->numCovertCalibrations' size='10'/></td></tr>";
	$content .= "<tr><td>When covert reviews are exhausted</td><td>";
	$exhaustedConditions = array("extrapeerreview", "error");
	$content .= "<input type='radio' name='exhaustedCondition' id='exhaustedCondition' value='extrapeerreview'>Assign extra peer review if available<br>";
	$content .= "<input type='radio' name='exhaustedCondition' id='exhaustedCondition' value='error'>Stop and report error";
	$content .= "</td></tr>";
	$content .= "</table>\n";
	
	$content .= "<script type='text/javascript'>
	if(".$configuration->numReviews." < 0)
	{
		$('#assignmentdefaultnumreviews').prop('checked', true);
		$('#numreviews').val('3');
	}
	$('input:radio[name=exhaustedCondition]:nth(".array_search("$configuration->exhaustedCondition", $exhaustedConditions).")').attr('checked',true);
	$('#assignmentdefaultnumreviews').change(function(){
		if(this.checked){
			$('#numreviews').prop('disabled', true)
		}else{
			$('#numreviews').prop('disabled', false)
		}
    });
    $('#assignmentdefaultnumreviews').change();
    </script>\n";
	
	$content .= "<h3>Autograde and Assign Markers</h3>";

	$content .= "<table width='100%'>\n";
	$content .= "<tr><td width='200'>Min Reviews for Auto-Grade</td><td>";
	$content .= "<input type='text' name='minReviews' id='minReviews' value='$configuration->minReviews' size='10'/></td></tr>\n";
	$content .= "<tr><td>Auto Spot Check Probability</td><td>"; ;
	$content .= "<input type='text' name='spotCheckProb' id='spotCheckProb' value='$configuration->spotCheckProb' size='10'/>(should be between 0 and 1)</td></tr>\n";
	$content .= "<tr><td>High Mark Threshold</td><td>";
	$content .= "<input type='text' name='highMarkThreshold' value='$configuration->highMarkThreshold' size='10'/>%</td></tr>\n";
	$content .= "<tr><td>High Mark Bias</td><td>";
	$content .= "<input type='text' name='highMarkBias' value='$configuration->highMarkBias' size='10'/></td></tr>\n";
	$content .= "<tr><td>Low Calibration Threshold</td><td>";
	$content .= "<input type='text' name='calibrationThreshold' value='$configuration->calibrationThreshold' size='10'/></td></tr>\n";
	$content .= "<tr><td>Calibration Bias</td><td>";
	$content .= "<input type='text' name='calibrationBias' value='$configuration->calibrationBias' size='10'/></td></tr>\n";
	$content .= "<tr><td>&nbsp</td></tr>\n";
	$content .= "</table>\n";
	
	$content .= "<h3>Marking Loads</h3>";
	$content .= "<table width='100%'>\n";
    foreach($dataMgr->getMarkers() as $markerID)
    {
        $content .= "<tr><td>".$dataMgr->getUserDisplayName(new UserID($markerID))."'s Load</td><td>";
        $content .= "<input type='text' name='load$markerID' value='".$dataMgr->getMarkingLoad(new UserID($markerID))."' size='30'/></td></tr>\n";
    }
	$content .= "</table>\n";

	$content .= "<input type='submit' value='Save'>";
	$content .= "</form>";
	
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
