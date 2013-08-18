<?php
require_once("../inc/common.php");
try
{
    $content .= "<h1>Admin Pages</h1>";
    $content .= "<a href='coursemanager.php'>Course Manager</a><br>";
    $content .= "<a href='usermanager.php'>User Manager</a><br>";
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}


