<?php
//Standard includes
require_once('inc/basic.php');
require_once('inc/themefunctions.php');

//Load the config
require_once('config.php');

//First off, we need to figure out the path to the MTA install
$pos = strrpos(__FILE__, "/");
$path = substr(__FILE__, 0, $pos);
if( substr($path, strlen($path) - 1) != '/' ) { $path .= '/'; }
#error_reporting(E_ALL);
#ini_set('display_errors','On');

$page_scripts = array();
$title = "Mechanical TA";

$MYSQLcorrectSchema = array ( "0" => "appeal_assignment" , "1" => "assignment_password_entered" , "2" => "assignments" , "3" => "course" , "4" => "course_configuration" , "5" => "group_picker_assignment" , "6" => "group_picker_assignment_groups" , "7" => "group_picker_assignment_selections" , "8" => "job_notifications" , "9" => "peer_review_assignment" , "10" => "peer_review_assignment_appeal_messages" , "11" => "peer_review_assignment_article_response_settings" , "12" => "peer_review_assignment_article_responses" , "13" => "peer_review_assignment_calibration_matches" , "14" => "peer_review_assignment_calibration_pools" , "15" => "peer_review_assignment_code" , "16" => "peer_review_assignment_code_settings" , "17" => "peer_review_assignment_demotion_log" , "18" => "peer_review_assignment_denied" , "19" => "peer_review_assignment_essay_settings" , "20" => "peer_review_assignment_essays" , "21" => "peer_review_assignment_images" , "22" => "peer_review_assignment_independent" , "23" => "peer_review_assignment_instructor_review_touch_times" , "24" => "peer_review_assignment_matches" , "25" => "peer_review_assignment_questions" , "26" => "peer_review_assignment_radio_options" , "27" => "peer_review_assignment_review_answers" , "28" => "peer_review_assignment_review_answers_drafts" , "29" => "peer_review_assignment_review_marks" , "30" => "peer_review_assignment_spot_checks" , "31" => "peer_review_assignment_submission_marks" , "32" => "peer_review_assignment_submissions" , "33" => "peer_review_assignment_text_options" , "34" => "user_passwords" , "35" => "users"); 
$SQLitecorrectSchema = array ( "0" => "appeal_assignment" , "1" => "assignment_password_entered" , "2" => "assignments" , "3" => "course" , "4" => "course_configuration" , "5" => "group_picker_assignment" , "6" => "group_picker_assignment_groups" , "7" => "group_picker_assignment_selections" , "8" => "job_notifications" , "9" => "peer_review_assignment" , "10" => "peer_review_assignment_appeal_messages" , "11" => "peer_review_assignment_article_response_settings" , "12" => "peer_review_assignment_article_responses" , "13" => "peer_review_assignment_calibration_matches" , "14" => "peer_review_assignment_calibration_pools" , "15" => "peer_review_assignment_code" , "16" => "peer_review_assignment_code_settings" , "17" => "peer_review_assignment_demotion_log" , "18" => "peer_review_assignment_denied" , "19" => "peer_review_assignment_essay_settings" , "20" => "peer_review_assignment_essays" , "21" => "peer_review_assignment_images" , "22" => "peer_review_assignment_independent" , "23" => "peer_review_assignment_instructor_review_touch_times" , "24" => "peer_review_assignment_matches" , "25" => "peer_review_assignment_questions" , "26" => "peer_review_assignment_radio_options" , "27" => "peer_review_assignment_review_answers" , "28" => "peer_review_assignment_review_answers_drafts" , "29" => "peer_review_assignment_review_marks" , "30" => "peer_review_assignment_spot_checks" , "31" => "peer_review_assignment_submission_marks" , "32" => "peer_review_assignment_submissions" , "33" => "peer_review_assignment_text_options" , "34" => "user_passwords" , "35" => "users", "36" => "calibrationState", "37" => "status", "38" => "userType", "39" => "appealType");

