<?php
require_once("inc/common.php");
try
{
    $title .= " | Edit Peer Review Assignment";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    #Get the assignment data
    $assignment = get_peerreview_assignment();

    #Get the reviewer assignment from the data manager
    $submissionAuthorMap = $assignment->getAuthorSubmissionMap();
    $reviewerAssignment = $assignment->getReviewerAssignment();
    $userDisplayMap = $dataMgr->getUserDisplayMap();

    //Load up everything in the post
    foreach($submissionAuthorMap as $_ => $submissionID){
        $i = 0;
        while(array_key_exists($submissionID."_$i", $_POST))
        {
            if($_POST[$submissionID."_$i"])
            {
                if(!array_key_exists($submissionID->id, $reviewerAssignment))
                    $reviewerAssignment[$submissionID->id] = array();
                $reviewerAssignment[$submissionID->id][$i] = new UserID($_POST[$submissionID."_$i"]);
            }
            else
            {
                //They've tried to remove this one
                unset($reviewerAssignment[$submissionID->id][$i]);
            }
            $i++;
        }
    }

    #Figure out how many columns we have
    $numCols = 1;
    if(array_key_exists('numcols', $_GET)){
        $numCols = $_GET['numcols'];
        if(array_key_exists("action", $_REQUEST))
        {
            if($_REQUEST['action'] == 'Add Column')
                $numCols++;
            if($_REQUEST['action'] == 'Remove Column')
                $numCols--;
            if($numCols == 0)
                $numCols = 1;
        }
        $numCols = max(0, $numCols);
    }
    else
    {
        //Quickly count the number of elements
       $numCols = max(1, array_reduce($reviewerAssignment, function($res, $item) { return max($res, sizeof($item)); }));
    }

    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'Save'){
        //If we are saving, be sure to remove extra columns

        $assignment->saveReviewerAssignment($reviewerAssignment);
        #We're good, go to home
        redirect_to_main();
    }


    $deniedUsers = $assignment->getDeniedUsers();
    $independentUsers = $assignment->getIndependentUsers();
    $availibleReviewers = array();

    #Pre-process the user names so that they have a star if they are indepednent
    //TODO: Add something to indicate that such a review exists
    foreach($userDisplayMap as $user => $userName){
        if(!$dataMgr->isStudent(new UserID($user)))
            continue;
        $validUser = false;
        if(array_key_exists($user, $submissionAuthorMap))
        {
            if(array_key_exists($user, $independentUsers)){
                $userName = "*".$userName;
            }else{
                $userName = "&nbsp;".$userName;
            }
            $validUser = true;
        }
        else if(!array_key_exists($user, $deniedUsers))
        {
            $userName = "+".$userName;
            $validUser = true;
        }
        if($validUser)
        {
            $availibleReviewers[$user] = $userName;
            $userDisplayMap[$user] = $userName;
        }
    }

    $content .= "<h1>Edit Peer Review Assignments</h1>\n";
    $content .= "A (*) beside a user&#39;s name means they are independent.<br>\n";

    $content .= "<form id='users' action='".get_redirect_url("?assignmentid=$assignment->assignmentID&numcols=$numCols")."' method='post'>";

    $content .= "<table width'=%100'>\n";
    $content .= "<tr><td style='text-align:center'><input type='submit' name='action' value='Remove Column' /></td><td style='text-align:center'><input type='submit' name='action' value='Add Column' /></td></tr>\n";
    $content .= "</table><br>\n";
    $content .= "<table align='left' width='100%'>\n";
    $content .= "<tr><td><h2 style='text-align:center'>User</h2></td>\n";
    for($i=1; $i <= $numCols; $i++){
        $content .= "<td><h2 style='text-align:center'>Reviewer $i</h2></td>";
    }
    $content .= "</tr>\n";

    $currentRowType = 0;
    $width = 450/$numCols;
    $width = "style='width:$width px'";

    foreach($userDisplayMap as $author => $authorName){
        if(!array_key_exists($author, $submissionAuthorMap))
            continue;
        $submissionID = $submissionAuthorMap[$author];
        $content .= "<tr class='rowType$currentRowType'><td width='200px'>$authorName</td>\n";
        for($i = 0; $i < $numCols; $i++)
        {
            $content .= "<td style='text-align:center'><select name='$submissionID"."_$i' $width>\n";
            $content .= "<option value=''> </option>\n";
            foreach($availibleReviewers as $reviewer => $reviewerName){
                if($reviewer == $author)
                    continue;
                $selected = '';
                if(isset($reviewerAssignment[$submissionID->id][$i]) && $reviewer == $reviewerAssignment[$submissionID->id][$i]->id)
                    $selected = 'selected';
                $content .= "<option value='$reviewer' $selected>".$reviewerName."</option>\n";
            }
            $content .= "</select></td>\n";
        }
        $currentRowType = ($currentRowType+1)%2;
    }

    $content .= "<tr><td>&nbsp;</td><td>\n";
    $content .= "</table>\n";
    $content .= "<br><input type='submit' name='action' value='Save' />\n";
    $content .= "</form></div>\n";

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
?>
