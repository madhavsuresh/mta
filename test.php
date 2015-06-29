<?php
require_once("inc/common.php");
try
{
	foreach($dataMgr->getAssignments() as $assignment)
	{
		if($assignment->assignmentID->id == 118)
		{
			$grades = $assignment->getGrades();
		}
	}
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