try
{
	$content = "<h1>Lint page</h1>";
	
	$content .= "<table>";
	$content .= "<tr><td>SQLite database present:</td>";
	if(file_exists("sqlite/$SQLITEDB.db")){	
		$content .= "<td><span style='color:green'>Yes</span></td></tr>";
	}else{
		$content .= "<td><span style='color:red'>No</span></td>";
	}
	$content .= "</tr>";
	
	$content .= "<tr><td>SQLite schema:</td>";
	if(file_exists("sqlite/$SQLITEDB.db")){
		$sqlitedb = new PDO("sqlite:sqlite/$SQLITEDB.db");
		$result = $sqlitedb->query("SELECT name FROM sqlite_master WHERE type='table';");
		$tableList = array();
		while ($table = $result->fetch(SQLITE3_ASSOC)) {
        	$tableList[] = $table['name'];
    	}
    	foreach($SQLitecorrectSchema as $table)
    	{
    		if(!in_array($table, $tableList))
    		{
    			$SQLiteschemastatus .= "<td><span style='color:red'>No</span></td></tr>";
    			break;
    		}
    	}
    	if(!$SQLiteschemastatus)
    		$SQLiteschemastatus = "<td><span style='color:green'>Yes</span></td></tr>";
    	$content .= $SQLiteschemastatus;
	}
	$content .= "</tr>";
	
	$content .= "<tr><td>MYSQL database connection:</td>";
	//1. The database is accessible
	try{
		$db = new PDO($MTA_DATAMANAGER_PDO_CONFIG["dsn"],
		                    $MTA_DATAMANAGER_PDO_CONFIG["username"],
		                    $MTA_DATAMANAGER_PDO_CONFIG["password"],
		                    array(PDO::ATTR_PERSISTENT => true));
		$content .= "<td><span style='color:green'>Yes</span></td></tr>";
	} catch(Exception $e){
		$content .= "<td><span style='color:red'>No</span></td>";
		$error = cleanString($e->getMessage());
		if(strpos($error,"No such file or directory"))
			$content .= "<td>Database Not Found. Ensure the correct DSN, user, and, password are set in config.php</td>";
		elseif(strpos($error,"Connection refused"))
			$content .= "<td>Connection refused</td>";
		elseif(strpos($error,"Access denied for user"))
			$content .= "<td></td>";
	}
	$content .= "</tr>";
	
	$content .= "<tr><td>MYSQL schema:</td>";
	if($db)
	{
		//2. and has a schema
		$result = $db->query("SHOW TABLES");
		$tableList = array();
		while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tableList[] = $row[0];
        }
    	foreach($MYSQLcorrectSchema as $table)
    	{
    		if(!in_array($table, $tableList))
    		{
    			$MYSQLschemastatus .= "<td><span style='color:red'>No</span></td><td>Ensure you run all MYSQL source scripts in the schema directory in order</td>";
    			break;
    		}
    	}
    	if(!$MYSQLschemastatus)
    		$MYSQLschemastatus = "<td><span style='color:green'>Yes</span></td>";
    	$content .= $MYSQLschemastatus;
		$content .= "</tr>";
	}
	
	$content .= "<tr><td>htaccess Working Status:</td>";
	$content .= "<td><div id='htaccessstatus'></div></td>";
	$content .= "</tr>";
    
    /*$content .= "<iframe src='$SITEURL/TEST100/login.php' id='iframe'>
    				<p>iframes are not supported by your browser.</p>
    			</iframe>";*/
    
    $content .= "<script type='text/javascript'>		

	var httpRequest = new XMLHttpRequest();
	httpRequest.onreadystatechange = function() {
	    if (httpRequest.readyState === 4) {
	        if (httpRequest.status === 200) {	    
	            // success
	            $('#htaccessstatus').css('color','green');
				$('#htaccessstatus').html('Yes');
	            //document.getElementById('iframeId').innerHtml = httpRequest.responseText;
	        } else {
	            // failure. Act as you want
	            $('#htaccessstatus').css('color','red');
				$('#htaccessstatus').html('No');
	        }
	    }
	};
	// arbitrary course example to test rewrite function
	httpRequest.open('GET', '$SITEURL/TEST100/login.php');
	httpRequest.send();

	</script>\n";
    			
	$content .= "<tr><td>Sessions configured:</td>";	
	if(file_exists(".user.ini") && file_exists("peerreview/.user.ini") && file_exists("grouppicker/.user.ini"))
		$content .= "<td><span style='color:green'>Yes</span></td>";
	else
		$content .= "<td><span style='color:red'>No</span></td>";
	$content .= "</tr>";

	$content .= "<tr><td>Admin .htaccess file present:</td>";	
	if(file_exists("admin/.htaccess"))
		$content .= "<td><span style='color:green'>Yes</span></td>";
	else
		$content .= "<td><span style='color:red'>No</span></td>";
	$content .= "</tr>";
	
	$content .= "<tr><td>Admin .htpasswd file present:</td>";	
	if(file_exists("admin/.htpasswd"))
		$content .= "<td><span style='color:green'>Yes</span></td>";
	else
		$content .= "<td><span style='color:red'>No</span></td>";
	$content .= "</tr>";
	
	$content .= "</table>";
	
	clearstatcache();
	$content .= "<h3>Important File Permissions</h3>";
	$content .= "<table>";
	$content .= "<tr><td>.user.ini</td><td>".substr(sprintf('%o', fileperms('.user.ini')), -4)."</td></tr>";
	$content .= "<tr><td>/peerreview/.user.ini</td><td>".substr(sprintf('%o', fileperms('peerreview/.user.ini')), -4)."</td></tr>";
	$content .= "<tr><td>/grouppicker/.user.ini</td><td>".substr(sprintf('%o', fileperms('grouppicker/.user.ini')), -4)."</td></tr>";
	$content .= "<tr><td>/admin/.htpasswd</td><td>".substr(sprintf('%o', fileperms('admin/.htpasswd')), -4)."</td></tr>";
	$content .= "<tr><td>/admin/.htaccess</td><td>".substr(sprintf('%o', fileperms('admin/.htaccess')), -4)."</td></tr>";
	$handle = @fopen("./config.php", "r");
	$themeUsed = NULL;
	if ($handle) {
    	while (($buffer = fgets($handle, 4096)) !== false) 
    	{
	        if(preg_match("/$MTA_THEME/", $buffer))
	        {
				$pos = strpos($buffer, "=");
				$themeUsed = str_replace( array(' ','"',';'), '', substr($buffer, $pos+1));
			}
	    }
	    if (!feof($handle)) {
	        echo "Error: unexpected fgets() fail\n";
	    }
	    fclose($handle);
	}
	$content .= "<tr><td>themes/$themeUsed/style.css</td><td>".substr(sprintf('%o', fileperms("themes/".$themeUsed."/style.css")), -4)."</td></tr>";
	$content .= "</table>";
	
    //2. Redirects based on .htaccess are working properly (this might need to go through an iframe)
    //    One of the failure modes that I often encounter is enabling .htaccess in Apache, so some way to detect whether it is enabled and working would be helpful.
    //		a. rewrite module 
    //		b. AllowOverride
    //3. Other problems that we encountered (Miguel, please check the wiki and edit this issue to add other checks that seem sensible.)
    
    //4. SITEURL
}catch(Exception $e) {
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
	*/
    render_exception_page($e);
}

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="description" content="" />
<meta name="keywords" content="" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php get_page_title(); ?> </title>
<?php get_page_headers(); ?>
</head>
<body>
<?php get_page_scripts(); ?>
<div id="wrapper">
    <div id="header">
        <div id="logo">
            <h1>Mechanical TA : Lint Page</h1>
        </div>
        <div id="menu">
            <ul>
            <?php get_page_menu(); ?>
            </ul>
            <br class="clearfix" />
        </div>
        <!--<?//php if($authMgr->isLoggedIn()) { ?> Logged in as <//?php get_user_name(); }?> -->
    </div>
    <div id="page">
        <div id="content">
        <!--<table width='100%'><tr><td align='center'>Contact <a href='mailto:cwthornt@cs.ubc.ca'>Chris</a> if you are having any Mechanical TA issues, not the course instructor</td></tr></table>-->
            <div class="box">
                <?php get_page_content(); ?>
            </div>
            <br class="clearfix" />
        </div>
        <br class="clearfix" />
    </div>
</div>
<div id="footer">
    Copyright (c) 2013 Chris Thornton. Design by <a href="http://www.freecsstemplates.org">FCT</a>.<br>
    <?php get_contact_string(); ?>
</div>
</body>
</html>