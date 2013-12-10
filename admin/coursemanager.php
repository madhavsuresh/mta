<?php
require_once("../inc/common.php");
try
{
    $courseObj = new stdClass();
    $courseObj->courseID = null;
    $courseObj->name = "";
    $courseObj->displayName = "";
    $courseObj->authType = "";
    $courseObj->registrationType = "";
    $courseObj->browsable = true;

    if(array_key_exists("save", $_GET))
    {
        //Try and store these updated changes into the DB
        if(array_key_exists("courseID", $_POST)){
            $courseObj->courseID = new CourseID($_POST["courseID"]);
        }
        $courseObj->name = $_POST["name"];
        $courseObj->displayName = $_POST["displayName"];
        $courseObj->authType = $_POST["authType"];
        $courseObj->registrationType = $_POST["registrationType"];
        $courseObj->browsable = isset_bool($_POST["browsable"]);

        if(!is_null($courseObj->courseID)){
            //We're updating
            $dataMgr->setCourseInfo($courseObj->courseID, $courseObj->name, $courseObj->displayName, $courseObj->authType, $courseObj->registrationType, $courseObj->browsable);
        }else{
            //We're making a new course
            $dataMgr->createCourse($courseObj->name, $courseObj->displayName, $courseObj->authType, $courseObj->registrationType, $courseObj->browsable);
        }
        //Now that save is done, we can just fall back to the regular editor
    }
    //We're in an editing mode
    if (array_key_exists("edit", $_GET))
    {
        $courseObj = $dataMgr->getCourseInfo(new CourseID($_GET["edit"]));
    }

    //Should we run up the edit dialog?
    if(array_key_exists("new", $_GET) || array_key_exists("edit", $_GET))
    {
        //If we're editing, then these variables have all been filled up
        $content .= "<form id='save' action='?save=1' method='post'>\n";
        if(!is_null($courseObj->courseID)) {
            $content .= "<input type='hidden' name='courseID' value='$courseObj->courseID'>\n";
        }
        $content .= "<table>\n";
        $content .= "<tr><td>Short Name: </td><td><input type='text' name='name' id='name' value='$courseObj->name'/></td></tr>\n";
    $content .= "<tr><td colspan='2'><div class=errorMsg><div class='errorField' id='error_name'></div></div></td></tr>\n";
        $content .= "<tr><td>Display Name: </td><td><input type='text' name='displayName' id='displayName' value='$courseObj->displayName'/></td></tr>\n";

        //TODO: Make this a bit less of a hack
        $content .= "<tr><td>Authentication Type: </td><td><select name='authType' id='authType'>";
        foreach(array("pdo" => "PDO", "ldap" => "LDAP", "multilevel" => "LDAP with PDO Fallback") as $method => $name){
            $content .= "<option";
            if($courseObj->authType == $method)
                $content .= " selected=selected";
            $content .= " value='$method'>$name</option>\n";
        }
        $content .= "</td></tr>\n";
        //TODO: This as well
        $content .= "<tr><td>Registration Type: </td><td><select name='registrationType' id='registrationType'>";
        foreach(array("open" => "Open", "closed" => "Closed") as $method => $name){
            $content .= "<option";
            if($courseObj->registrationType == $method)
                $content .= " selected=selected";
            $content .= " value='$method'>$name</option>\n";
        }
        $extra = "";
        if($courseObj->browsable)
            $extra = "checked";

        $content .= "<tr><td>Browsable: </td><td><input type='checkbox' name='browsable' id='browsable' $extra /></td></tr>\n";
        $content .= "</td></tr>\n";
        $content .= "</table>\n";
        $content .= "<input type='submit' value='Save'/>\n";
        $content .= "</form>\n";
        //Get the validate function
        $content .= "<script> $(document).ready(function(){ $('#save').submit(function() {\n";
        $content .= "var error = false;\n";

        $content .= "$('#error_name').html('').parent().hide();\n";
        $content .= "var name = $('#name').val();\n";
        $content .= "for(i=0; i <name.length; i++){\n";
        $content .= "var ch = name.charCodeAt(i);\n";
        $content .= "if(2 != !(ch >= \"a\".charCodeAt(0) && ch <= \"z\".charCodeAt(0)) + !(ch >= \"A\".charCodeAt(0) && ch <= \"Z\".charCodeAt(0)) + !(ch >= \"0\".charCodeAt(0) && ch <= \"9\".charCodeAt(0))){\n";
        $content .= "$('#error_name').html('Course short names can only be alphanumeric characters');\n";
        $content .= "$('#error_name').parent().show();\n";
        $content .= "error=true;\n";
        $content .= "}\n";
        $content .= "}\n";
        $content .= "if(name.length < 1){\n";
        $content .= "$('#error_name').html('Course short names must be at least one character long');\n";
        $content .= "$('#error_name').parent().show();\n";
        $content .= "error=true;\n";
        $content .= "}";
        foreach($dataMgr->getCourses() as $cObj){
            if($cObj->courseID != $courseObj->courseID){
                $content .= "if(name == '$cObj->name'){\n";
                $content .= "$('#error_name').html('Course short name is already in use');\n";
                $content .= "$('#error_name').parent().show();\n";
                $content .= "error=true;\n";
                $content .= "}\n";
            }
        }
        $content .= "return !error;\n";
        $content .= "}); }); </script>\n";
    }
    else
    {
        //Give the option to add a student
        $content .= "<a href='?new=1'>New Course</a><br><br>\n";
        $content .= "<h2>Courses</h2>\n";
        foreach($dataMgr->getCourses() as $courseObj)
        {
            $content .= "<a href='?edit=$courseObj->courseID'>$courseObj->displayName ($courseObj->name)</a><br>\n";
        }
    }
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

