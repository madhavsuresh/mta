<?php

require_once(dirname(__FILE__)."/../common.php");
require_once("inc/authmanager.php");

class HTAccessAuthManager extends AuthManager
{
    private $salt = "default_salt_fish";
    private $db;
    private $courseID;

    function __construct($registrationType, $dataMgr)
    {
        parent::__construct($registrationType, $dataMgr);
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
	return True;
    }

    function checkAuthentication($username, $password)
    {
	return True;
    }

    function addUserAuthentication($username, $password)
    {
        throw new Exception("Not implemented, leave password field blank for htaccess authentication");
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

