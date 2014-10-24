<?php
require_once("inc/common.php");
try
{
	$title .= " | Edit Course Configuration";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();
	
	$content = "<h1>Edit Course Configuration</h1>";
	
	$content .= "<h3>Assign Reviews</h3>";
	
	$content .= "<form action='?save=1' method='post'>";
    $content .= "<table width='100%'>\n";
    $content .= "<tr><td width='300'>Window size to judge reviewer quality</td><td>";
    $content .= "<input type='text' name='windowsize' id='windowsize' value='4' size='10'/></td></tr>\n";
    $content .= "<tr><td>Num. Reviews to assign</td><td>";
    $content .= "<input type='text' name='numreviews' id='numreviews' value='3' size='10'/>&nbsp<input type='checkbox' name='assignmentdefaultnumreviews' id='assignmentdefaultnumreviews' value='assignmentdefaultnumreviews'>Use assignment default</td></tr>";
    $content .= "<tr><td>Max Assignment Attempts</td><td>";
    $content .= "<input type='text' name='maxattempts' id='maxattempts' value='20' size='10'/></td></tr>";
    $content .= "<tr><td>Score Noise</td><td>";
    $content .= "<input type='text' name='scorenoise' id='scorenoise' value='0.01' size='10'/></td></tr>";
    $content .= "<tr><td>Number of covert reviews to assign</td><td>";
    $content .= "<input type='text' name='numCovertCalibrations' id='numCovertCalibrations' value='0' size='10'/></td></tr>";
	$content .= "<tr><td>When covert reviews are exhausted</td><td>";
	$content .= "<input type='radio' name='exhaustedCondition' id='exhaustedCondition' value='extrapeerreview' checked='checked'>Assign extra peer review if available<br>";
	$content .= "<input type='radio' name='exhaustedCondition' id='exhaustedCondition' value='error'>Stop and report error";
	$content .= "</td></tr>";
	$content .= "</table>\n";
	
	$content .= "<h3>Autograde and Assign Markers</h3>";

	$content .= "<table width='100%'>\n";
	$content .= "<tr><td width='200'>Min Reviews for Auto-Grade</td><td>";
	$content .= "<input type='text' name='minReviews' id='minReviews' value='3' size='10'/></td></tr>\n";
	$content .= "<tr><td>Auto Spot Check Probability</td><td>"; ;
	$content .= "<input type='text' name='spotCheckProb' id='spotCheckProb' value='0.25' size='10'/>(should be between 0 and 1)</td></tr>\n";
	$content .= "<tr><td>High Mark Threshold</td><td>";
	$content .= "<input type='text' name='spotCheckThreshold' value='80' size='10'/>%</td></tr>\n";
	$content .= "<tr><td>High Mark Bias</td><td>";
	$content .= "<input type='text' name='highMarkBias' value='2' size='10'/></td></tr>\n";
	$content .= "<tr><td>Low Calibration Threshold</td><td>";
	$content .= "<input type='text' name='calibThreshold' value='8.5' size='10'/></td></tr>\n";
	$content .= "<tr><td>Calibration Bias</td><td>";
	$content .= "<input type='text' name='calibBias' value='1.5' size='10'/></td></tr>\n";
	$content .= "<tr><td>&nbsp</td></tr>\n";
	$content .= "</table>\n";
	
	$content .= "<input type='submit' value='Submit'>";
	$content .= "</form>";
	
	$content .= "<script type='text/javascript'>
		$('#assignmentdefaultnumreviews').change(function(){
			if(this.checked){
				$('#numreviews').prop('disabled', true)
			}else{
				$('#numreviews').prop('disabled', false)
			}
        });
        $('#courseSelect').change();
        </script>\n";
	
	if(array_key_exists("save", $_GET)){
		$configuration = new CourseConfiguration();
        
        //Assign Reviews 
        $configuration->windowSize = require_from_post("windowsize");
		if(array_key_exists("assignmentdefaultnumreviews", $_POST))
			$configuration->numReviews = -1;
		else
			$configuration->numReviews = require_from_post("numreviews");
        $configuration->scoreNoise = require_from_post("scorenoise");
        $configuration->maxAttempts = require_from_post("maxattempts");
		$configuration->numCovertCalibrations = require_from_post("numCovertCalibrations");
       	$configuration->exhaustedCondition = require_from_post("exhaustedCondition");
		
		//Autograde and assign markers
        $configuration->minReviews = require_from_post("minReviews");
		$configuration->spotCheckProb = require_from_post("spotCheckProb");
        $configuration->spotCheckThreshold = require_from_post("spotCheckThreshold");
        $configuration->highMarkBias = require_from_post("highMarkBias");
		$configuration->calibThreshold = require_from_post("calibThreshold");
       	$configuration->calibBias = require_from_post("calibBias");
		
       	$dataMgr->saveCourseConfiguration($configuration);
	}
	
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
