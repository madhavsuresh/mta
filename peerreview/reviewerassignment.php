<?php
include("inc/common.php");
try
{
    $title .= " | Assign Reviewers";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    #Get the assignment data
    $assignment = get_assignment();

    $authors = array();
    foreach($assignment->getAuthorSubmissionMap() as $authorID => $submissionID)
    {
        $obj = new stdClass;
        $obj->authorID = new UserID($authorID);
        if($assignment->deniedUser($obj->authorID)){
            continue;
        }
        $obj->submissionID = $submissionID;
        $authors[] = $obj;
    }
    //shuffle($authors);
    $reviewerAssignment = array();
    for($i = 0; $i < sizeof($authors); $i++)
    {
        $submissionID = $authors[$i]->submissionID;
        $reviewerAssignment[$submissionID->id] = array();
        for($j = 0; $j < 3; $j++)
        {
            $reviewerAssignment[$submissionID->id][] = $authors[ ($i+$j+1) % sizeof($authors) ]->authorID;
        }
    }

    $assignment->saveReviewerAssignment($reviewerAssignment);

    foreach($authors as $obj)
    {
        $authorID = $obj->authorID;
        $content .= $dataMgr->getUserDisplayName($authorID) ."  ".sizeof($assignment->getAssignedReviews($authorID))."<br/>";
    }
    render_page();
}catch(Exception $e){
    render_exception_page($e);
}

?>


