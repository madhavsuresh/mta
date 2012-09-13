<?php
include("inc/common.php");
$authMgr->enforceInstructor();
$dataMgr->requireCourse();

if(array_key_exists('assignment', $_GET)){
    $assignment = $_GET['assignment'];
    $assignmentType = $dataMgr->getAssignmentType($assignment);
    $scripts = fixedGlob("$assignmentType/scripts/*.py");
} else {
    #Get the scripts that are associated with no assignments
    $scripts = fixedGlob("python/scripts/*.py");
}

$title .= " | Run Scripts";

if(array_key_exists('exec', $_GET))
{
    //The args that we are going to be passing off to the python script
    $args = '';
        $args .= "--assignment $assignment ";
    print_r($_POST);
    print_r($scripts);

    if(!array_key_exists('script', $_POST) || !isset($scripts[$_POST['script']]) || !array_key_exists('args', $_POST)){
        $content .= "There was an error trying to get the script";
        render_page();
    }
    $script = escapeshellarg($scripts[$_POST['script']]);
    $args .= " " . escapeshellcmd($_POST['args']);
    $cmd = "python2 python/scriptrunner.py $script $args ";
    $res = shell_exec("$cmd 2>&1");

    #Does the res string start with a header? if yes, then we should just dump out this file
    if(substr($res, 0, strlen("HEADER ")) === "HEADER ")
    {
        $res = explode("\n", $res);
        foreach($res as $line){
            if(substr($line, 0, strlen("HEADER ")) === "HEADER "){
                header(substr($line, strlen("HEADER ")));
            } else {
                echo "$line\n";
            }
        }
        exit();
    }
    else
    {
        $content .= '<h1>Script Cmd</h1>';
        $content .= cleanString($cmd);
        if(substr($res, 0, strlen("<!--HTML-->")) !== "<!--HTML-->")
        {
            $res = cleanString($res);
        }
        $content .= "<h1>Script Result</h1>";
        $content .= $res;
        render_page();
    }
}
else
{
    #We need to display the scripts that they can run
    #but first, let's try and get pretty names for them
    $scriptNames = array();
    $scriptDescriptions = array();

    foreach($scripts as $script)
    {
        preg_match("/^(?<script>.+).py$/", $script, $matches);
        $helpFile = $matches['script'].'.txt';
        if(file_exists($helpFile)){
            $fp = fopen($helpFile, 'r');
            $scriptNames[$script] = fgets($fp);
            $scriptDescriptions[$script] = str_replace("\n", " ",str_replace("\n\n", "<br>", fread($fp, 8192)));
            fclose($fp);
        }else{
            $scriptNames[$script] = $script;
            $scriptDescriptions[$script] = 'None';
        }
    }

    $content .= "<h1>Availible Scripts</h1>\n";

    $tmp = '';
    if(isset($assignment))
        $tmp = "assignment=$assignment";
    $content .= "<form id='scripts' action='?exec&$tmp' method='post'>\n";
    $content .= "<table width='100%'>\n";

    $content .= "<tr><td>Script</td><td><select name='script' id='script' style='width:100%' onchange='doUpdate(this)'>\n";
    for($i = 0; $i < sizeof($scripts); $i++){
        $name = $scriptNames[$scripts[$i]];
        $content .= "<option value='$i'>$name</option>\n";
    }
    $content .= "</select></td></tr>\n";
    $content .= "<tr><td>Args</td><td><input type='text' name='args' id='args' style='width:100%'/></td></tr>\n";
    $content .= "</table>\n";
    $content .= "<h2>Description</h2>\n";
    #populate the default description
    $desc = '';
    if(isset($scripts[0]))
        $desc = $scriptDescriptions[$scripts[0]];
    $content .= "<div id='descriptionDiv' width='100%'>$desc</div>\n";

    $content .= "<br><input type='submit' value='Run' />\n";
    $content .= "</form>\n";


    $content .= "<script type='text/javascript'>\n";
    $content .= "function doUpdate(x) {\n";
    for($i = 0; $i < sizeof($scripts); $i++){
        $desc = $scriptDescriptions[$scripts[$i]];
        $content .= "if(x.selectedIndex == $i){ $('#descriptionDiv').html('$desc'); return;}\n";
    }
    $content .= "};\n";
    $content .= "</script>";

    render_page();
}
?>
