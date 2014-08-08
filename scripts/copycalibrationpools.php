<?php

class CopyCalibrationPoolsScript extends Script
{
	function getName()
    {
        return "Copy Calibration Pools";
    }
    function getDescription()
    {
        return "Copy calibration pools from a previous offering.";
    }
    function getFormHTML()
    {
    	global $dataMgr;
		
    	$html = "";
		
		$html .= "<div style='margin-bottom: 20px'>";
		
		$html .= "Copy calibration pools from: ";
		
		$html .= "<select name='courseSelect' id='courseSelect'>";
		
		foreach($dataMgr->getCourses() as $courseObj){
			$html .= "<option value='$courseObj->courseID'>$courseObj->name - $courseObj->displayName</option>\n";
		}
		$html .= "</select>\n";
		
		$html .= "</div>\n";
		
		$html .= "<div id='calibPoolSelect' style='margin-bottom: 20px; border-width: 1px; border-style: solid; border-color: black; padding:10px'>";
		
		foreach($dataMgr->getAllCalibrationPoolHeaders() as $calibPoolObj){
			$html .= "<div class='$calibPoolObj->courseID'>";
			$html .= "<input style='margin: 4px' type='checkbox' name='assignment-$calibPoolObj->assignmentID'>$calibPoolObj->name<br>";
			$html .= "</div>\n";
		}
		
		$html .= "</div>\n";
		
		$html .= "<table align='left' width='50%'>";
		$html .= "<tr><td>Anchor&nbsp;on&nbsp;Start&nbsp;Date:</td><td><input type='text' name='anchorDate' id='anchorDate' /></td></tr>";		
        $html .= "<input type='hidden' name='anchorDateSeconds' id='anchorDateSeconds' />";
        $html .= "</table><br>";
		   
		$html .= "<script type='text/javascript'> $('#anchorDate').datetimepicker({minDateTime: new Date(), defaultDate: new Date()}); </script>";
		
        $html .= set_element_to_date("anchorDate", round(microtime(time())));
		
		//TODO: Revise the trigger to convert anchorDate to anchorDateSeconds
		$html .= "<script type='text/javascript'> $('form').submit(function() {
			$('#anchorDateSeconds').val(moment($('#anchorDate').val(), 'MM/DD/YYYY HH:mm').unix());
			})</script>\n";	
		
		$html .= "<script type='text/javascript'>
        $('#courseSelect').change(function(){
			$(':checkbox').prop('checked', false);
        	$('#calibPoolSelect').children().hide();
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
		
		global $USERID;
		
		$html = "";
	
		$assignmentIDs = array();
		
		//Get all selected assignment ID's from POST
		foreach($_POST as $key => $value){
			if(substr($key,0,11)=="assignment-"){
				$assignmentID = substr($key, 11, strlen($key));
				$assignmentIDs[] = $assignmentID;
			}
		}
			
		if(!empty($assignmentIDs))
		{
			$assignments = array();
			
			$deltas = array();
				
			$copiedAssignments = array();
			
			$anchor_date = require_from_post('anchorDateSeconds');
			
			$reference_date = NULL;
			
			$i = 0;
			
			foreach($assignmentIDs as $assignmentID){
				$assignment = $dataMgr->getAssignment(new AssignmentID($assignmentID));
				if(!$reference_date){
					$reference_date = $assignment->submissionStartDate;
				}
				$deltas[$i] = $assignment->submissionStartDate - $reference_date;
				
				$assignments[] = $assignment;
				$i++;
			}
			
			$i = 0;
			
			//Create copied assignments 
			foreach($assignments as $assignment){
				 $assID = $assignment->assignmentID;
				 $copiedAssignment = $assignment;
				 $copiedAssignment->assignmentID = NULL;
				 $startDate = $copiedAssignment->submissionStartDate;
				 $base = $anchor_date + $deltas[$i];
				 
				 $copiedAssignment->submissionStartDate = $base;
				 $copiedAssignment->submissionStopDate = $copiedAssignment->submissionStopDate - $startDate + $base;
				 $copiedAssignment->reviewStartDate = $copiedAssignment->reviewStartDate - $startDate + $base;
				 $copiedAssignment->reviewStopDate = $copiedAssignment->reviewStopDate - $startDate + $base;
 				 $copiedAssignment->markPostDate = $copiedAssignment->markPostDate - $startDate + $base;
 				 $copiedAssignment->appealStopDate = $copiedAssignment->appealStopDate - $startDate + $base;
				 $copiedAssignments[] = $copiedAssignment;
				 
				 $dataMgr->saveAssignment($copiedAssignment, $copiedAssignment->assignmentType);
				 
				 $authorIDsubmissionIDMap = $dataMgr->getAssignment($assID)->getAuthorSubmissionMap();
				 
				 foreach($authorIDsubmissionIDMap as $submissionID)
				 {
				 	 $html .= "<p>THE COPIED ASSIGNMENT ID IS $copiedAssignment->assignmentID and its name is $copiedAssignment->name</p>";
					
					 $html .= "The submission is $submissionID";
					 
					 $submission = $dataMgr->getAssignment($assID)->getSubmission($submissionID);


		             $sh = $dataMgr->getDatabase()->prepare("INSERT INTO peer_review_assignment_submissions (assignmentID, authorID, noPublicUse) VALUES(?, ?, ?);");
		             $sh->execute(array($copiedAssignment->assignmentID, $submission->authorID, $submission->noPublicUse));
					 
					 /*
					 if($result){
					 	$html .= "<h1>Query ran successfully</h1>"; 
					 } else {
					 	$html .= "<h1>Query ran unsuccessfully".mysql_error()."</h1>"; 
					 }
					 */
					 //saveSubmission($dataMgr->getAssignment($assID), $submission);
				 }
				 
				 $html .= "<ol>\n";
				 foreach($dataMgr->getAssignment($assID)->getReviewQuestions() as $question)
			     {
			         $html .= "<li>".cleanString($question->question)."</li>\n";
			     }
				 $html .= "</ol>";
				 
				 $i++;
			}
			
			$html .= "<p>The following assignments have been created:</p>";
			
			$bg = '#eeeeee';
			
			foreach($copiedAssignments as $copiedAssignment){
				
				 $bg = ($bg == '#eeeeee' ? '#ffffff' : '#eeeeee');
				
				 $html .= "<div style='background-color:$bg'>";
				
	             $html .= "<h3>".$copiedAssignment->name."</h3>";
				 
				 $html .= $copiedAssignment->getHeaderHTML($USERID);
				 
				 $html .= "</div>";
			}
			
		} else {
			$html .= "<p>No calibration pools were copied because none were selected</p>\n";
		}
		return $html;
	}


} 

?>