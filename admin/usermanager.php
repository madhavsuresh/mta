<?php
try
{
    if(!array_key_exists("courseid", $_GET)){
        require_once("../inc/common.php");
        //Nope, run up the course picker for people
        $content .= "<h1>Course Select</h1>";
        foreach($dataMgr->getCourses() as $courseObj)
        {
            $content .= "<a href='?courseid=$courseObj->courseID'>$courseObj->displayName</a><br>";
        }
        render_page();
    }
    else{
        $adminFileSkip=true;
        $extraUrl="&courseid=". $_GET["courseid"];
        require_once("../usermanager.php");
    }
}catch(Exception $e){
    render_exception_page($e);
}

