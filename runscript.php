<?php
require_once("inc/common.php");
try
{
    $title .= " | Run Scripts";
    require_once("inc/script.php");
    $authMgr->enforceInstructor();
    $dataMgr->requireCourse();

    if(array_key_exists('assignmentid', $_GET)){
        $assignmentID = $_GET['assignmentid'];
        $assignmentHeader = $dataMgr->getAssignmentHeader(new AssignmentID($assignmentID));
        $scriptPath = MTA_ROOTPATH."$assignmentHeader->assignmentType/scripts/";
        $targetName = $assignmentHeader->name;
        $scriptClassSuffix = $assignmentHeader->assignmentType."Script";
    } else {
        #Get the scripts that are associated with no assignments
        $scriptPath = MTA_ROOTPATH."scripts/";
        $targetName = "Global";
        $scriptClassSuffix = "Script";
    }

    $action = "";
    if(array_key_exists("action", $_GET))
        $action = $_GET["action"];

    #We need to display the scripts that they can run
    #but first, let's try and get pretty names for them
    $scripts = array();
    foreach(fixedGlob($scriptPath."*.php")as $script)
    {
        //Try to scan the script
        $obj = new stdClass;
        $status = 0;
        exec(str_ireplace("^\\", "/", "php -l \"".escapeshellcmd($script)."\" 2>&1"), $results, $status); //Miguel: changed backslashes to forwardslashes due to WIndows environment
        $scriptFileBaseName = basename($script, ".php");
        $obj->name = $scriptFileBaseName;
        $obj->desc = "";
        $obj->loaded = false;
        if($status)
        {
            foreach($results as $line)
                $obj->desc.="$line\n";
            $obj->desc = cleanString($obj->desc);
        }
        else
        {
            include($script);
            $scriptClassType = $scriptFileBaseName.$scriptClassSuffix;
            if(!class_exists($scriptClassType))
            {
                $obj->desc = "No class of type '$scriptClassType' in '$script'";
            }
            else
            {
                try {
                    $obj->scr = new $scriptClassType();
                    $obj->name = $obj->scr->getName();
                    $obj->desc = $obj->scr->getDescription();
                    $obj->loaded = true;
                }catch(Exception $e){
                    #Failed to get a script object with the given name
                    $obj->desc = "Failed to create object of type '$scriptClassType' in '$script'";
                }
            }
        }
        $scripts[$scriptFileBaseName] = $obj;
    }

    if($action == "exec" || ($action == "prepare" && $scripts[require_from_get("script")]->loaded && !$scripts[require_from_get("script")]->scr->hasParams() ))
    {
        //Go get this script
        $script = $scripts[require_from_get("script")];
        if(!$script->loaded) {
            $content .= "<h1>".$script->name." Error</h1>";
            $content .= $script->desc;
        }else{
            $content .= "<h1>".$script->name." Output</h1>";
            $content .= $script->scr->executeAndGetResult($_POST);
        }
        render_page();
    }
    else if($action == "prepare")
    {
        //Get the script with the given name
        $script = $scripts[require_from_get("script")];
        if(!$script->loaded) {
            $content .= "<h1>".$script->name." Error</h1>";
            $content .= $script->desc;
        }else{
            $content .= "<h1>".$script->name." Options</h1>";

            $tmp = '';
            if(isset($assignmentID))
                $tmp = "assignmentid=$assignmentID";
            $content .= "<form id='scripts' action='".get_redirect_url("?action=exec&script=".require_from_get("script")."&$tmp")."' method='post'>\n";
            $content .= "<input type='hidden' name='script' value='".require_from_get("script")."' />\n";
            $content .= $script->scr->getFormHTML();
            $content .= "<br><br><input type='submit' value='Run Script' />\n";
            $content .= "</form>\n";
            $content .= $script->scr->getFormScripts();
        }

        render_page();
    }
    else
    {
        $content .= "<h1>Available Scripts</h1>\n";

        $content .= "<form id='scripts' method='get'>\n";
        $content .= "<input type='hidden' name='action' value='prepare' />\n";
        if(isset($assignmentID))
            $content .= "<input type='hidden' name='assignmentid' value='$assignmentID'/>\n";
        $content .= "<select name='script' id='scriptSelect'/>";
        foreach($scripts as $scriptName => $script)
        {
            $content .= "<option value='$scriptName'>".$script->name."</option>\n";
        }
        $content .= "</select>\n";
        $content .= "<h2>Description</h2>\n";
        $content .= "<div id='scriptDescContainer'>\n";
        foreach($scripts as $scriptName => $script)
        {
            $content .= "<div id='$scriptName'>\n";
            $content .= $script->desc;
            $content .= "</div>\n";
        }
        $content .= "</div>\n";
		//for operating without htaccess rewrites
		if(!file_exists('.htaccess'))
			$content .= "<input type='hidden' name='courseid' value='".$_GET["courseid"]."'>";

        $content .= "<br><br><input type='submit' value='Set Arguments' />\n";
        $content .= "</form>\n";

        $content .= "<script type='text/javascript'>
        $('#scriptSelect').change(function(){
            $('#' + this.value).show().siblings().hide();
        });
        $('#scriptSelect').change();
        </script>\n";
        render_page();
    }
}catch(Exception $e){
    render_exception_page($e);
}
?>
