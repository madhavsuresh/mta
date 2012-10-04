<?php
require_once("inc/common.php");
try
{
    $title .= " | Enter Assignment Password";
    $assignmentID = new AssignmentID(require_from_get("assignmentid"));
    $assignment = $dataMgr->getAssignment($assignmentID);

    $errors = "";
    if(array_key_exists("password", $_POST))
    {
        //Check to make sure the passwords match
        if($_POST["password"] == $assignment->password) {
            $dataMgr->userEnteredPassword($assignment->assignmentID, $USERID);
            redirect_to_main();
        }
        $errors .= "Invalid Password<br>";
    }

    $content .= "<h1>Password for ".$assignment->name."</h1>";
    $content .= $assignment->passwordMessage;
    $content .=
    "<div class='box'>
    <form action='?assignmentid=$assignment->assignmentID' method='post'><input type='hidden' name='action' value='login'>
    <table>
        <tr><td>Password: </td><td><input type='password' name='password' /></td></tr>
    </table>
    <table>
        <tr><td><input type='submit' value='Enter' /></td></tr>
    </table>
    </form>
    </div>";
    $content .= $errors;

    render_page();

}catch(Exception $e){
    render_exception_page($e);
}


?>
