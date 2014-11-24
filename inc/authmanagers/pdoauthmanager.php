<?php

require_once(dirname(__FILE__)."/../common.php");
require_once("inc/authmanager.php");

class PDOAuthManager extends AuthManager
{
    private $salt = "default_salt_fish";
    private $db;
    private $courseID;

    function __construct($registrationType, $dataMgr)
    {
        parent::__construct($registrationType, $dataMgr);
        if(get_class($dataMgr) != "PDODataManager")
        {
            throw new Exception("PDOAuthManager requires a PDODataManager to work");
        }
        $this->db = $dataMgr->getDatabase();
        $this->courseID = $dataMgr->courseID;
    }
    function supportsAddingUsers() { return true; }
    function supportsGettingFirstAndLastNames() { return false; }
    function supportsGettingStudentID() { return false; }

	/*function formAddQuery($keys, $table, $others)
	{
		switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
			case 'mysql':
				return $this->db->prepare("INSERT INTO $table (".implode(",", array_merge($keys, $others)).") VALUES (".implode(",",array_map(function($item){return ":".$item;}, array_merge($keys, $others) ) ).") ON DUPLICATE KEY UPDATE ".implode(",", array_map(function($item){return $item."=:".$item;}, $others) ).";" );
				break;
			case 'sqlite':
				return $this->db->prepare("INSERT OR IGNORE INTO $table (".implode(",", array_merge($keys, $others)).") VALUES (".implode(",", array_map(function($item){return ":".$item;}, (array_merge($keys, $others)) ) )."); UPDATE $table SET ".implode(",",array_map(function($item){return $item."=:".$item;}, $others))." WHERE ".implode(",", array_map(function($item){return $item."=:".$item;}, $keys) ).";");
				break;
			default:
				throw new Exception("PDO driver used is neither mysql or sqlite");
				break;
		}
	}*/

    function userNameExists($username)
    {
        $sh = $this->db->prepare("SELECT username FROM user_passwords WHERE username=?;");
        $sh->execute(array($username));
        return $sh->fetch() != NULL;
    }

    function checkAuthentication($username, $password)
    {
        $hash = $this->getHash($password);
        $sh = $this->db->prepare("SELECT username FROM user_passwords WHERE username=? AND passwordHash=?;");
        $sh->execute(array($username, $hash));
        return $sh->fetch() != NULL;
    }

    function addUserAuthentication($username, $password)
    {
        //TODO: Make this tied to username/courseID instead of just username
        $hash = $this->getHash($password);
        switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->db->prepare("INSERT INTO user_passwords (username, passwordHash) VALUES (:username, :passwordHash) ON DUPLICATE KEY UPDATE passwordHash = :passwordHash;");
       			break;
			case 'sqlite':
       			$sh = $this->db->prepare("INSERT OR IGNORE INTO user_passwords (username, passwordHash) VALUES (:username, :passwordHash); UPDATE user_passwords SET passwordHash = :passwordHash WHERE username = :username;");
       			break;
		}
		//$sh = $this->formAddQuery(array("username"), "user_passwords", array("passwordHash"));
        return $sh->execute(array("username"=>$username, "passwordHash"=>$hash));
    }

    function removeUserAuthentication($username)
    {
        throw new Exception("Not implemented");
    }

    function getHash($password)
    {
        return "".sha1($this->salt.sha1($this->salt.sha1($password)));
    }

    function supportsSettingPassword()
    {
        return true;
    }
}

