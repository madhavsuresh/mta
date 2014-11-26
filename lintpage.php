<?php
//-------------------------------common.php
//Standard includes
require_once('inc/basic.php');
require_once('inc/themefunctions.php');
require_once('inc/ids.php');
require_once('inc/authmanager.php');
require_once('inc/datamanager.php');
require_once('inc/htmlpurifier/HTMLPurifier.auto.php');
require_once('inc/courseconfiguration.php');

//Load the config
require_once('config.php');


function mta_error_handler($errno, $errstr, $errfile, $errline) {
  //if ( E_RECOVERABLE_ERROR===$errno ) {
    render_exception_page(new ErrorException($errstr, $errno, 0, $errfile, $errline));
  //}
  return false;
}
//set_error_handler('mta_error_handler');
#error_reporting(E_ALL);
#ini_set('display_errors','On');

try
{
    //Go get us something that can load some data
    if(!isset($MTA_DATAMANAGER)) { die("The MTA_DATAMANAGER must be set in the config file"); }
    require_once(MTA_ROOTPATH."inc/datamanagers/".$MTA_DATAMANAGER."datamanager.php");
    $dataMgrType = $MTA_DATAMANAGER."DataManager";
    $dataMgr = new $dataMgrType();

    //Now, do a couple of checks to see if we have the course or course ID in get
    if(array_key_exists("courseid", $_GET))
    {
        $id = $_GET["courseid"];
        if(preg_match("/[^\d]/", $id))
        {
            die("Invalid course id '$id'");
        }
        $dataMgr->setCourseFromID(new CourseID($id));
    }
    else if(array_key_exists("course", $_GET))
    {
        $course = $_GET["course"];
        if(preg_match("/[^a-zA-Z0-9]/", $course))
        {
            die("Invalid course name '$course'");
        }
        $dataMgr->setCourseFromName($course);
    }

    //Get the global auth manager
    $authMgr = $dataMgr->createAuthManager();

    #And as a helper, whenever we include this file let's set $USER to be
    #who the session thinks is logged in
    if(array_key_exists("loggedID", $_SESSION) && $dataMgr->isUser(new UserID($_SESSION["loggedID"])))
        $USERID = new UserID($_SESSION["loggedID"]);
    else
        $USERID = NULL;

    //Leave a global for the HTML purifier
    $HTML_PURIFIER = NULL;
    $PRETTYURLS = isset($_GET["prettyurls"]);
    $NOW = time();
    $GRACETIME = 15*60;//15 minutes

    /** Stuff that's needed by the template */
    $content="";
    $page_scripts = array();
    $title = "Mechanical TA";
    $menu=get_default_menu_items();


}catch(Exception $e) {
    render_exception_page($e);
}
//---------------------------------common.php

try
{
	$content = "<h1>Lint page</h1>/n";
	
	$content .= "<table>";
	$content .= "<tr><td>Database connection:</td>";
	try{
		$dataMgrType = $MTA_DATAMANAGER."DataManager";
	    $dataMgr = new $dataMgrType();
		$content .= "<td><span style='color:green'>Good</span></td></tr>";
	} catch(Exception $e){
		$content .= "<td><span style='color:red'>Red</span></td></tr>";
	}
	
	//1. The database is accessible and has a schema.
    
    
    //2. Redirects based on .htaccess are working properly (this might need to go through an iframe)
    //    One of the failure modes that I often encounter is enabling .htaccess in Apache, so some way to detect whether it is enabled and working would be helpful.
    
    //3. Other problems that we encountered (Miguel, please check the wiki and edit this issue to add other checks that seem sensible.)
    
    render_page();
}catch(Exception $e) {
	print_r("HOUR ... NUMBER ... THREE ... SWAG!!!");
	/*
	$e->getMessage( void );
	$e->getPrevious ( void );
	$e->getCode ( void );
	$e->getFile ( void );
	$e->getLine ( void );
	$e->getTrace ( void );
	$e->getTraceAsString ( void );
	$e->__toString ( void );
	$e->__clone ( void );
    render_exception_page($e);
	*/
    render_exception_page($e);
}


?>