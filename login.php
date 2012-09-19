<?php
include("inc/common.php");

if(!$dataMgr->courseID)
{
    //Give them a 404
    page_not_found();
}

try
{
    $loginAttemptFailed = false;
    $username = '';
    if(isset($_POST["username"])){ $username = $_POST['username']; }

    if (array_key_exists("action", $_POST) && $_POST["action"]=="login")
    {
        $loginAttemptFailed = !$authMgr->performLogin($_POST['username'], $_POST['password']);
    }

    #Now, if we've gotten here, we need to see if the user is logged in
    if ($authMgr->isLoggedIn()) {
        #Silly user, they are logged in - take them to the main page
        redirect_to_main();
    } else {
        #They are not logged in, we need to give them the option

        $content =
    '<div class="box">
    <form action="?" method="post"><input type="hidden" name="action" value="login">
    <table>
        <tr><td>Username: </td><td><input type="text" name="username" value="'.$username.'"/></td></tr>
        <tr><td>Password: </td><td><input type="password" name="password" /></td></tr>
    </table>
    <table>
        <tr><td><input type="submit" value="Login" /></td></tr>
    </table>
    </form>
    </div>';

        if($authMgr->registrationOpen())
        {
            $content .= "<a href='".get_redirect_url("register.php")."'>Click here to register</a>\n";
        }

        $title .= " Login";
        if($loginAttemptFailed)
        {
            $content .= "<div> Login Failed - Check your password, or contact your TA to make sure your username is valid <br>If you have already been able to log in but are unable to now, this is probably because your account has been switched to your CS ID. Go <a href='https://www.cs.ubc.ca/ugrad/getacct/getacct.jsp'>here</a> to reset your password/find your ID </div>";
        }
        render_page();
    }
}catch(Exception $e){
    render_exception_page($e);
}
?>
