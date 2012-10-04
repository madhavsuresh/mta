<?php
require_once("inc/common.php");
try
{
    $title .= " | Edit Group Selections";

    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    $assignment = get_grouppicker_assignment();
    $groupMap = $assignment->getGroups();

    $userDisplayMap = $dataMgr->getUserDisplayMap();
    //Load this stuff up from the post
    $deleted = array();
    foreach($userDisplayMap as $userID => $_)
    {
        if(isset($_POST["user$userID"]))
        {
            if($_POST["user$userID"] != '')
            {
                $groupMap[$userID] = $_POST["user$userID"];
            }
            else
            {
                unset($groupMap[$userID]);
            }
        }
    }

    if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'Save')
    {
        //We need to save the list
        $assignment->saveGroups($groupMap);
        redirect_to_main();
    }

    $content .= "<h1>Edit Group Assignment For ".$assignment->name."</h1>\n";

    $content .= "<form id='groups' action='".get_redirect_url("?assignmentid=$assignment->assignmentID")."' method='post'>";
    $content .= "<table width='100%'>\n";
    foreach($userDisplayMap as $userID => $userDisplayName)
    {
        if(!$dataMgr->isStudent(new UserID($userID)))
            continue;
        $content .= "<tr><td>$userDisplayName</td><td>";
        $content .= "<select name='user$userID'>\n";
        $content .= "<option value=''> </option>\n";
        for($i = 0; $i < sizeof($assignment->groups); $i++)
        {
            $selected = '';
            if(isset($groupMap[$userID]) && $i == $groupMap[$userID])
                $selected = 'selected';
            $content .= "<option value='$i' $selected>".$assignment->groups[$i]."</option>\n";
        }

        $content .= "</select></td>\n";
        $content .= "</td></tr>\n";
    }
    $content .= "</table>\n";
    $content .= "<br><input type='submit' name='action' value='Save' />\n";
    $content .= "</form>\n";

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
