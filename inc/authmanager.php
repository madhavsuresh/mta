<?php

abstract class AuthManager
{
    abstract function checkAuthentication($username, $password);

    abstract function supportsAddingUsers();
    abstract function supportsGettingFirstAndLastNames();
    abstract function supportsGettingStudentID();

    public $registrationType = "";
    public $registrationCode = "";

    protected $dataMgr;

    function __construct($registrationType, $dataMgr)
    {
        $this->registrationType = $registrationType;
        if($this->registrationType == "code")
        {
           $this->registrationCode = $this->dataMgr->getConfigProperty("registrationcode");
        }
        $this->dataMgr = $dataMgr;
    }

    function isLoggedIn()
    {
        global $_SESSION;
        return array_key_exists("loggedID", $_SESSION) && $this->dataMgr->isUser(new UserID($_SESSION["loggedID"]));
    }

    function enforceLoggedIn(){
        if (!$this->isLoggedIn()) {
            redirect_to_page("login.php");
        }
    }

    function enforceInstructor(){
        #Make sure that they are logged in, and an instructor
        global $USERID;
        if(!$this->isLoggedIn() || ! $this->dataMgr->isInstructor($USERID)) {
            redirect_to_page("login.php");
        }
    }

    function enforceMarker(){
        #Make sure that they are logged in, and an instructor
        global $USERID;
        if(!$this->isLoggedIn() || ! $this->dataMgr->isMarker($USERID)) {
            redirect_to_page("login.php");
        }
    }

    function performLogin($username, $password)
    {
        global $_SESSION;

        $this->validateUserName($username);

        #Check to make sure we should even bother with the authentication
        if($username != "" && $password != "") {
            #Is this person a registered user?
            if($this->dataMgr->isUserByName($username)) {
                #They do exist, time to preform the actual authentication
                if($this->checkAuthentication($username, $password))
                {
                    $_SESSION["loggedID"] = $this->dataMgr->getUserID($username)->id;
                    $_SESSION["logged"] = $username;
                    return true;
                }
            }
        }
        return false;
    }

    function getCurrentUsername()
    {
        if(isset($_SESSION["logged"]))
            return $_SESSION["logged"];
        else return NULL;
    }

    function becomeUser(UserID $userid)
    {
        $this->enforceInstructor();
        #Do the assignment
        $_SESSION["oldInstructorID"] = $_SESSION["loggedID"];
        $_SESSION["oldInstructor"] = $_SESSION["logged"];
        $_SESSION['loggedID'] = $userid->id;
        $_SESSION['logged'] = $this->dataMgr->getUsername($userid);
    }

    function returnToInstructor()
    {
        global $_SESSION;
        if(!array_key_exists('oldInstructorID', $_SESSION) || !array_key_exists('oldInstructor', $_SESSION)){
            throw new Exception("Session does not contain the return user");
        }
        #Set the USER variable properly
        $_SESSION["loggedID"] = $_SESSION["oldInstructorID"];
        $_SESSION["logged"] = $_SESSION["oldInstructor"];
        unset($_SESSION['oldInstructorID']);
        unset($_SESSION['oldInstructor']);
    }

    function registrationOpen()
    {
        return $this->registrationType != "closed";
    }

    function userNameExists($username)
    {
        die("Auth manager did not implement a way to check for users");
    }

    function allowsNewUsers()
    {
        if($this->registrationType == "closed")
            return false;
        else
            return $this->supportsAddingUsers();
    }
    function checkRegistrationCode($code)
    {
        return $this->registrationType != "code" || $this->registrationCode == $code;
    }

    function addUserAuthentication($username, $password)
    {
        throw new Exception("Tried to add a user in a class that does not support it");
    }
    function removeUserAuthentication($username)
    {
        throw new Exception("Tried to remove a user in a class that does not support it");
    }
    function getUserFirstAndLastNames($username)
    {
        throw new Exception("Tried to get the first and last names of a user without a class that supports it");
    }
    function getStudentID($username)
    {
        throw new Exception("Tried to get the student ID of a user without a class that supports it");
    }

    function validateUserName($username)
    {
        if(preg_match("/.*([^a-zA-Z0-9]).*/", $username))
        {
            throw new Exception("Invalid user name");
        }
        return true;
    }

    function getRegistrationFormHeader()
    {
        return "<h1>Register For ".$this->dataMgr->courseDisplayName."</h1>\n";
    }

    function creatingUserInRegistrationForm()
    {
        global $_GET;
        return array_key_exists("createuser", $_GET);
    }


