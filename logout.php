<?php
require_once(dirname(__FILE__)."/inc/common.php");
try
{
    session_unset();
    // now that the user is logged out,
    // go to login page
    redirect_to_page("login.php");
}catch(Exception $e){
    render_exception_page($e);
}
?>
