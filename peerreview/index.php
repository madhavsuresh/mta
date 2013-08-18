<?php
require_once("inc/common.php");
try
{
    $title .= " | Mark Assignment";
    $dataMgr->requireCourse();
    $authMgr->enforceMarker();

    $hideBlank = 0;
    if(array_key_exists("hideblank", $_GET))
    {
        $hideBlank = $_GET["hideblank"];
    }
    $hideEdit = 1;
    if(array_key_exists("hideedit", $_GET))
    {
        $hideEdit = $_GET["hideedit"];
    }

    //Get the assignment
    $assignment = get_peerreview_assignment(false);

    $content .= "<h1>$assignment->name</h1>\n";

    $content .= "<h2>Question</h2>\n";
    $content .= $assignment->submissionQuestion;

    $content .= "<h2>Review Questions</h2>\n";
    $content .= "<ol>\n";
    foreach($assignment->getReviewQuestions() as $question)
    {
       $content .= "<li>".cleanString($question->question)."</li>\n";
    }
    $content .= '</ol>';

    //#Glob the whole friggin directory for submissions and reviews
    $submissionAuthors = $assignment->getAuthorSubmissionMap();
    $reviewMap = $assignment->getReviewMap();
    $scoreMap = $assignment->getMatchScoreMap();
    $deniedUsers = $assignment->getDeniedUsers();
    $appealMap = $assignment->getReviewAppealMap();
    $markAppealMap = $assignment->getReviewMarkAppealMap();
    $spotCheckMap = $assignment->getSpotCheckMap();
    $stats = $assignment->getAssignmentStatistics();
    $userStats = $assignment->getAssignmentStatisticsForUser($USERID);
    $displayMap = $dataMgr->getUserDisplayMap();

    //Start making the big table
    $content .= "<h1>Submissions (".$stats->numSubmissions."/".$stats->numPossibleSubmissions.") and Reviews (".$stats->numStudentReviews."/".$stats->numPossibleStudentReviews.")</h1>";
    $content .= "There are ".$stats->numUnmarkedSubmissions." unmarked submissions, ".$stats->numUnmarkedReviews." unmarked reviews, ".$stats->numPendingAppeals." pending appeals and ".$stats->numPendingSpotChecks." pending spot checks<br>\n";
    $content .= "You have ".$userStats->numUnmarkedSubmissions." unmarked submissions, ".$userStats->numUnmarkedReviews." unmarked reviews and ".$userStats->numPendingSpotChecks." pending spot checks<br>\n";

    if($hideBlank) {
        $content .= "<a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&hideblank=0&hideedit=$hideEdit")."'>Show Blank Submissions</a>\n";
    }else{
        $content .= "<a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&hideblank=1&hideedit=$hideEdit")."'>Hide Blank Submissions</a>\n";
    }
    if($hideEdit) {
        $content .= "<a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&hideblank=$hideBlank&hideedit=0")."'>Show All Edit Buttons</a>\n";
    }else{
        $content .= "<a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&hideblank=$hideBlank&hideedit=1")."'>Hide All Edit Buttons</a>\n";
    }
    $content .= "<a title='New' target='_blank' href='".get_redirect_url("peerreview/editsubmission.php?assignmentid=$assignment->assignmentID&authorid=".$assignment->getUserIDForAnonymousSubmission($USERID, $authMgr->getCurrentUsername())."&close=1")."'>Create Instructor Submission</a>\n";

    #Now start going through stuff by user names
    $content .= "<table width='100%'>\n";
    $currentRowIndex = 0;
    $currentRowType = 0;
    foreach($displayMap as $authorID => $authorName)
    {
        $authorID = new UserID($authorID);
        if((!$dataMgr->isStudent($authorID) && !array_key_exists($authorID->id, $submissionAuthors)) || $assignment->deniedUser($authorID) || ($hideBlank && !array_key_exists($authorID->id, $submissionAuthors)))
        {
            continue;
        }
        $submissionID = null;
        if(array_key_exists($authorID->id, $submissionAuthors))
            $submissionID = $submissionAuthors[$authorID->id];
        $currentRowType = ($currentRowType+1)%2;
        $currentRowIndex++;

        $content .= "<tr class='rowType$currentRowType'><td align='right' width='30'>$currentRowIndex.</td><td align='left'>";

        //The first real slot, has the submission in it
        $content .= "<table align='left' width='100%'><tr><td>\n";
        if($submissionID)
        {
            $content .= "<a title='View' href='".get_redirect_url("peerreview/viewer.php?assignmentid=$assignment->assignmentID&type0=submission&submissionid0=$submissionID")."'>$authorName</a>";
        }
        else
        {
            $content .= "$authorName";
        }
        $content .= "</td></tr><tr><td>\n";
        $content .= "<table align='right'><tr>\n";
        if($submissionID)
        {
            //#Add the delete and edit buttons
            if(!$hideEdit){
                $content .= "<td align='right' width='18'><a title='Delete' href='".get_redirect_url("peerreview/delete.php?assignmentid=$assignment->assignmentID&type=submission&authorid=$authorID")."'><div class='icon delete'></div></a></td>\n";
                $content .= "<td align='right' width='18'><a title='Edit' target='_blank' href='".get_redirect_url("peerreview/editsubmission.php?assignmentid=$assignment->assignmentID&authorid=$authorID&close=1")."'><div class='icon edit'></div></a></td>\n";
            }else{
                $content .= "<td align='right' width='18'>&nbsp;</td>";
                $content .= "<td align='right' width='18'>&nbsp;</td>";
            }

            #Close the edit buttons table
            $content .= "</tr></table></td><td>";

            #Has this been graded?
            $content .= "<td width='20' style='text-align:center'><a  target='_blank' class='editmarklink' title='Mark' href='".get_redirect_url("peerreview/marksubmission.php?assignmentid=$assignment->assignmentID&submissionid=$submissionID")."'>".$assignment->getSubmissionMark($submissionID)->getSummaryString()."</a></td>\n";
        }
        else
        {
            $content .= "<td align='right' width='18'>&nbsp;</td>\n";
            $content .= "<td align='right' width='18'><a target='_blank' title='Edit' href='".get_redirect_url("peerreview/editsubmission.php?assignmentid=$assignment->assignmentID&authorid=$authorID&close=1")."'><div class='icon edit'></div></a></td>\n";
            $content .= "<td align='right' width='18'>&nbsp;</td>\n";
            $content .= "</tr></table></td><td>\n";
        }

        $content .= "</tr></table></td><td>\n";

        #Middle Cell - Stuff about reviews
        $content .= "<table align='left' width='100%'>";

        if($submissionID && array_key_exists($submissionID->id, $reviewMap))
        {
            foreach($reviewMap[$submissionID->id] as $reviewObj)
            {
                $reviewerName = $displayMap[$reviewObj->reviewerID->id];
                $content .= "<tr><td>";

                if($reviewObj->exists)
                {
                    $content .= "<a title='View' href='".get_redirect_url("peerreview/viewer.php?assignmentid=$assignment->assignmentID&type0=review&matchid0=$reviewObj->matchID")."'>Review by $reviewerName</a></td><tr>";
                    $content .= "<tr><td><table align='right'><tr>";
                    $score = precisionFloat($scoreMap[$reviewObj->matchID->id]);
                    $content .= "<td>(Gave&nbsp;score&nbsp;of&nbsp;$score)</td>";

                    if(!$hideEdit || $reviewObj->instructorForced){
                        $content.= "<td align='right' width='18'><a title='Delete' href='".get_redirect_url("peerreview/delete.php?assignmentid=$assignment->assignmentID&type=review&matchid=$reviewObj->matchID")."'><div class='icon delete'></div></a></td>\n";
                        $content .= "<td align='right' width='18'><a target='_blank' title='Edit' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&matchid=$reviewObj->matchID&close=1")."'><div class='icon edit'></div></a></td>\n";                  }
                    else{
                        $content .= "<td align='right' width='18'>&nbsp;</td>\n";
                        $content .= "<td align='right' width='18'>&nbsp;</td>\n";
                    }

                    if(!$reviewObj->instructorForced)
                    {
                        $content .= "<td width='20' style='text-align:center'><a class='editmarklink' target='_blank' title='Mark' href='".get_redirect_url("peerreview/markreview.php?assignmentid=$assignment->assignmentID&matchid=$reviewObj->matchID")."'>".$assignment->getReviewMark($reviewObj->matchID)->getSummaryString()."</a></td>";
                    }else{
                        $content .= "<td width='20'>&nbsp;</td>\n";
                    }
                    //Is there an appeal for this review?
                    if(array_key_exists($reviewObj->matchID->id, $appealMap))
                    {
                        //Is this an appeal that needs a response?
                        if($appealMap[$reviewObj->matchID->id])
                            $appealText = "Unanswered Appeal";
                        else
                            $appealText = "Appeal";
                        $content .= "</tr><td colspan='2'><a target='_blank' href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&close=1&matchid=$reviewObj->matchID&appealtype=review")."'>$appealText</a></td>";
                    }
                    //Is there an appeal for this review's mark?
                    if(array_key_exists($reviewObj->matchID->id, $markAppealMap))
                    {
                        //Is this an appeal that needs a response?
                        if($markAppealMap[$reviewObj->matchID->id])
                            $appealText = "Unanswered Mark Appeal";
                        else
                            $appealText = "Mark Appeal";
                        $content .= "</tr><td colspan='2'><a target='_blank' href='".get_redirect_url("peerreview/editappeal.php?assignmentid=$assignment->assignmentID&close=1&matchid=$reviewObj->matchID&appealtype=reviewmark")."'>$appealText</a></td>";
                    }
                    $content .= "</tr></table></td></tr>";
                }
                else
                {
                    $content .= "Missing review by $reviewerName</td></tr><tr><td>\n";
                    $content .= "<table align='right'><tr>\n";
                    $content .= "<td align='right' width='18'><a target='_blank' title='Edit' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&submissionid=$submissionID&matchid=$reviewObj->matchID&close=1")."'><div class='icon edit'></div></a></td>\n";
                    $content .= "<td align='right' width='18'>&nbsp;</td></tr></table></td></tr>\n";
                }
            }
        }

        $content .= "</table>\n";

        //#Last Column, Add the list of big actions for this author's work
        $content .= "<td>";
        if($submissionID)
        {
            $content .= "<table>";
            $args = "type0=submission&submissionid0=$submissionID";
            $i=1;
            if(array_key_exists($submissionID->id, $reviewMap))
            {
                foreach($reviewMap[$submissionID->id] as $reviewObj)
                {
                    if($reviewObj->exists)
                    {
                        $args .= "&type$i=review&&matchid$i=$reviewObj->matchID";
                        $i++;
                    }
                }
            }
            $content .= "<tr><td><a href='viewer.php?assignmentid=$assignment->assignmentID&$args'>View All Reviews</a></td></tr>";
            $content .= "<tr><td><a target='_blank' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&submissionid=$submissionID&reviewer=anonymous&close=1")."'>Add Anonymous Review</a></td></tr>\n";
            $content .= "<tr><td><a target='_blank' href='".get_redirect_url("peerreview/editreview.php?assignmentid=$assignment->assignmentID&submissionid=$submissionID&reviewer=instructor&close=1")."'>Add Instructor Review</a></td></tr>\n";
            if(array_key_exists($submissionID->id, $spotCheckMap))
            {
                $spotCheck = $spotCheckMap[$submissionID->id];
                $content .= "<tr><td><br><a  target='_blank' href='viewer.php?assignmentid=$assignment->assignmentID&$args&type$i=spotcheck&submissionid$i=$submissionID'>Spot check by ".$displayMap[$spotCheck->checkerID->id]."<br>(".$spotCheck->getStatusString().")</a></td></tr>";
            }
            $content .= "</table>";
        }


        $content .= "</td></tr>";
    }
    $content .= "</table>\n";

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
