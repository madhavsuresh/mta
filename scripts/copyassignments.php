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
		$html .= "<select name='courseSelect' id='courseSelect'>";
		foreach($dataMgr->getCourses() as $courseObj){
			$html .= "<option value='$courseObj->name'>$courseObj->displayName</option>";
		}
		$html .= "</select><br>	";
		
		$html .= "<script type='text/javascript'>
        $('#courseSelect').change(function(){
            $('#' + this.value).show().siblings().hide();
        });
        $('#courseSelect').change();
        </script>\n";
		
		$html .= "<select name='sometext' size='5'>
		  <option>text1</option>
		  <option>text2</option>
		  <option>text3</option>
		  <option>text4</option>
		  <option>text5</option>
		</select>";
		
        return $html;
    }
    function hasParams()
    {
        return true;
    }

	function executeAndGetResult()
	{
		
			
			
		return;
	}

} 

?>