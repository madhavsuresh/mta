<?php
require_once("inc/authmanager.php");

#The LDAP login class
class LDAPAuthManager extends AuthManager
{
    private $host = 'localhost';
    private $port= 389;
    private $basedn = 'ou=People';
    function __construct($registrationType, $dataMgr)
    {
        global $cfgLDAPAuthMgrHost, $cfgLDAPAuthMgrPort, $cfgLDAPAuthMgrBaseDN;
        parent::__construct($registrationType, $dataMgr);
        //TODO: Grab this from the dataMgr would be better
        $this->host = $cfgLDAPAuthMgrHost;
        $this->port = $cfgLDAPAuthMgrPort;
        $this->basedn= $cfgLDAPAuthMgrBaseDN;
    }
    function checkAuthentication($username, $password) {
        global $auth_ldapconfig;

        #Get the connection, and try to bind
        #Recipe from http://code.activestate.com/recipes/101525-ldap-authentication/
        $ds=@ldap_connect($this->host,$this->port);
        if(!$ds) { return false; }
        $r = @ldap_search( $ds, $this->basedn, 'uid=' . "$username");
        if ($r) {
            $result = @ldap_get_entries( $ds, $r);
            if ($result[0]) {
                if (@ldap_bind( $ds, $result[0]['dn'], $password) ) {
                    if ($result[0])
                        return true;
                    else
                        return false;
                }
            }
        }
        return false;
    }

    function supportsAddingUsers()
    {
        return false;
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
        $ds=@ldap_connect($this->host,$this->port);
        if(!$ds) { return false; }
        $r = @ldap_search( $ds, $this->basedn, 'uid=' . "$username");
        return $r != null;
    }

    function getUserFirstAndLastNames($username)
    {
        $ds=@ldap_connect($this->host,$this->port);
        if(!$ds) { die("what the crap"); }
        $r = @ldap_search( $ds, $this->basedn, 'uid=' . "$username");
        if ($r) {
            $result = @ldap_get_entries( $ds, $r);
            if ($result[0]) {

            }
        }
        die("Failed to get first and last names - contact the TA");
    }
}
