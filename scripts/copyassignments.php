<?php

class CopyAssignmentsScript extends Script
{
	private $dateFormat = "MMMM Do YYYY, HH:mm";
	
	function getName()
    {
        return "Copy Assignments";
    }
    function getDescription()
    {
        return "Copy assignments from a previous offering.";
    }
    function getFormHTML()
    {
    	global $dataMgr;
		
    	$html = "";
		
		$html .= "<div style='margin-bottom: 20px'>";
		
		$html .= "Copy assignments from: ";
		
		$html .= "<select name='courseSelect' id='courseSelect'>";
		
		foreach($dataMgr->getCourses() as $courseObj){
			$html .= "<option value='$courseObj->courseID'>$courseObj->name - $courseObj->displayName</option>\n";
		}
		$html .= "</select>\n";
		
		$html .= "</div>\n";
		
		$html .= "<div id='assignmentSelect' style='margin-bottom: 20px'>";
		
		foreach($dataMgr->getAllAssignmentHeaders() as $assignmentObj){
			$html .= "<div class='$assignmentObj->courseID'>";
			$html .= "<input style='margin: 4px' type='checkbox' name='assignment-$assignmentObj->assignmentID'>$assignmentObj->name<br>";
			$html .= "</div>\n";
		}
		
		$html .= "</div>\n";
		
		$html .= "<table align='left' width='50%'>\n";
		$html .= "<tr><td>Appeal&nbsp;Stop&nbsp;Date</td><td><input type='text' name='appealStopDate' id='appealStopDate' /></td></tr>\n";		
        $html .= "<input type='hidden' name='appealStopDateSeconds' id='appealStopDateSeconds' />\n";
        $html .= "</table><br>\n";
   
		$html .= "<script type='text/javascript'> $('#appealStopDate').datetimepicker({ defaultDate : new Date(".date($this->dateFormat).")}); </script>\n";
		
		$html .= "<script type='text/javascript'>
        $('#courseSelect').change(function(){
        	$('#assignmentSelect').children().attr('checked', false);
        	$('#assignmentSelect').children().hide();
            $('.' + this.value).show();
        });
        $('#courseSelect').change();
        </script>\n";
		
		
        return $html;
    }
    function hasParams()
    {
        return true;
    }

	function executeAndGetResult()
	{
		global $dataMgr;
		
		$html = "";
	
		$assignmentIDs = array();
		
		//Get all selected assignment ID's from POST
		foreach($_POST as $key => $value){
			if(substr($key,0,11)=="assignment-"){
				$assignmentID = substr($key, 11, strlen($key));
				$assignmentIDs[] = $assignmentID;
				$html .= "<p>".$assignmentID."</p>";
			}
		}
		
		
		if(!empty($assignmentIDs))
		{
			$copiedAssignments = array();
			
			$assignment;
			
			$seed_Date = require_from_post('appealStopDate');
			
			$html .= "The seed date is ".$seed_Date;
			
			//Create copied assignments 
			foreach($assignmentIDs as $assignmentID){
				 $copiedAssignment = $dataMgr->getAssignment(new AssignmentID($assignmentID));
				 $copiedAssignment->assignmentID = NULL;
				 $dataMgr->saveAssignment($copiedAssignment, $copiedAssignment->assignmentType);
				 $html .= "<p>".gettype($copiedAssignment).$copiedAssignment->assignmentID."</p>";
			}
			
		}
		return $html;
	}

} 

?>