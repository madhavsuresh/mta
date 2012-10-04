<?php
require_once('inc/common.php');
try
{
    $authMgr->enforceLoggedIn();
    $dataMgr->requireCourse();

    #What are they trying to do?
    $action = require_from_get("action", false);

    switch($action){
    case 'select':
        $authMgr->enforceInstructor();
        $title .= " | Become Student";

        $content .= "<h1>Select User</h1></div>\n";
        $content .= "<div class='contentText'>\n";
        $content .= "<table align='left'>\n";

        foreach($dataMgr->getUserDisplayMap() as $user => $name)
        {
            if(!$dataMgr->isStudent(new UserID($user))){
                continue;
            }
            $content .= "<tr><td><a href='?action=assign&userid=$user'>$name</a></td></tr>";
        }
        $content .= "</table>\n";
        $content .= "</div>";
        render_page();
        break;
    case 'return':
        $authMgr->returnToInstructor();
        redirect_to_main();
        break;
    case 'assign':
        $authMgr->enforceInstructor();
        $userid = require_from_get("userid");
        $authMgr->becomeUser(new UserID($userid));
        redirect_to_main();
        break;
    default:
        throw new Exception("unknown action '$action'");
    }
}catch(Exception $e){
    render_exception_page($e);
}
?>