    function getRegistrationFormHTML($username = "", $firstname = "", $lastname = "", $studentid = "", $extraRows="", $markingLoad="", $skipPassword=false, $target=null, $skipIdCheck=false)
    {  	
        if(is_null($target))
            $target = get_redirect_url("?action=register");

        $html = $this->getRegistrationFormHeader();

        $html .= "<form id='register' action='$target' method='post'>\n";
        if($this->creatingUserInRegistrationForm())
        {
            $html .= "<input type='hidden' name='__adduserauth' value='1'>\n";
        }
        $html .= "<table>\n";
        $html .= "<tr><td>Username: </td><td><input type='text' name='username' id='username' value='$username'/></td></tr>\n";
        $html .= "<tr><td colspan='2'><div class=errorMsg><div class='errorField' id='error_username'></div></div></td></tr>\n";
        if(!$skipPassword){
            $html .= "<tr><td>Password: </td><td><input type='password' name='password' id='password'/></td></tr>\n";
            $html .= "<tr><td colspan='2'><div class=errorMsg><div class='errorField' id='error_password'></div></div></td></tr>\n";
        }
        if(!$this->supportsGettingFirstAndLastNames())
        {
            $html .= "<tr><td>First Name: </td><td><input type='text' name='firstname' id='firstname' value='$firstname'/></td></tr>\n";
            $html .= "<tr><td colspan='2'><div class=errorMsg><div class='errorField' id='error_firstname'></div></div></td></tr>\n";
            $html .= "<tr><td>Last Name: </td><td><input type='text' name='lastname' id='lastname' value='$lastname'/></td></tr>\n";
            $html .= "<tr><td colspan='2'><div class=errorMsg><div class='errorField' id='error_lastname'></div></div></td></tr>\n";
        }
        if(!$this->supportsGettingStudentID())
        {
            $html .= "<tr><td>Student ID: </td><td><input type='text' name='studentid' id='studentid' value='$studentid'/></td></tr>\n";
            $html .= "<tr><td colspan='2'><div class=errorMsg><div class='errorField' id='error_studentid'></div></div></td></tr>\n";
        }
        $html .= $this->_getRegistrationFormHTML();
        $html .= $extraRows;
    	$html .= "<tr id='markingLoadRow'><td>Marking Load:</td><td><input name='markingLoad' id='markingLoad' value='$markingLoad'/></td></tr>\n";
    	$html .= "<script>
					$('#userType').change(function(){
						if($('#userType').val() == 'marker')
						{
			        		$('#markingLoadRow').show();
							$('#markingLoad').val('$markingLoad');
						}
						else if($('#userType').val() == 'instructor')
						{
							$('#markingLoadRow').show();
							$('#markingLoad').val('$markingLoad');
						}
						else
						{
			            	$('#markingLoadRow').hide();
							$('#markingLoad').val('0');
						}
			        });
			        $('#userType').change();
				</script>";
        $html .= "</table>\n";

        $html .= "<input type='submit' value='Register'/>\n";
        $html .= "</form>\n";

        //Get the validate function
        $html .= "<script> $(document).ready(function(){ $('#register').submit(function() {\n";
        $html .= "var error = false;\n";

        $html .= "$('#error_username').html('').parent().hide();\n";
        $html .= "var name = $('#username').val();\n";
        $html .= "for(i=0; i <name.length; i++){\n";
        $html .= "var ch = name.charCodeAt(i);\n";
        $html .= "if(2 != !(ch >= \"a\".charCodeAt(0) && ch <= \"z\".charCodeAt(0)) + !(ch >= \"A\".charCodeAt(0) && ch <= \"Z\".charCodeAt(0)) + !(ch >= \"0\".charCodeAt(0) && ch <= \"9\".charCodeAt(0))){\n";
        $html .= "$('#error_username').html('User names can only be alphanumeric characters');\n";
        $html .= "$('#error_username').parent().show();\n";
        $html .= "error=true;\n";
        $html .= "}\n";
        $html .= "}\n";

        //We don't hide the parent of the username, since we did it before
        $html .= $this->getCodeForLengthCheck("username", 2, false);

        //$html .= $this->getCodeForLengthCheck("password", 2);
        if(!$this->supportsGettingFirstAndLastNames())
        {
            $html .= $this->getCodeForLengthCheck("firstname", 2);
            $html .= $this->getCodeForLengthCheck("lastname", 2);
        }
        if(!$this->supportsGettingStudentID() && !$skipIdCheck)
        {
            $html .= $this->getCodeForLengthCheck("studentid", 2);
        }

        //Add whatever else the
        $html .= $this->getRegistrationFormValidationCode();

        $html .= "return !error;\n";
        $html .= "}); }); </script>\n";

        return $html;
    }

    function _getRegistrationFormHTML()
    {
        return "";
    }

    function getRegistrationFormValidationCode()
    {
        return "";
    }

    function supportsSettingPassword()
    {
        return false;
    }

    protected function getCodeForLengthCheck($fieldName, $length, $hideErrorBox=true)
    {
        $code = '';
        if($hideErrorBox)
        {
            $code .= "$('#error_$fieldName').html('').parent().hide();\n";
        }
        $code .= "var l = $('#$fieldName').val().length;\n";
        $code .= "if(l < $length){\n";
        $code .= "$('#error_$fieldName').html('Field is too short');\n";
        $code .= "$('#error_$fieldName').parent().show();\n";
        $code .= "error=true;}\n";
        return $code;
    }
}

class DummyAuthManager extends AuthManager
{
    function checkAuthentication($username, $password) { return false; }
    function supportsAddingUsers() { return false; }
    function supportsGettingFirstAndLastNames() { return false; }
    function supportsGettingStudentID() { return false; }
}

