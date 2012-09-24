<?php
include("inc/common.php");
try
{
    $title .= " | Assign Reviewers";
    $dataMgr->requireCourse();
    $authMgr->enforceInstructor();

    #Get the assignment data
    $assignment = get_peerreview_assignment();
    $reviewMap = $assignment->getReviewMap();
    $scoreMap = $assignment->getMatchScoreMap();
    $submissions =  $assignment->getAuthorSubmissionMap();

    $authors = array();

    foreach($submissions as $authorID => $submissionID)
    {
        $authors[$authorID] =
            array_reduce(array_map( function($x) { global $scoreMap; if(isset($scoreMap[$x->matchID->id])) { return $scoreMap[$x->matchID->id]; } return 0; }, $reviewMap[$submissionID->id]),
            function($v,$w) {return $v+$w; });
    }

    asort($authors, SORT_NUMERIC);

    //$instructors = $dataMgr->getInstructors();
    $instructors = array(new UserID(120), new UserID(120), new UserID(126),new UserID(126), new UserID(123),new UserID(123),new UserID(141), new UserID(141), new UserID(141), new UserID(141));
    $i = 0;

    function createMatch($sub, $user)
    {
        global $content;
        $content .= "$sub, $user<br>";
    }
    foreach($authors as $author => $_)
    {
        //createMatch($submissions[$author], $instructors[$i++ % sizeof($instructors)], true);
        $assignment->createMatch($submissions[$author], $instructors[$i++ % sizeof($instructors)], true);
    }

    $content .= "Done";

    render_page();
}catch(Exception $e){
    render_exception_page($e);
}
