<?php

/** Cleans up a string for printing
 */
function cleanString($string)
{
    $string = str_replace( array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
                           array("'", "'", '"', '"', '-', '--', '...'),
                           #array("&lsquo;", "&rsquo;", '&ldquo;', '&rdquo;', '&ndash;', '&mdash;', '&hellip;'),
                           $string);
    // Next, replace their Windows-1252 equivalents.
    $string = str_replace( array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
                           array("'", "'", '"', '"', '-', '--', '...'),
                           #array("&lsquo;", "&rsquo;", '&ldquo;', '&rdquo;', '&ndash;', '&mdash;', '&hellip;'),
                           $string);
    $string = str_replace("\n", "<br>", htmlentities($string, ENT_QUOTES ));
    return $string;
}

/** Makes a float into a pretty string
 */
function precisionFloat($value, $place=2){
    $value = $value * pow(10, $place + 1);
    $value = floor($value);
    $value = (float) $value/10;
    (float) $modSquad = ($value - floor($value));
    $value = floor($value);
    if ($modSquad > .5){
            $value++;
    }
    return $value / (pow(10, $place));
}

function fixedGlob($str)
{
    $ret = glob($str);
    if(!$ret){
        return array();
    }
    return $ret;
}

function file_size($size)
{
    $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
    return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
}

function optional_from_get($varname, $default=NULL)
{
    global $_GET;
    if(!array_key_exists($varname, $_GET))
    {
        return $default;
    }
    return $_GET[$varname];
}

function require_from_get($varname)
{
    global $_GET;
    if(!array_key_exists($varname, $_GET))
    {
        throw new Exception("Missing $varname in GET");
    }
    return $_GET[$varname];
}

function require_from_post($varname)
{
    global $_POST;
    if(!array_key_exists($varname, $_POST))
    {
        throw new Exception("Missing $varname in POST");
    }
    return $_POST[$varname];
}

function require_from_array($varname, &$array)
{
    if(!array_key_exists($varname, $array))
    {
        throw new Exception("Missing $varname in array");
    }
    return $array[$varname];
}

function shuffle_assoc( $array )
{
    $keys = array_keys( $array );
    mt_shuffle( $keys );
    return array_merge( array_flip( $keys ) , $array );
}

function shuffle_assoc2($list) { 
  if (!is_array($list)) return $list; 

  $keys = array_keys($list); 
  shuffle($keys); 
  $random = array(); 
  foreach ($keys as $key) { 
    $random[$key] = $list[$key]; 
  }
  return $random; 
} 

function mt_shuffle(&$items) {
    for ($i = count($items) - 1; $i > 0; $i--){
        $j = mt_rand(0, $i);
        $tmp = $items[$i];
        $items[$i] = $items[$j];
        $items[$j] = $tmp;
    }
}

function get_url_to_main()
{
    return get_redirect_url("index.php");
}

function redirect_to_main()
{
    header("Location: ".get_url_to_main());
    exit();
}

function get_redirect_url($page)
{
    global $dataMgr, $SITEURL, $PRETTYURLS;

    if($dataMgr->courseID)
    {
        $prefix = $SITEURL;
        $suffix="?";
        $questionPos = strpos($page, '?');
        if($questionPos === 0)
        {
            if($PRETTYURLS)
            {
                return $page;
            }
            else
            {
                return $page."&courseid=$dataMgr->courseID";
            }
        }
        else if($questionPos !== FALSE)
            $suffix='&';

        if($PRETTYURLS)
        {
            return $prefix.$dataMgr->courseName."/".$page;
        }
        else
        {
            return $prefix.$page.$suffix."courseid=$dataMgr->courseID";
        }
    }
    else
    {
        return $SITEURL.$page;
    }
}

function redirect_to_page($page)
{
    header("Location: ".get_redirect_url($page));
    exit();
}

function page_not_found()
{
    die("This page is invalid!");
    //header("HTTP/1.0 404 Not Found");
    exit();
}

function get_html_purifier()
{
    global $HTML_PURIFIER;
    if($HTML_PURIFIER === NULL)
    {
        $config = HTMLPurifier_Config::createDefault();
        // configuration goes here:
        $config->set('Core.Encoding', 'UTF-8'); // replace with your encoding
        $config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your doctype

        $HTML_PURIFIER = new HTMLPurifier($config);
    }
    return $HTML_PURIFIER;
}


function median()
{
    $args = func_get_args();

    switch(func_num_args())
    {
    case 0:
        trigger_error('median() requires at least one parameter',E_USER_WARNING);
        return false;
        break;
    case 1:
        $args = array_pop($args);
        // fallthrough
    default:
        if(!is_array($args)) {
            trigger_error('median() requires a list of numbers to operate on or an array of numbers',E_USER_NOTICE);
            return false;
        }
        sort($args);
        $n = count($args);
        $h = intval($n / 2);
        if($n % 2 == 0) {
            $median = ($args[$h] + $args[$h-1]) / 2;
        } else {
            $median = $args[$h];
        }
        break;
    }
    return $median;
}

function isset_bool($x)
{
  if(isset($x))
    return 1;
  else
    return 0;
}

function insertTask($object, &$array)
{
	$length = sizeof($array);
	if($length == 0)
	{
		$array[0] = $object;
		return;
	}
	for($i = 0; $i < $length; $i++)
	{
		if($object->endDate < $array[$i]->endDate)
		{
			for($j = $length; $j > $i; $j--)
			{
				$array[$j] = $array[$j-1];
			}
			$array[$i] = $object;
			return;
		}
	}
	$array[$length] = $object;
}

function phpDate($seconds)
{
	return date('M jS Y, H:i', $seconds);
}

function grace($seconds)
{
	global $GRACETIME;
	return $seconds + $GRACETIME; //15 minute grace period
}
