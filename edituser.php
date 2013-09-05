<?php
require_once("inc/common.php");
try
{
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    //Load up the variables that we might have gotten from post
    $alias = $dataMgr->getUserAlias($USERID);
    if(isset($_POST["alias"])) { $alias = trim($_POST["alias"]); }

    //Figure out what we're supposed to be doing
    $action = optional_from_get("action");

    if($action == "save")
    {
        //Make sure the user name isn't something evil
        if(strlen($alias) == 0)
            $alias = NULL;
        $dataMgr->setUserAlias($USERID, $alias);
        $content .= "Saved!";

        //What Kevin wants, Kevin gets.....
        redirect_to_main();
    }

    //We need to show the registration page
    $title .= " | Edit User";

    $content .= "<h3>".$dataMgr->getUserDisplayName($USERID)." Settings</h3>";
    $content .= "<form id='register' action='".get_redirect_url("edituser.php?action=save")."' method='post'>\n";
    $content .= "<table width='100%'>\n";
    $content .= "<tr><td>Alias</td><td>";
    $content .= "<input type='text' name='alias' value='$alias' size='50'/></td></tr>";
    $content .= "</table>\n";

    $content .= "<input type='submit' value='Save'/>\n";
    $content .= "</form>\n";

    render_page();
}catch(Exception $e) {
    render_exception_page($e);
}

?>

