<?php
require_once("inc/common.php");

global $assignments;
global $dataMgr;
global $USERID;
global $content;

//$content .= "<h1>HELLO THERE TA</h1>";

$reviewTasks = array();

foreach($assignments as $assignment)
{
	$reviews = $assignment->getReviewsForMarker($USERID);
	
	foreach($reviews as $reviewObj)
	{
		if($reviewObj->exists)
		{
			
		}	
		else 
		{
			$reviewTask = new stdClass();
			$reviewTask->html = 
			"<table width='100%'><tr><td class='column1'><h4>$assignment->name</h4></td>
			<td class='column2'>Review</td></td>
			<td class='column3'><a target='_self' title='Edit' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&submissionid=$reviewObj->submissionID&matchid=$reviewObj->matchID&close=1")."'><button>Go</button></a></td>
			<td class='column4'>".phpDate($assignment->markPostDate)."</td></tr></table>\n";
			insert($reviewTask, $reviewTasks);
		}	
	}
}


$content .= "<h1>Tasks</h1>\n";
$bg = '';
foreach($reviewTasks as $reviewTask)
{
	$bg = ($bg == '#E0E0E0' ? '' : '#E0E0E0');
	$content .= "<div class='TODO' style='background-color:$bg;'>";
	$content .= $reviewTask->html;
	$content .= "</div>";
}

?>