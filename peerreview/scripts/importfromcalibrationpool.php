<?php
require_once("peerreview/inc/common.php");

class ImportFromCalibrationPoolPeerReviewScript extends Script
{
	function getName()
    {
        return "Import From Calibration Pools";
    }
    function getDescription()
    {
        return "Import calibration essays and reviews from a 'calibration pool' into this assignment.";
    }
    function getFormHTML()
    {
    	global $dataMgr, $USERID;
		
    	$html = "";
		
		$html .= "<div style='margin-bottom: 20px'>";
		
		$html .= "Import from from calibration pools: ";
		
		$html .= "<select name='courseSelect' id='courseSelect'>";
		
		foreach($dataMgr->getCoursesInstructedByUser($USERID) as $courseObj){
			$html .= "<option value='$courseObj->courseID'>$courseObj->name - $courseObj->displayName</option>\n";
		}
		$html .= "</select>\n";
		
		$html .= "</div>\n";
		
		$html .= "<div id='calibPoolSelect' style='margin-bottom: 20px; border-width: 1px; border-style: solid; border-color: black; padding:10px'>";
		
		foreach($dataMgr->getAllCalibrationPoolHeaders() as $pool){
			$html .= "<div class='$pool->courseID'>";
			$html .= "<input style='margin: 4px' type='radio' name='selectedPool' value='$pool->assignmentID'>$pool->name<br>";
			$html .= "</div>\n";
		}
		
		$html .= "</div>\n";
		
		$html .= "<h4 style='color:red;'> Please ensure that this assignment's submission questions and review questions are the same with the pool </h4>";;
		   
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
		
		if(array_key_exists('selectedPool', $_POST))
		{
			$selectedPoolID = require_from_post('selectedPool');
			$selectedPool = $dataMgr->getAssignment(new AssignmentID($selectedPoolID));
			
			$assignment = get_peerreview_assignment();
			
			$selectedPoolQuestions = $selectedPool->getReviewQuestions();
			
			$assignmentQuestions = $assignment->getReviewQuestions();
			
			// Check if assignment and pool hav ethe same number of review questions
			if(sizeof($selectedPoolQuestions) != sizeof($assignmentQuestions))
			{
				$html .= "<p style='color:red;'> The calibration pool and this assignment don't have the same number of review questions</p>\n";
				return $html;
			}
			
			$numReviewQuestions = sizeof($selectedPoolQuestions);
			
			$selectedPoolQuestionIDs = array();
			
			foreach($selectedPoolQuestions as $selectedPoolQuestion)
			{
				$selectedPoolQuestionIDs[] = $selectedPoolQuestion->questionID->id;
			}
			
			$assignmentQuestionIDs = array();
			
			foreach($assignmentQuestions as $assignmentQuestion)
			{
				$assignmentQuestionIDs[] = $assignmentQuestion->questionID->id;
			}
			
			//TODO: Check if they are the same type of questions in the same order
			
			//TODO: Check if that the radio questions have the same values and range
			
			//NOTE: not all these submissions maybe instructor submissions.
			$authorIDtosubmissionIDMapForPool = $selectedPool->getAuthorSubmissionMap();
			
			$oldToNewAuthorIDMap = array();
			
			$oldToNewReviewerIDMap = array();
					
			foreach($authorIDtosubmissionIDMapForPool as $authorID => $submissionID)
			{
				$submission = $selectedPool->getSubmission($submissionID);					 
				
				$submission->submissionID = NULL;
				
				//TODO: Check if author of a submission from pool is already an author in this assignment

				if($assignment->submissionExists(new UserID($authorID)) || !$assignment->isInSameCourse($selectedPool))
				{
					$newAuthorID = $assignment->getUserIDForCopyingSubmission($submission->authorID, $dataMgr->getUsername($submission->authorID));
					$submission->authorID = $newAuthorID;
					$oldToNewAuthorIDMap[$authorID] = $newAuthorID->id;
				}
				else
					$oldToNewAuthorIDMap[$authorID] = $submission->authorID->id;
				
				//TODO: Check if submission from  pool is the same 'textually' as submission already in this assignment
				
				$assignment->saveSubmission($submission);
			}
			
			$authorIDtosubmissionIDMapForAssignment = $assignment->getAuthorSubmissionMap();
			
			$oldToNewReviewerIDMap[$authorID] = array();
			
			foreach($authorIDtosubmissionIDMapForPool as $authorID => $submissionID)
			{
				$matchIDs = $selectedPool->getSingleInstructorReviewForSubmission($submissionID); //gets 'correct' reviews the old-fashioned way
				
				foreach($matchIDs as $matchID)
			 	{
					$review = $selectedPool->getReview($matchID);
					 
					$copiedSubmissionID = $authorIDtosubmissionIDMapForAssignment[$oldToNewAuthorIDMap[$authorID]];
					if(!$copiedSubmissionID)
						throw new Exception("Submission by an author was not properly transcribed");
					
					$newReviewerID = $assignment->getUserIDForCopyingReview($review->reviewerID, $dataMgr->getUsername($review->reviewerID), $copiedSubmissionID);
					
					$oldToNewReviewerIDMap[$authorID][$review->reviewerID->id] = $newReviewerID->id;
					
					$newmatchID = $assignment->createMatch($copiedSubmissionID, $newReviewerID, true, 'key');
					 
					$copiedReview = new Review($assignment);
					$copiedReview->submissionID = $copiedSubmissionID;
					$copiedReview->reviewerID = $newReviewerID;
					$copiedReview->matchID = $newmatchID;
					$copiedReview->answers = array();
						 
					for($i = 0; $i < $numReviewQuestions; $i++)
					{
						$answer = $review->answers[$selectedPoolQuestionIDs[$i]];
						$copiedReview->answers[$assignmentQuestionIDs[$i]] = $answer;
					}

				 	$assignment->saveReview($copiedReview);
			 	}
			}
			
			//The printed output
			$html .= "<p>The calibration submissions and reviews have been imported</p>";
			
			$html .= "<table align='left' width='100%'>";
			$html .= "<tr><td><h2>Submissions by</h2></td><td><h2>Transcribed by</h2></td><td><h2>Reviews by</h2></td><td><h2>Transcribed by</h2></td><tr>";
			foreach($oldToNewAuthorIDMap as $oldAuthorID => $newAuthorID)
			{	
				$html .= "<tr><td>".$dataMgr->getUserDisplayName(new UserID($oldAuthorID))."</td><td>".$dataMgr->getUserDisplayName(new UserID($oldAuthorID))."</td><td><table align='left'>";
				foreach($oldToNewReviewerIDMap[$oldAuthorID] as $oldReviewerID => $newReviewerID)
				{
					$html .= "<tr><td>".$dataMgr->getUserDisplayName(new UserID($oldReviewerID))."</td></tr>";
				}
				$html .= "</table></td><td><table align='left'>";
				foreach($oldToNewReviewerIDMap[$oldAuthorID] as $newReviewerID)
				{
					$html .= "<tr><td>".$dataMgr->getUserDisplayName(new UserID($newReviewerID))."</td></tr>";
				}
				$html .= "</table></td></tr>";
			}
			$html .= "</table>";
			
		} else {
			$html .= "<p>No calibration pool was selected for importing calibration submissions and reviews</p>\n";
		}
		return $html;
	}


} 

?>