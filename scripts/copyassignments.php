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
			$html .= "<option value='$courseObj->courseID'>$courseObj->name - $courseObj->displayName</option>\n";
		}
		$html .= "</select>\n";
		
		$html .= "</div>\n";
		
		$html .= "<div id='assignmentSelect' style='margin-bottom: 20px; border-width: 1px; border-style: solid; border-color: black; padding:10px'>";
		
		foreach($dataMgr->getAllAssignmentHeaders() as $assignmentObj){
			$html .= "<div class='$assignmentObj->courseID'>";
			$html .= "<input style='margin: 4px' type='checkbox' name='assignment-$assignmentObj->assignmentID'>$assignmentObj->name<br>";
			$html .= "</div>\n";
		}
		
		$html .= "</div>\n";
		
		$html .= "<p><input type='checkbox' name='includeCalibration' value='includeCalibration' checked/> Include calibration submissions and calibration reviews</p>";
		
		$html .= "<table align='left' width='50%'>";
		$html .= "<tr><td>Anchor&nbsp;on&nbsp;Start&nbsp;Date:</td><td><input type='text' name='anchorDate' id='anchorDate' /></td></tr>";		
        $html .= "<input type='hidden' name='anchorDateSeconds' id='anchorDateSeconds' />";
        $html .= "</table><br>";
		   
		$html .= "<script type='text/javascript'> $('#anchorDate').datetimepicker({minDateTime: new Date(), defaultDate: new Date()}); </script>";
		
        $html .= set_element_to_date("anchorDate", round(microtime(time())));
		
		//Maybe revise the trigger to convert anchorDate to anchorDateSeconds
		$html .= "<script type='text/javascript'> $('form').submit(function() {
			$('#anchorDateSeconds').val(moment($('#anchorDate').val(), 'MM/DD/YYYY HH:mm').unix());
			})</script>\n";	
		
		$html .= "<script type='text/javascript'>
        $('#courseSelect').change(function(){
			$(':checkbox').prop('checked', false);
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
			foreach($assignments as $assignment)
			{
				$originalAssignmentID = $assignment->assignmentID;
				$originalAssignment = $dataMgr->getAssignment($originalAssignmentID);
				
				$copiedAssignment = $assignment;
				$copiedAssignment->assignmentID = NULL;
				$startDate = $copiedAssignment->submissionStartDate;
				$base = $anchor_date + $deltas[$i];
				
				$copiedAssignment->name .= " (Copy)";
				 
				$copiedAssignment->submissionStartDate = $base;
				$copiedAssignment->submissionStopDate = $copiedAssignment->submissionStopDate - $startDate + $base;
				$copiedAssignment->reviewStartDate = $copiedAssignment->reviewStartDate - $startDate + $base;
				$copiedAssignment->reviewStopDate = $copiedAssignment->reviewStopDate - $startDate + $base;
 				$copiedAssignment->markPostDate = $copiedAssignment->markPostDate - $startDate + $base;
 				$copiedAssignment->appealStopDate = $copiedAssignment->appealStopDate - $startDate + $base;
				$copiedAssignments[] = $copiedAssignment;
				 
				$dataMgr->saveAssignment($copiedAssignment, $copiedAssignment->assignmentType);
				$i++;
				
				$questionsToCopy = array();
				$originalOrderOfQuestionIDs = array();
				$numReviewQuestions = 0;
				//Get all review questions from original assignment and add it to copied assignment
				foreach($originalAssignment->getReviewQuestions() as $reviewQuestion)
			    {
			     	$originalOrderOfQuestionIDs[] = $reviewQuestion->questionID->id;
					$numReviewQuestions++;
					$reviewQuestion->questionID = NULL;
					$questionsToCopy[] = $reviewQuestion;
			    }
				
				//Must add questions in reverse to copy original order
				for($j = $numReviewQuestions - 1; $j >= 0; $j--)
				{
				 	$copiedAssignment->saveReviewQuestion($questionsToCopy[$j]);
				}
				 
				$copiedOrderOfQuestionIDs = array();
				foreach($copiedAssignment->getReviewQuestions() as $question)
				{
					$copiedOrderOfQuestionIDs[] = $question->questionID->id;
				}
				
				if(array_key_exists('includeCalibration',$_POST))
				{
					$submissionIDtoreviewsMap = $originalAssignment->getCorrectReviewMap(); //Miguel: new method of searching for submissions that have been reviewed by 'correctly'
					
					//Copy original submissions to copied assignment
					foreach($submissionIDtoreviewsMap as $submissionID => $reviews)
					{
					 	//copiedSubmission should be exactly like original submission
						$copiedSubmission = $originalAssignment->getSubmission(New SubmissionID($submissionID));
						
						//Save as new submission in db 
						$copiedSubmission->submissionID = NULL;
						$copiedAssignment->saveSubmission($copiedSubmission);
						
						$newSubmissionID = $copiedAssignment->getSubmissionID($copiedSubmission->authorID);
						
						foreach($reviews as $reviewObj)
						{
							$review = $originalAssignment->getReview($reviewObj->matchID);
							$newMatchID = $copiedAssignment->createMatch($newSubmissionID, $review->reviewerID, true, 1);
							 
							$copiedReview = new Review($copiedAssignment);
							$copiedReview->submissionID = $newSubmissionID;
							$copiedReview->reviewerID = $review->reviewerID;
							$copiedReview->matchID = $newMatchID;
							$copiedReview->answers = array();
							
							for($i = 0; $i < $numReviewQuestions; $i++)
							{
								$answer = $review->answers[$originalOrderOfQuestionIDs[$i]];
								$copiedReview->answers[$copiedOrderOfQuestionIDs[$i]] = $answer;
							}
		
						 	$copiedAssignment->saveReview($copiedReview);
						}
					}
				}
			}
			
			//The printed output
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
			$html .= "<p>No assignments were copied because none were selected</p>\n";
		}
		return $html;
	}
} 

?>