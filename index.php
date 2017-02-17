<?php
require_once("inc/common.php");
require_once("peerreview/inc/calibrationutils.php");
try
{
    //Has the course been set?
    if(!$dataMgr->courseName)
    {
        //Nope, run up the course picker for people
        $content .= "<h1>Course Select</h1>";
		if(file_exists('.htaccess'))
		{
	        foreach($dataMgr->getCourses() as $courseObj)
	        {
	            if($courseObj->browsable)
	                $content .= "<a href='$SITEURL$courseObj->name'/>$courseObj->displayName</a><br>";
	        }
		}
		else
		{
			foreach($dataMgr->getCourses() as $courseObj)
	        {
	            if($courseObj->browsable)
	                $content .= "<a href='{$SITEURL}login.php?courseid=$courseObj->courseID'/>$courseObj->displayName</a><br>";
	        }
		}
        render_page();
    }
    else
    {
        $authMgr->enforceLoggedIn();

        #$dataMgr->numStudents();
        $content .= show_timezone();

        #Figure out what courses are availible, and display them to the user (showing what roles they have)
        $assignments = $dataMgr->getAssignments();

		if($dataMgr->isInstructor($USERID))
        {
			require_once("notifications.php");
		}

		#TO-DO Section and Calibration Section processing
		if($dataMgr->isStudent($USERID))
		{
			require_once("tasks_student.php");
		}

		if($dataMgr->isMarker($USERID))
		{
			require_once("tasks_TA.php");
		}

        if($dataMgr->isInstructor($USERID))
        {
            //Give them the option of creating an assignment, or running global scripts
            $content .= "<table align='left'><tr>\n";
            $content .= "<td><a title='Create new Assignment' href='".get_redirect_url("editassignment.php?action=new")."'><div class='icon new'></div></a</td>\n";
            $content .= "<td><a title='Run Scripts' href='".get_redirect_url("runscript.php")."'><div class='icon script'></div></a></td>\n";
            $content .= "<td><a title='User Manager' href='".get_redirect_url("usermanager.php")."'><div class='icon userManager'></div></a></td>\n";
			$content .= "<td><a title='Course Configuration' href='".get_redirect_url("editcourseconfiguration.php")."'>Course Configuration</a></td>\n";
            $content .= "</tr></table><br>\n";
        }

        $content .= "<h1>Assignments</h1>\n";
        $currentRowIndex = 0;
        foreach($assignments as $assignment)
        {
            #See if we should even display this assignment
            if(!$assignment->showForUser($USERID))
                continue;

            $rowClass = "rowType".($currentRowIndex % 2);
            $currentRowIndex++;

            #Make a div for each assignment to live in
            $content .= "<div class='box $rowClass'>\n";
            $content .= "<h3>".$assignment->name."</h3>";
            if($dataMgr->isInstructor($USERID))
            {
                #We need to give them the common options
                $content .= "<table align='left'><tr>\n";
                $content .= "<td><a title='Move Up' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=moveUp")."'><div class='icon moveUp'></div></a</td>\n";
                $content .= "<td><a title='Move Down' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=moveDown")."'><div class='icon moveDown'></div></a></td>\n";
                $content .= "<td><a title='Delete' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=delete")."'><div class='icon delete'></div></a></td>\n";
                $content .= "<td><a title='Edit Main Settings' href='".get_redirect_url("editassignment.php?action=edit&assignmentid=$assignment->assignmentID")."'><div class='icon edit'></div></a></td>\n";
                $content .= "<td><a title='Run Scripts' href='".get_redirect_url("runscript.php?assignmentid=$assignment->assignmentID")."'><div class='icon script'></div></a></td>\n";
                $content .= "<td><a title='Duplicate Assignment' href='".get_redirect_url("editassignment.php?assignmentid=$assignment->assignmentID&action=duplicate")."'><div class='icon duplicate'></div></a></td>\n";
                $content .= "</table><br/>\n";
            }
            $content .= $assignment->getHeaderHTML($USERID);
            $content .= "</div>";
        }

        render_page();
    }
}catch(Exception $e) {
    render_exception_page($e);
}

?>
