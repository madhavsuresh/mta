<?php
//require_once("inc/common.php");

//First off, we need to figure out the path to the MTA install
$pos = strpos(dirname(__FILE__),DIRECTORY_SEPARATOR.'inc');
$path = substr(__FILE__, 0, $pos);
if( substr($path, strlen($path) - 1) != '/' ) { $path .= '/'; }
define('MTA_ROOTPATH', $path);
//Add the MTA root path into the includes
set_include_path(get_include_path().PATH_SEPARATOR.MTA_ROOTPATH);

if(array_key_exists("save", $_GET)){

	$contents = file_get_contents_utf8("config.php");
	
	$siteurl_start = strripos($contents, '$SITEURL=') + strlen('$SITEURL=');
	$siteurl_end = stripos($contents, ';', $siteurl_start);
	
	$contents_before = substr($contents, 0, $siteurl_start);
	$contents_after = substr($contents, $siteurl_end, strlen($contents) - $siteurl_end);
	$siteurl = $_POST['rooturl'];

	$new_contents = $contents_before.'"'.$siteurl.'" '.$contents_after;

	echo $contents;	
	
	//exec("chmod -R a+r config.php"); 
	file_put_contents("config.php", $new_contents);
}

$content = "<form action='?save=1' method='post'>";
$content .= "<table>";
$content .= "<tr><td>ROOT URL</td><td><input type='text' name='rooturl'></td></tr>";
$content .= "</table>";
$content .= "</form>";

echo $content;

//if()
exec("cp config.php.template config.php");

//Load the config
require_once('config.php');

if($db = sqlite_open("$SQLITEDB.db"))
{
	
}
else
{
	try 
	{ 	
	    //exec('sqlite3 test.db');
		 //*** connect to SQLite database ***/
		//$db = new SQLite3("database.db");
		//$db->exec('read sqlite/sqliteimport.sql');
	    //$db->exec("INSERT INTO foo VALUES ('fnord')");
	    //$dbh = new PDO("database.sqlite");
	    exec("cat sqlite/sqliteimport.sql | sqlite3 sqlite/$SQLITEDB.db");
		exec("cat sqlite/sample.sql | sqlite3 sqlite/$SQLITEDB.db");
	    echo "Handle has been created ...... <br><br>";
	
	}
	catch(PDOException $e)
	{
	    echo $e->getMessage();
	    echo "<br><br>Database -- NOT -- loaded successfully .. ";
	    die( "<br><br>Query Closed !!! $error");
	}
	
	echo "Database loaded successfully ....";
}

function file_get_contents_utf8($fn) {
     $content = file_get_contents($fn);
      return mb_convert_encoding($content, 'UTF-8',
          mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
}

?>

