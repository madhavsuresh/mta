<?php
require_once("inc/common.php");
try
{
    $title .= " | Group Viewer";

    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    $assignment = get_grouppicker_assignment();

    $groupMap = $assignment->getGroups();


    $content .= "<h1>Group Assignment For ".$assignment->name."</h1>\n";


    for($i = 0; $i < sizeof($assignment->groups); $i++)
    {
        $group = $assignment->groups[$i];
        $content .= "<h2>".$group."</h2>\n";
        foreach($groupMap as $user => $groupIndex)
        {
            if($groupIndex == $i)
            {
                $content .= $dataMgr->getUserDisplayName(new UserID($user))."</br>\n";
            }
        }
    }

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>
