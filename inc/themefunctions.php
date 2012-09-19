<?php

/**
 * Get Page Content
 */
function get_page_content() {
	global $content;
	#exec_action('content-top');
	#$content = strip_decode($content);
	#$content = exec_filter('content',$content);
	echo $content;
	#exec_action('content-bottom');
}

/**
 * Get Page Title
 */
function get_page_title() {
	global $title;
	#exec_action('content-top');
	#$content = strip_decode($content);
	#$content = exec_filter('content',$content);
	echo $title;
	#exec_action('content-bottom');
}

/**
 * Get Page Menu
 */
function get_page_menu($echo = true) {
	global $menu;
    if(isset($menu))
    {
        $myVar = "";
        foreach($menu as $menuItem)
        {
            #This better be an array
            if(!is_array($menuItem))
            {
                //Log this error
            }
            else if(!array_key_exists("name", $menuItem))
            {
                //Log it
            }
            else if(!array_key_exists("link", $menuItem))
            {
                //Log it
            }
            else
            {
                $classType = "";
                //Is this the active element?
                if(isset($menuItem["current"])) { $classType = ' class="first current_page_item"'; }
                $myVar .= "<li$classType><a href=\"".$menuItem["link"]."\">".$menuItem["name"]."</a></li>\n";
            }
        }
        if ($echo) {
            echo $myVar;
        } else {
            return $myVar;
        }
    }
}

/**
 * Get Theme URL
 *
 * @param bool $echo Optional, default is true. False will 'return' value
 * @return string Echos or returns based on param $echo
 */
function get_theme_url($echo=true) {
	global $SITEURL;
	global $MTA_THEME;
	$myVar = trim($SITEURL . "themes/" . $MTA_THEME);

	if ($echo) {
		echo $myVar;
	} else {
		return $myVar;
	}
}

/**
 * Get Core UI URL
 *
 * @param bool $echo Optional, default is true. False will 'return' value
 * @return string Echos or returns based on param $echo
 */
function get_ui_url($echo=true) {
	global $SITEURL;
	$myVar = trim($SITEURL . "coreui/");

	if ($echo) {
		echo $myVar;
	} else {
		return $myVar;
	}
}

function get_page_headers($echo=true)
{
    $uiURL = get_ui_url(false);
    $html  = "<link rel='stylesheet' type='text/css' href='".$uiURL."css/redmond/jquery-ui.css' />\n";
    $html .= "<link rel='stylesheet' type='text/css' href='".$uiURL."css/jquery-ui-timepicker-addon.css' />\n";
    $html .= "<link rel='stylesheet' type='text/css' href='".$uiURL."css/oxygen-icons/icons.css' />\n";
    $html .= "<link rel='stylesheet' type='text/css' href='".get_theme_url(false)."/style.css' />\n";

    if($echo) {
        echo $html;
    } else {
        return $html;
    }
}

function get_page_scripts($echo=true)
{
    $uiURL = get_ui_url(false);
    $html  = "<script type='text/javascript' src='".$uiURL."js/jquery.js'></script>\n";
    $html .= "<script type='text/javascript' src='".$uiURL."js/jquery-ui.js'></script>\n";
    $html .= "<script type='text/javascript' src='".$uiURL."js/jquery-ui-timepicker-addon.js'></script>\n";
    $html .= "<script type='text/javascript' src='".$uiURL."js/utils.js'></script>\n";
    $html .= "<script type='text/javascript' src='".$uiURL."js/moment.min.js'></script>\n";
    $html .= "<script type='text/javascript' src='".$uiURL."tiny_mce/tiny_mce.js'></script>\n";

    //$html .= init_tiny_mce(false);

    if($echo) {
        echo $html;
    } else {
        return $html;
    }
}

function set_element_to_date($element, $date, $target="val", $format="MM/DD/YYYY HH:mm",$echo=false, $noScriptTags=false)
{
    $code = '';
    if(!$noScriptTags) {
        $code .= "<script type='text/javascript'>\n";
    }
    $code .= "$('#$element').ready(function(){ var d = moment.unix($date); $('#$element').$target(d.format('$format')); });\n";
    //$code .= "$('#$element').ready(function(){ var d = moment(new Date(".($date*1000).")); $('#$element').val(d.format('$format')); });\n";
    if(!$noScriptTags){
        $code .=  "</script>\n";
    }

    if($echo) {
        echo $code;
    } else {
        return $code;
    }
}

