<?php
require_once("inc/authmanager.php");

class MultiLevelAuthManager extends AuthManager
{

    private $authMethods;

    function __construct($registrationType, $dataMgr)
    {
        parent::__construct($registrationType, $dataMgr);

        require_once("ldapauthmanager.php");
        require_once("pdoauthmanager.php");

        $this->authMethods = array(new LDAPAuthManager($registrationType, $dataMgr), new PDOAuthManager($registrationType, $dataMgr));

    }

    function checkAuthentication($username, $password) {
        foreach($this->authMethods as $auth)
        {
            if($auth->checkAuthentication($username, $password))
                return true;
        }
        return false;
    }

    function supportsAddingUsers()
    {
        return true;
    }

    function supportsGettingFirstAndLastNames()
    {
        return false;
    }

    function supportsGettingStudentID()
    {
        return false;
    }

    function userExists($username)
    {
        foreach($this->authMethods as $auth)
        {
            if($auth->userExists($username))
                return true;

        }
        return false;
    }

    function addUserAuthentication($username, $password)
    {
        return $authMethods[1]->addUserAuthentication($username, $password);
    }
    /*
     *
     *
    if($action == "createuser")
    {
        $content .= "<h1>Register New User For $dataMgr->courseDispalyName</h1>\n";
    }
    else
    {
        $content .= "<h1>Register For $dataMgr->courseDisplayName</h1>\n";
    }
    $content .= "<div class='box'>\n";
    $content .= "To register for the course, fill in the following information.\n";
    if($action == "createuser")
    {
        $content .= "You should only complete this step if you were unable to register on the previous page";
    }
    else //if($authMgr->canAddUsers())
    {
        $content .= "In the event that you are not able to login, <a href='".get_redirect_url("?action=createuser")."'>click here</a>\n";
    }
    $content .= "</div>\n";
     *
     */
}

?>

