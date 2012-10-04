<?php
require_once("inc/common.php");
try
{
    $dataMgr->requireCourse();

    if(!$authMgr->registrationOpen())
    {
        $content .= "Registration is not possible for $dataMgr->courseDisplayName";
        render_page();
    }

    //Load up the variables that we might have gotten from post
    $errors = '';
    $username = '';
    $password = '';
    $firstname = '';
    $lastname = '';
    $studentid = '';
    $registrationCode = '';
    if(isset($_POST["username"])) { $username = $_POST["username"]; }
    if(isset($_POST["password"])) { $password = $_POST["password"]; }
    if(isset($_POST["firstname"])) { $firstname = $_POST["firstname"]; }
    if(isset($_POST["lastname"])) { $lastname = $_POST["lastname"]; }
    if(isset($_POST["studentid"])) { $studentid = $_POST["studentid"]; }
    if(isset($_POST["registrationcode"])) { $registrationcode = $_POST["registrationcode"]; }

    //Figure out what we're supposed to be doing
    $action = optional_from_get("action");
    $addUserAuthentication = array_key_exists("__adduserauth", $_POST);
    $loginAttemptFailed = false;

    if($action == "register")
    {
        //Make sure the user name isn't something evil
        $authMgr->validateUserName($username);

        //check to make sure the registration code matches (if we need it)
        if(!$authMgr->checkRegistrationCode($registrationCode))
        {
            //Let the user know they have an invalid registration code
            $content = "Registration code was invalid";
            render_page();
        }
        else
        {
            //Add in the authentication
            if($addUserAuthentication)
            {
                if($authMgr->userNameExists($username))
                {
                    $content = "Username '$username' is already in use";
                    render_page();
                }
                $authMgr->addUserAuthentication($username, $password);
            }

            //Check to see if this authentication worked
            if($authMgr->checkAuthentication($username, $password))
            {
                //Yes, we need to add them
                if($authMgr->supportsGettingFirstAndLastNames())
                {
                    $names = $authMgr->getFirstAndLastNames($username);
                    $firstname = $names[0];
                    $lastname = $names[1];
                }
                if($authMgr->supportsGettingStudentID())
                {
                    $studentid = $authMgr->getStudentID($username);
                }
                $dataMgr->addUser($username, $firstname, $lastname, $studentid);

                //Run up the fact they are now good to go
                $content .= "Registration completed. <a href='".get_url_to_main()."'>Go to course</a>\n";
                render_page();
            }
            else
            {
                $loginAttemptFailed = true;
            }
        }
    }

    //We need to show the registration page
    $title .= " | Register";

    $content .= "Use your CS account username/password to enroll in the course. Even if you are on the wait list, you will be able to get an account from <a href='https://www.cs.ubc.ca/ugrad/getacct/getacct.jsp'>here</a>. If you can't remember your password, you can reset your password <a href='https://www.cs.ubc.ca/ugrad/getacct/getacct.jsp'>here</a><br><br>";
    $content .= $authMgr->getRegistrationFormHTML($username, $firstname, $lastname, $studentid);


    if($loginAttemptFailed)
    {
        $content .= "Login failed - check your username and password";
    }
    render_page();
}catch(Exception $e) {
    render_exception_page($e);
}

?>