function init_tiny_mce($echo=true)
{
$code = "<script type='text/javascript'>
	tinyMCE.init({
		// General options
        mode : 'specific_textareas',
        editor_selector : 'mceEditor',
		theme : 'advanced',
		plugins : 'autolink,lists,style,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,searchreplace,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,wordcount,advlist,autosave',

		// Theme options
		theme_advanced_buttons1 : 'save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontsizeselect',
		theme_advanced_buttons2 : 'cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,cleanup,help,code,|,forecolor,backcolor',
		theme_advanced_buttons3 : 'tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,|,ltr,rtl,|,fullscreen',
		theme_advanced_buttons4 : 'styleprops,|,cite,abbr,acronym,del,ins,|,visualchars,nonbreaking,template,pagebreak,restoredraft,visualblocks',
		theme_advanced_toolbar_location : 'top',
		theme_advanced_toolbar_align : 'left',
		theme_advanced_statusbar_location : 'bottom',
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		content_css : '".get_ui_url(false)."/editorcontent.css',

		// Drop lists for link/image/media/template dialogs
		template_external_list_url : 'lists/template_list.js',
		external_link_list_url : 'lists/link_list.js',
		external_image_list_url : 'lists/image_list.js',
		media_external_list_url : 'lists/media_list.js',
        height:'500',

		// Style formats
		style_formats : [
			{title : 'Bold text', inline : 'b'},
			{title : 'Red text', inline : 'span', styles : {color : '#ff0000'}},
			{title : 'Red header', block : 'h1', styles : {color : '#ff0000'}},
			{title : 'Example 1', inline : 'span', classes : 'example1'},
			{title : 'Example 2', inline : 'span', classes : 'example2'},
			{title : 'Table styles'},
			{title : 'Table row 1', selector : 'tr', classes : 'tablerow1'}
		],

	});
</script>";

	if ($echo) {
		echo $code;
	} else {
		return $code;
	}
}

/** Gets the current user name
 */
function get_user_name($echo=true) {
    global $dataMgr, $USER;
    $myVar = $dataMgr->getFriendlyName($USER);

	if ($echo) {
		echo $myVar;
	} else {
		return $myVar;
	}
}

function render_page()
{
    global $MTA_THEME, $authMgr;
    header("Content-Type: text/html; charset=utf-8");
    include("themes/".$MTA_THEME."/template.php");
    exit();
}

function render_exception_page($exception)
{
    global $SITEMASTER, $content, $_SESSION, $_GET, $_POST;
    $content  = "<h1>Exception!</h1>";
    //$content .= cleanString($exception);
    $content .= cleanString($exception->getMessage());

    $dump  = $exception."\n\n";
    $dump .= "URL: ".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]."\n\n";
    $dump .= "SESSION\n".print_r($_SESSION, TRUE)."\n\n";
    $dump .= "GET\n".print_r($_GET, TRUE)."\n\n";
    $dump .= "POST\n".print_r($_POST, TRUE)."\n\n";

    //$content .= "<br><a href='mailto:$SITEMASTER?subject=Mechanical TA Debug Dump&body=".rawurlencode($dump)."'>Mail crash dump to webmaster</a>";
    render_page();
}

function get_default_menu_items()
{
    global $authMgr, $dataMgr, $USERID, $_SESSION;
    $menu = array();
    if($authMgr->isLoggedIn())
    {
        $menu[] = array("name" => "Home", "link" => get_url_to_main());
        //Add the option to become a student
        if($dataMgr->isInstructor($USERID))
        {
            $menu[] = array("name" => "Become Student", "link" => get_redirect_url("becomestudent.php?action=select"));
        }
        //Are they already hiding as a student?
        else if(array_key_exists('oldInstructor', $_SESSION))
        {
            $menu[] = array("name" => "Return to Instructor", "link" => get_redirect_url("becomestudent.php?action=return"));
        }
        $menu[] = array("name" => "Logout", "link" => get_redirect_url("logout.php"));
    }
    return $menu;
}

?>
