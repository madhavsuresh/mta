<?php
include("inc/common.php");
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
        $content .= "<li><a href='#tabs-1'>My Submission</a></li>\n";
        for($i = 1; $i <= sizeof($assignedReviews); $i++)
        {
            $tabIndex= $i+1;
            $content .= "<li><a href='#tabs-$tabIndex'>Review $i</a></li>\n";
        }
        $content .= "</ul>";


        //Define a function that will render a tab for us
        function getTabHTML(SubmissionID $submissionID, $showSubmissionMark, $showReviews, $showReviewMarks, $showAppealLinks)
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

            $reviews = $assignment->getReviewsForSubmission($submissionID);
            $reviewCount = 0;
            //Do the first pass, and see if we can find this user's submission
            foreach($reviews as $review)
            {
                if($review->reviewerID->id == $USERID->id)
                {
                    $html .= "<h1>My Review</h1>\n";
                    $html .= $review->getHTML();
                    $html .= "<h2 class='altHeader'>My Review Mark</h2>\n";
                    $html .= $assignment->getReviewMark($review->matchID)->getHTML($assignment->maxReviewScore);
                    $reviewCount++;
                    break;
                }
            }

            //Show them the reviews that this submission
            if($showReviews)
            {
                $reviewIndex=0;
                //Next, do all of the other reviews
                foreach($reviews as $review)
                {
                    if($review->reviewerID->id != $USERID->id)
                    {
                        $reviewCount++;

                        if($dataMgr->isInstructor($review->reviewerID)) {
                            $html.= "<h1>Review $reviewCount (Instructor Review)</h1>\n";
                        } else {
                            $html.= "<h1>Review $reviewCount</h1>\n";
                        }
                        if($showAppealLinks)
                        {
                            //Now we need to do the stuff for appeals
                            if($assignment->appealExists($review->matchID))
                            {
                                //Show them a link for editing their appeals
                                $tmp = "";
                                if($assignment->hasNewAppealMessage($review->matchID)) {
                                    $tmp = "(Update)";
                                }
                                $html .= "<a href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&reviewid=$reviewIndex")."'>View/Respond to Appeal $tmp</a><br>";
                            }
                            else if($NOW < $assignment->appealStopDate)
                            {
                                //Show them a link to launching an appeal
                                $html .= "<a href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&reviewid=$reviewIndex")."'>Appeal Review</a><br>";
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
        $content .= "<div id='tabs-1'>\n";
        $content .= getTabHTML($assignment->getSubmissionID($USERID), true, true, $assignment->showMarksForReviewsReceived, true);
        $content .= "</div>\n";

        //Next, we need the other submissions
        for($i = 0; $i < sizeof($assignedReviews); $i++)
        {
            $tabIndex= $i+2;
            $content .= "<div id='tabs-$tabIndex'>\n";
            $content .= getTabHTML($assignment->getSubmissionID($assignedReviews[$i]), $assignment->showMarksForReviewedSubmissions, $assignment->showOtherReviews, $assignment->showMarksForOtherReviews, false);
            $content .= "</div>\n";
        }

        $content .= "</div>";
    }

    render_page();
} catch(Exception $e) {
    render_exception_page($e);
}

?>
