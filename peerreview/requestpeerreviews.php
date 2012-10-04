<?php
require_once("inc/common.php");
try
{
    $title = " | Request Peer Reviews";
    $dataMgr->requireCourse();
    $authMgr->enforceLoggedIn();

    $assignment = get_peerreview_assignment();

    $authorMap = $assignment->getAuthorSubmissionMap();
    $independents = $assignment->getIndependentUsers();
    $reviewMap = $assignment->getReviewMap();

    $possibleSubmissions = array();
    $userIsIndependent = array_key_exists($USERID, $independents);

    if(sizeof($assignment->getAssignedReviews($USERID)) != 0)
    {
        redirect_to_main();
    }

    foreach($authorMap as $author => $submissionID)
    {
        if($userIsIndependent == array_key_exists($author, $independents))
        {
            if(array_key_exists($submissionID->id, $reviewMap))
                $possibleSubmissions[$submissionID->id] = sizeof($reviewMap[$submissionID->id]) + array_reduce($reviewMap[$submissionID->id], function($res,$item) {return $res + ($item->exists); } );
            else
                $possibleSubmissions[$submissionID->id] = 0;
        }
    }

    //We want to sort the possible submissions so that the smallest number of review ones are up top
    asort($possibleSubmissions);

    //Take the top three
    if(sizeof($possibleSubmissions) < 3)
        throw new Exception("Not enough submissions to assign!");

    $i = 0;
    foreach($possibleSubmissions as $submissionID => $_)
    {
        $assignment->createMatch(new SubmissionID($submissionID), $USERID);
        $i++;
        if($i >= 3)
            break;
    }

    redirect_to_main();
}catch(Exception $e){
    render_exception_page($e);
}
?>
