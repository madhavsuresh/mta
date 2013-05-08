<?php
require_once("inc/common.php");
try
{
    if(!isset($adminFileSkip) || !$adminFileSkip)
        $authMgr->enforceInstructor();
    if(!isset($extraUrl))
        $extraUrl = "";
    $dataMgr->requireCourse();

    function getTypeRow($currentType = '')
    {
        $html = "<tr><td>Type</td><td><select name='userType'>\n";
        foreach(array("student"=>"Student", "marker"=>"Marker", "instructor"=>"Instructor") as $type => $name){
            $html .= "<option";
            if($currentType == $type)
                $html .= " selected='selected'";
            $html .= " value='$type'>$name</option>\n";
        }
        $html .= "</select></td></tr>";
        return $html;
    }

    if(array_key_exists("save", $_GET)){
        //Do we have a password - if so update it?
        if(array_key_exists("password", $_POST) && strlen($_POST["password"]) > 0){
            $authMgr->addUserAuthentication($_POST["username"], $_POST["password"]);
        }
        //Do everything else
        if(array_key_exists("userID", $_POST)){
            $dataMgr->updateUser(new UserID($_POST["userID"]), $_POST["username"], $_POST["firstname"], $_POST["lastname"], $_POST["studentid"], $_POST["userType"]);
        }else{
            $dataMgr->addUser($_POST["username"], $_POST["firstname"], $_POST["lastname"], $_POST["studentid"], $_POST["userType"]);
        }
        //The save completed without issue, fall back to the main page
    }

    if(array_key_exists("new", $_GET))
    {
        //If we're editing, then these variables have all been filled up
        $content .= $authMgr->getRegistrationFormHTML("", "", "", "", getTypeRow(), !$authMgr->supportsSettingPassword(), "?courseid=$dataMgr->courseID&save=1", true);
    }
    else if (array_key_exists("edit", $_GET))
    {
        $user = $dataMgr->getUserInfo(new UserID($_GET["edit"]));
        $content .= $authMgr->getRegistrationFormHTML($user->username, $user->firstName, $user->lastName, $user->studentID, getTypeRow($user->userType) . "<input type='hidden' name='userID' value='$user->userID' />", !$authMgr->supportsSettingPassword(), "?courseid=$dataMgr->courseID&save=1", true);
    }
    else
    {
        //Give the option to add a student
        $content .= "<a href='?new=1'>New User</a><br><br>\n";
        $content .= "<h2>Registered Users</h2>\n";
        //We need to display a list of all the users...
        $userMap = $dataMgr->getUserDisplayMap();
        foreach($userMap as $id => $displayName)
        {
            $content .= "<a href='?edit=$id"."$extraUrl'>$displayName</a><br>\n";
        }
    }
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
