<?php
require_once("inc/common.php");
try
{
    $title .= " | Edit Assignment";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    #Depending on the action, we do different things
    $action = require_from_get("action");


    if($action == 'new')
    {
        //If we don't have a type, then we need to show the picker
        $content .= "<h1>New Assignment</h1>\n";
        foreach($dataMgr->getAssignmentTypeToNameMap() as $type => $name)
        {
            $content .= "<a href=?action=edit&type=$type>$name</a><br/>\n";
        }

        render_page();
    }
    else if($action == "save")
    {
        $assignmentType = require_from_get("type");

        $assignment = $dataMgr->createAssignmentInstance(null, $assignmentType);
        if(array_key_exists("assignmentid", $_GET))
        {
            $assignment->assignmentID = new AssignmentID($_GET["assignmentid"]);
        }

        $assignment->loadFromPost($_POST);
        //Try to save this
        $dataMgr->saveAssignment($assignment, $assignmentType);
        //Do we have extra stuff to do because this was a clone?
        if(array_key_exists("cloneid", $_GET))
        {
            $assignment->finalizeDuplicateFromBase($dataMgr->getAssignment(new AssignmentID($_GET["cloneid"])));
        }
        redirect_to_main();
    }
    else if ($action == "duplicate" || $action == "edit")
    {
        $args = '';
        $assignmentID = NULL;
        if(array_key_exists("assignmentid", $_GET))
        {
            $assignmentID = new AssignmentID($_GET["assignmentid"]);
        }

        if(array_key_exists("type", $_GET))
        {
            //We're editing a new assignment
            $type = $_GET["type"];
            $assignment = $dataMgr->createAssignmentInstance(NULL, $type);
        }
        else if($action == "duplicate")
        {
            //We need to get a duplicate of the specified assignment
            if($assignmentID == NULL)
                throw new Exception("Need an assignment id to clone something");
            $assignment = $dataMgr->getAssignment($assignmentID);
            if($action == "duplicate")
            {
                $assignment = $assignment->duplicate();
            }
            $args="&cloneid=$assignmentID";
            $assignmentID = NULL;
        }
        else
        {
            //They are just editing something
            $assignment = $dataMgr->getAssignment($assignmentID);
        }

        $idGet = '';
        if($assignmentID)
            $idGet = "&assignmentid=$assignmentID";

        $content .= show_timezone();
        $content .= "<h1>Edit Assignment</h1>\n";
        $content .= "<form id='assignment' action='?action=save&type=$assignment->assignmentType$idGet$args' method='post'>\n";
        $content .= $assignment->getFormHTML();
        $content .= "<br><br><input type='submit' value='Save' />\n";
        $content .= "</form>\n";

        //The validate script
        $content .= "<script type='text/javascript'> $(document).ready(function(){ $('#assignment').submit(function() {";
        $content .= "var error = false;";
        $content .= $assignment->getValidationCode();
        $content .= "if(error){return false;}else{return true;}\n";
        $content .= "}); }); </script>\n";

        //Any extra scripts
        $content .= $assignment->getFormScripts();
        render_page();
    }
    else if($action == "delete")
    {
        //Give them a chance to backout
        $assignmentID = new AssignmentID(require_from_get("assignmentid"));
        $assignment = $dataMgr->getAssignment($assignmentID);

        $content .= "<div class='contentTitle'><h1>Delete Assignment ".$assignment->name."<h1></div>\n";
        $content .= "<div style='text-align:center'>\n";
        $content .= "Are you sure you wish to remove this assignment?\n";
        $content .= "<table width='100%'><tr>\n";
        $content .= "<td style='text-align:center'><a href='".get_url_to_main()."'>Cancel</a></td>\n";
        $content .= "<td style='text-align:center'><a href='".get_redirect_url("?assignmentid=$assignmentID&action=deleteconfirmed")."'>Confirm</td></tr></table>\n";
        $content .= "</div>\n";
        render_page();

    }
    else if($action == "deleteconfirmed")
    {
        $assignmentID = new AssignmentID(require_from_get("assignmentid"));

        $dataMgr->deleteAssignment($assignmentID);
        redirect_to_main();
    }
    else
    {
        //We're into the actions that all require an assignment
        $assignmentID = new AssignmentID(require_from_get("assignmentid"));
        if(!$dataMgr->assignmentExists($assignmentID)){
            throw new Exception("No assignment '$assignmentID'");
        }
        //Do a switch on the action
        switch($action){
            case "moveUp":
                $dataMgr->moveAssignmentUp($assignmentID);
                break;
            case "moveDown":
                $dataMgr->moveAssignmentDown($assignmentID);
                break;
            default:
                throw new Exception("Unknown action '$action'");
        }

        #And return to main
        redirect_to_main();
    }

}catch(Exception $e){
    render_exception_page($e);
}

?>

