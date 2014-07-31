<?php

class CopyAssignmentsScript extends Script
{
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
			$html .= "<option value='$courseObj->courseID'>$courseObj->name - $courseObj->displayName</option>";
		}
		$html .= "</select>";
		
		$html .= "</div>";
		
		$html .= "<div id='assignmentSelect'>";
		
		foreach($dataMgr->getAllAssignmentHeaders() as $assignmentObj){
			$html .= "<div class='$assignmentObj->courseID'>";
			$html .= "<input style='margin: 4px' type='checkbox' name='assignment-$assignmentObj->assignmentID'>$assignmentObj->name<br>";
			$html .= "</div>";
		}
		
		$html .= "</div>";
		
		//TODO: make javascript more accurate
		$html .= "<script type='text/javascript'>
        $('#courseSelect').change(function(){
        	$('.' + this.value).siblings().hide();
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
		
		$assignments = array();
		
		foreach($_POST as $key => $value){
			if($value=='YES'){
				$assignments[] = $key;
			}
		}
		
		$html = "";
		
		return ;
	}

} 

?>