<?php
include("inc/common.php");
try
{
    include("inc/script.php");
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
    $scripts = fixedGlob($scriptPath."*.php");

    $title .= " | Run Scripts";

    if(array_key_exists('exec', $_GET))
    {
        //Go get this script
        $scriptName = basename(require_from_post("script"));
        require_once($scriptPath.$scriptName.".php");
        $scriptClassType = $scriptName.$scriptClassSuffix;
        $script = new $scriptClassType();
        $content .= "<h1>".$script->getName()." Output</h1>";
        $content .= $script->executeAndGetResult($_POST);
        render_page();
    }
    else if(array_key_exists("prepare", $_GET))
    {
        //Get the script with the given name
        $scriptName = basename(require_from_post("script"));
        require_once($scriptPath.$scriptName.".php");
        $scriptClassType = $scriptName.$scriptClassSuffix;
        $script = new $scriptClassType();
        $content .= "<h1>".$script->getName()." Options</h1>";

        $tmp = '';
        if(isset($assignmentID))
            $tmp = "assignmentid=$assignmentID";
        $content .= "<form id='scripts' action='".get_redirect_url("?exec&$tmp")."' method='post'>\n";
        $content .= "<input type='hidden' name='script' value='$scriptName' />\n";
        $content .= $script->getFormHTML();
        $content .= "<br><br><input type='submit' value='Run Script' />\n";
        $content .= "</form>\n";
        $content .= $script->getFormScripts();

        render_page();
    }
    else
    {
        #We need to display the scripts that they can run
        #but first, let's try and get pretty names for them
        $scriptData = array();

        foreach($scripts as $script)
        {
            //Try to scan the script
            $obj = new stdClass;
            $status = 0;
            exec("php -l \"".escapeshellcmd($script)."\" 2>&1", $results, $status);
            if($status)
            {
                $obj->script = basename($script, ".php");
                $obj->name = $script;
                $obj->desc = "";
                foreach($results as $line)
                    $obj->desc.="$line\n";
                $obj->desc = cleanString($obj->desc);
            }
            else
            {
                require_once("$script");
                $obj->script = basename($script, ".php");
                $scriptClassType = $obj->script.$scriptClassSuffix;
                $scr = new $scriptClassType();
                $obj->name = $scr->getName();
                $obj->desc = $scr->getDescription();
            }
            $scriptData[] = $obj;
        }

        $content .= "<h1>Available Scripts</h1>\n";

        $tmp = '';
        if(isset($assignmentID))
            $tmp = "assignmentid=$assignmentID";
        $content .= "<form id='scripts' action='".get_redirect_url("?prepare&$tmp")."' method='post'>\n";

        $content .= "<select name='script' id='scriptSelect'/>";
        foreach($scriptData as $script)
        {
            $content .= "<option value='$script->script'>".$script->name."</option>\n";
        }
        $content .= "</select>\n";
        $content .= "<h2>Description</h2>\n";
        $content .= "<div id='scriptDescContainer'>\n";
        foreach($scriptData as $script)
        {
            $content .= "<div id='$script->script'>\n";
            $content .= $script->desc;
            $content .= "</div>\n";
        }
        $content .= "</div>\n";

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
