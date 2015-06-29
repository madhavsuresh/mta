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
		return "Sorry, this script has still not been tested after many changes to the system";	
			
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
				 
				 //Create copied assignment
				 $dataMgr->saveAssignment($copiedAssignment, $copiedAssignment->assignmentType);
				 
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
				 
				 //Map of author ID's to submission ID's
				 $authorIDtosubmissionIDMap = $originalAssignment->getAuthorSubmissionMap();
				 
				 //Map of submission ID's to reviews
				 //$submissionIDtoreviewsMap = $originalAssignment->getReviewMap();
				 
				 //Copy original submissions to copied assingment
				 foreach($authorIDtosubmissionIDMap as $submissionID)
				 {
					 //Get original submission
					 $submission = $originalAssignment->getSubmission($submissionID);					 
					 
					 //Copy old submission by setting its submission ID to null and saving it 
					 $submission->submissionID = NULL;
					 $copiedAssignment->saveSubmission($submission);
				 }
				 
				 //Now that submissions have been copied get the map of author ID's to submission ID's for copied assignment
				 $authorIDtosubmissionIDMap2 = $copiedAssignment->getAuthorSubmissionMap();
				 
				 foreach($authorIDtosubmissionIDMap as $authorID => $submissionID)
				 {
				 	 $matchIDs = $originalAssignment->getInstructorMatchesForSubmission($submissionID);
				 	 
					 foreach($matchIDs as $matchID)
				 	 {
						 $review = $originalAssignment->getReview($matchID);
						 
						 $copiedSubmissionID = $authorIDtosubmissionIDMap2[$authorID];
						 
						 $newmatchID = $copiedAssignment->createMatch($copiedSubmissionID, $review->reviewerID, true);
						 
						 $copiedReview = new Review($copiedAssignment);
						 $copiedReview->submissionID = $copiedSubmissionID;
						 $copiedReview->reviewerID = $review->reviewerID;
						 $copiedReview->matchID = $newmatchID;
						 $copiedReview->answers = array();
						 
						 for($i = 0; $i < $numReviewQuestions; $i++)
						 {
						 	$answer = $review->answers[$originalOrderOfQuestionIDs[$i]];
						 	$copiedReview->answers[$copiedOrderOfQuestionIDs[$i]] = $answer;
						 }

					 	 $copiedAssignment->saveReview($copiedReview);
				 	 }
				 }
				 $i++;
			}
			
			//The printed output
			$html .= "<p>The following assignments have been created:</p>";
			$bg = '#eeeeee';
			foreach($copiedAssignments as $copiedAssignment)
			{	
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