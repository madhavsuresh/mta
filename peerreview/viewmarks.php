<?php
require_once("inc/common.php");
try
{
    $title .= " | View Marks";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $assignment = get_peerreview_assignment();
    $assignedReviews = $assignment->getAssignedReviews($USERID);

    if($NOW < $assignment->markPostDate)
    {
        $content .= "Marks have not been posted yet\n";
    }
    else if($assignment->deniedUser($USERID))
    {
        $content .= "You have been excluded from this assignment\n";
    }
    else
    {
        $content .= "<h1>$assignment->name</h1>\n";
        $content .= $assignment->submissionQuestion;

        $content .= "<script>
                     $(function() {
                         $( '#tabs' ).tabs();
                     });
                     </script>";

        //Make the tab widget
        $content .= "<div id='tabs'><ul>";
        $tabOffset = 0;
        if($assignment->submissionExists($USERID))
        {
            $content .= "<li><a href='#tabs-1'>My Submission</a></li>\n";
            $tabOffset++;
        }
        for($i = 1; $i <= sizeof($assignedReviews); $i++)
        {
            $tabIndex= $i+$tabOffset;
            $content .= "<li><a href='#tabs-$tabIndex'>Review $i</a></li>\n";
        }
        $content .= "</ul>";


        //Define a function that will render a tab for us
        function getTabHTML(SubmissionID $submissionID, $showSubmissionMark, $showStudentReviews, $showInstructorReviews, $showReviewMarks, $showAppealLinks)
        {
            global $USERID, $assignment, $dataMgr, $NOW;
            $html  = "<h1>Submission</h1>\n";
            #Show the submission
            try
            {
                $submission = $assignment->getSubmission($submissionID);
            }catch(Exception $e){
                $html .= "(No Submission)\n";
                return $html;
            }

            //Print out the submission
            $html .= $submission->getHTML();
            if($showSubmissionMark)
            {
                $html .= "<h2 class='altHeader'>Submission Mark</h2>\n";
                $html .= $assignment->getSubmissionMark($submissionID)->getHTML($assignment->maxSubmissionScore);
            }

            //Figure out this index
            $assignedReviews = $assignment->getAssignedReviews($USERID);
            $reviews = $assignment->getReviewsForSubmission($submissionID);
            $reviewCount = 0;
            //Do the first pass, and see if we can find this user's submission
            foreach($reviews as $review)
            {
                if(sizeof($review->answers) == 0)
                    continue;
                if($review->reviewerID->id == $USERID->id)
                {
                    $html .= "<h1>My Review</h1>\n";
                    $html .= $review->getHTML();
                    $html .= "<h2 class='altHeader'>My Review Mark</h2>\n";
                    $html .= $assignment->getReviewMark($review->matchID)->getHTML($assignment->maxReviewScore);

                    $assignedReviewIndex = 0;
                    foreach($assignedReviews as $mID)
                    {
                        if($mID->id == $review->matchID->id){
                            break;
                        }
                        $assignedReviewIndex++;
                    }

                    //Now we need to do the stuff for appeals
                    if($assignment->appealExists($review->matchID, "reviewmark"))
                    {
                        //Show them a link for editing their appeals
                        $tmp = "";
                        if($assignment->hasNewAppealMessage($review->matchID, "reviewmark")) {
                            $tmp = "(Update)";
                        }
                        $html .= "<br><br><a href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&reviewid=$assignedReviewIndex&appealtype=reviewmark")."'>View/Respond to Review Mark Appeal $tmp</a><br>";
                    }
                    else if($NOW < $assignment->appealStopDate)
                    {
                        //Show them a link to launching an appeal
                        $html .= "<br><br><a href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&reviewid=$assignedReviewIndex&appealtype=reviewmark")."'>Appeal Review Mark </a><br>";
                    }
                    $reviewCount++;
                    break;
                }
            }

            //Show them the reviews that this submission
            if($showStudentReviews || $showInstructorReviews)
            {
                $reviewIndex=0;
                //Next, do all of the other reviews
                foreach($reviews as $review)
                {
                    if($review->reviewerID->id != $USERID->id)
                    {
                        if(sizeof($review->answers) == 0)
                            continue;

                        if($dataMgr->isInstructor($review->reviewerID)) {
                            if(!$showInstructorReviews)
                                continue;
                            $html.= "<h1>Review $reviewCount (Instructor Review)</h1>\n";
                        } else {
                            if(!$showStudentReviews)
                                continue;
                            if($dataMgr->isMarker($review->reviewerID)) {
                              $html.= "<h1>Review $reviewCount (TA Review)</h1>\n";
                            }
                            else {
                              $html.= "<h1>Review $reviewCount</h1>\n";
                            }
                        }

                        $reviewCount++;
                        if($showAppealLinks)
                        {
                            //Now we need to do the stuff for appeals
                            if($assignment->appealExists($review->matchID, "review"))
                            {
                                //Show them a link for editing their appeals
                                $tmp = "";
                                if($assignment->hasNewAppealMessage($review->matchID, "review")) {
                                    $tmp = "(Update)";
                                }
                                $html .= "<a href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&reviewid=$reviewIndex&appealtype=review")."'>View/Respond to Appeal $tmp</a><br>";
                            }
                            else if($NOW < $assignment->appealStopDate)
                            {
                                //Show them a link to launching an appeal
                                $html .= "<a href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&reviewid=$reviewIndex&appealtype=review")."'>Appeal Review</a><br>";
                            }
                        }

                        $html .= $review->getHTML();
                        if($showReviewMarks && !$dataMgr->isInstructor($review->reviewerID))
                        {
                            $html .= "<h2 class='altHeader'>Review $reviewCount Mark</h2>\n";
                            $html .= $assignment->getReviewMark($review->matchID)->getHTML($assignment->maxReviewScore);
                        }
                    }
                    $reviewIndex++;
                }
            }
            return $html;
        }
        //Signature: getTabHTML(submissionID, show the submission mark, show reviews, show the review marks, show the appeal buttons)

        //The first tab, our submission
        $tabOffset = 1;
        if($assignment->submissionExists($USERID))
        {
            $content .= "<div id='tabs-$tabOffset'>\n";
            $content .= getTabHTML($assignment->getSubmissionID($USERID), true, true, true, $assignment->showMarksForReviewsReceived, true);
            $content .= "</div>\n";
            $tabOffset++;
        }

        //Next, we need the other submissions
        for($i = 0; $i < sizeof($assignedReviews); $i++)
        {
            $tabIndex= $i+$tabOffset;
            $content .= "<div id='tabs-$tabIndex'>\n";
            $content .= getTabHTML($assignment->getSubmissionID($assignedReviews[$i]), $assignment->showMarksForReviewedSubmissions, $assignment->showOtherReviewsByStudents, $assignment->showOtherReviewsByInstructors, $assignment->showMarksForOtherReviews, false);
            $content .= "</div>\n";
        }

        $content .= "</div>";
    }

    render_page();
} catch(Exception $e) {
    render_exception_page($e);
}

?>
