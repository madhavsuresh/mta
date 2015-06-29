<?php
require_once("inc/common.php");
require_once("inc/calibrationutils.php");
try
{
    $title .= " | Edit Review";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    if(array_key_exists("close", $_GET))
        $closeOnDone = "&close=1";
    else
        $closeOnDone = "";

    $assignment = get_peerreview_assignment();
    $assignmentWithSubmission = $assignment;

    $beforeReviewStart = $NOW < $assignment->reviewStartDate;
    $afterReviewStop   = grace($assignment->reviewStopDate) < $NOW;
	$beforeCalibrationStart = $NOW < $assignment->calibrationStartDate;
	$afterCalibrationStop   = grace($assignment->calibrationStopDate) < $NOW;
	
    $isCalibration = false;

    if(array_key_exists("review", $_GET) || array_key_exists("calibration", $_GET)){
        #We're in student mode
        $reviewerID = $USERID;
        if(array_key_exists("review", $_GET)){
            $id = $_GET["review"];
            $reviewAssignments = $assignment->getAssignedReviews($reviewerID);
            $getParams = "&review=$id";
        }else{
            $id = $_GET["calibration"];
            $reviewAssignments = $assignment->getAssignedCalibrationReviews($reviewerID);
            $getParams = "&calibration=$id";
            $isCalibration = true;
        }

        #Try and extract who the author is - if we have an invalid index, return to main
        if(!isset($reviewAssignments[$id]))
            throw new Exception("No review with id $id");

        #Get the match id, then everything else
        $matchID = $reviewAssignments[$id];
        
        //Check to make sure that they haven't already submitted this
        if($isCalibration)
            $assignmentWithSubmission = $dataMgr->getAssignment($dataMgr->getAssignmentDataManager("peerreview")->getAssignmentIDForMatchID($matchID));
            if($assignmentWithSubmission->getReviewMark($matchID)->isValid){
                redirect_to_page("peerreview/viewcalibration.php?assignmentid=$assignment->assignmentID&calibration=".$_GET["calibration"]);
        }
        
        $submission = $assignmentWithSubmission->getSubmission($matchID);
        if($assignmentWithSubmission->reviewExists($matchID)){
            $review = $assignmentWithSubmission->getReview($matchID);
        }else{
            $review = $assignmentWithSubmission->getReviewDraft($matchID);
        }
        $reviewerName = $dataMgr->getUserDisplayName($reviewerID);
    }
    else
    {
        //We better be an instructor
        $authMgr->enforceMarker();

        if(array_key_exists("matchid", $_GET))
        {
            //This is easy, just go load it up
            $matchID = new MatchID($_GET["matchid"]);
            $submission = $assignment->getSubmission($matchID);
            if($assignment->reviewExists($matchID)){
                $review = $assignment->getReview($matchID);
            }else{
                $review = $assignment->getReviewDraft($matchID);
            }
            $reviewerName = $dataMgr->getUserDisplayName($review->reviewerID);
            $getParams = "&matchid=$matchID";
        }
        else if(array_key_exists("reviewer", $_GET))
        {
            //Get the submission, and make a new review
            $reviewer = require_from_get("reviewer");
            $submissionID = new SubmissionID(require_from_get("submissionid"));
            $submission = $assignment->getSubmission($submissionID);
            $review = new Review($assignment);
            $review->reviewerID = $USERID;
            $review->submissionID = $submissionID;
            if($reviewer == "instructor")
                $reviewerName = "Instructor ".$dataMgr->getUserDisplayName($USERID);
            else if($reviewer == "anonymous")
                $reviewerName = "Anonymous (by ".$dataMgr->getUserDisplayName($USERID).")";
            else
                throw new Exception("Unknown reviewer type '$reviewer'");
            $getParams = "&reviewer=$reviewer&submissionid=$submissionID";
        }
        else
        {
            //No idea what this is
            throw new Exception("No valid options specified");
        }

        //We need to see if we're running into an issue where someone else has touched
        if(!array_key_exists("force", $_GET) && (!isset($matchID) || !$assignment->reviewExists($matchID)) && $dataMgr->isInstructor($review->reviewerID))
        {
            //Figure out if anyone else has touched this
            $touches = $assignment->getTouchesForSubmission($review->submissionID);

            //Figure out if we have someone else in this array
            $maxI = sizeof($touches);
            for($i = 0; $i < $maxI; $i++)
            {
                if($touches[$i]->userID == $review->reviewerID->id)
                {
                    unset($touches[$i]);
                }
            }

            if(sizeof($touches))
            {
                //We need to print the warning message
                $content .= "<h1>Submission Touched</h1>\n";

                $content .= "This submission has been touched by the following users:<br><br>\n";

                $i = 0;
                foreach($touches as $touch)
                {
                    $content .= $dataMgr->getUserDisplayName(new UserID($touch->userID))." on <span id='touchdate$i' ></span></br>\n";
                    $content .= set_element_to_date("touchdate$i", $touch->timestamp, "html", $assignment->dateFormat);
                    $i++;
                }

                $getArgs="force=1&";
                foreach($_GET as $arg=>$val) {
                    $getArgs.="$arg=$val&";
                }
                $content .= "<br><a href='?$getArgs'>Force Review Anyways</a>";

                render_page();
            }
        }

        //If we're an instructor, we need to touch this
        if($dataMgr->isInstructor($review->reviewerID))
        {
            $assignment->touchSubmission($review->submissionID, $review->reviewerID);
        }

        #We can just override the data on this assignment so that we can force a write
        $beforeReviewStart = false;
        $afterReviewStop   = false;
		$beforeCalibrationStart = false;
		$afterCalibrationStop = false;
    }

    #Check to make sure submissions are valid
    if($isCalibration ? $beforeCalibrationStart : $beforeReviewStart) 
    {
        $content .= 'This assignment has not been posted';
    }
    else if($isCalibration ? $afterCalibrationStop : $afterReviewStop)
    {
        $content .= 'Reviews can no longer be submitted';
    }
    else if($assignment->deniedUser($review->reviewerID))
    {
        $content .= 'You have been excluded from this assignment';
    }
    else #There's no reason not to run up the submission interface now
    {
        #Show the submission question
        $content .= "<h1>Submission Question</h1>\n";
        $content .= $assignmentWithSubmission->submissionQuestion;

        #Get the review that we are currently working on
        $content .= "<h1>Submission</h1>\n";
        $content .= $submission->getHTML();

        //Get the validate function
        $content .= "<script> $(document).ready(function(){ $('#saveButton').click(function() {\n";
        $content .= "var error = false;\n";
        #$content .= "var res = $('#review').find('input[type=\"submit\"]:focus').attr('id');\n";
        $content .= $review->getValidationCode();
        $content .= "return !error;\n";
        $content .= "}); }); </script>\n";

        //Make the form
        $content .= "<h1>$reviewerName's Review</h1>\n";
        $content .= "<form id='review' action='".get_redirect_url("peerreview/submitreview.php?assignmentid=$assignment->assignmentID$getParams$closeOnDone")."' method='post'>";
        $content .= $review->getFormHTML();
        $content .= "<br><br><input type='submit' name='saveAction' id='saveButton' value='Submit' /><input type='submit' name='saveAction' value='Save Draft' />\n";
        $content .= "</form>\n";
		
		if(array_key_exists("showall",$_GET))
		{
			$reviews = $assignmentWithSubmission->getReviewsForSubmission($submission->submissionID);
			if($reviews)
			{
				$reviewMap = $assignmentWithSubmission->getReviewMap();
				
				$content .= "<div style='margin-top:20px; border-top: 3px solid #999; border-bottom: 3px solid #999;'><h1>Student Reviews</h1></div>";
				foreach($reviews as $review)
				{
					if($dataMgr->isStudent($review->reviewerID) && !$reviewMap[$review->submissionID->id][$review->reviewerID->id]->instructorForced)
					{
						$content .= "<h1>".$dataMgr->getUserDisplayName($review->reviewerID)."'s Review</h1>\n";
						$content .= $review->getHTML(true);
	 				   
	 				    $content .= "<h1>".$dataMgr->getUserDisplayName($review->reviewerID)."'s Current Review Score</h1>\n";
					    //TODO: Remove this hardcoded bit for the window size
					    $content .= precisionFloat(compute_peer_review_score_for_assignments($review->reviewerID, $assignment->getAssignmentsBefore(4))*100)."%";
					    
					    $matchID = $assignment->getMatchID($review->submissionID, $review->reviewerID);
						$reviewMark = $assignment->getReviewMark($matchID);
						
					    $content .= "<form id='mark$matchID' action='peerreview/submitmark.php?assignmentid=$assignment->assignmentID&type=review&matchid=$matchID' method='post'>\n";
						//Extracted from getFormHTML function from ReviewMark Class. Need ID's on every review's inputs.
						$content .= "<h1>Mark</h1>\n";
				        $content .= "<h2>Score</h2>\n";
				        $content .= "<input type='text' value='$reviewMark->score' name='score' id='score$matchID'>\n";
				        $content .= "<h2>Comments</h2>\n";
				        $content .= "<textarea name='comments' cols='60' rows='10'>\n";
				        $content .= "$reviewMark->comments";
				        $content .= "</textarea>\n";
						if($reviewMark->markTimestamp) $html .= "<h4>Last Updated: ".date("Y-m-d H:i:s",$reviewMark->markTimestamp)."</h4>";
						
						$content .= "<br><br><input type='submit' value='Submit' />\n";
						$content .= "</form>\n";
						
						$content .= "<div id='message$matchID'></div><br>\n";
				
						$content .= "<script type='text/javascript'>
									$(document).ready(function(){
										  $('#mark$matchID').submit(function(){
									      $.post($(this).attr('action'), $(this).serialize(), function(response){},'json');
									      if($('#score$matchID').val() != ''){
									      	  $('#message$matchID').css('color','green');
										      $('#message$matchID').html('Mark submitted');
										  } else {
										  	  $('#message$matchID').css('color','red');
										      $('#message$matchID').html('You must enter a score');
										  }
									      return false;
									   });
									});
									</script>";
									
						/*$content .= "<script type='text/javascript'> $(document).ready(function(){ $('#mark$matchID').submit(function() {\n";
				        $content .= "var error = false;\n";
						$content .= "$('#message$matchID').html('').parent().hide();\n";
        				$content .= "if($('#score$matchID').val() == ''){";
        				$content .= "$('#message$matchID').html('You must enter a score');error = true;\n";
						$content .= "}else{";
						$content .= "$('#message$matchID').html('Mark submitted'); error = false;\n";
        				$content .= "}$('#message$matchID').parent().show();\n";
				        $content .= "if(error){return false;}else{return true;}\n";
				        $content .= "}); }); </script>\n";*/	
					}
				}
			}
		}
		
		//Miguel: new calibration score briefing
		if($isCalibration)
		{
			$numberOfCalibrations = $dataMgr->numCalibrationReviews($reviewerID);
			if($numberOfCalibrations){
				$score = convertTo10pointScale(computeWeightedAverage($dataMgr->getCalibrationScores($reviewerID)), $assignment);
			} else {
				$score = "--";
			}
			$content .= "<h4>Current Weighted Average: $score / $assignment->calibrationMaxScore</h4>";
			
		}
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
