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
    $draftMap = $assignment->getReviewDraftMap();

    $possibleSubmissions = array();
    $userIsIndependent = array_key_exists($USERID->id, $independents);

    if(sizeof($assignment->getAssignedReviews($USERID)) != 0)
    {
        redirect_to_main();
    }

    foreach($authorMap as $author => $submissionID)
    {
        if($userIsIndependent == array_key_exists($author, $independents))
        {
            $count = 0;
            if(array_key_exists($submissionID->id, $reviewMap))
                $count += sizeof($reviewMap[$submissionID->id]) + array_reduce($reviewMap[$submissionID->id], function($res,$item) {return $res + ($item->exists); } );
            //We also want to count people that have done drafts
            if(array_key_exists($submissionID->id, $draftMap))
                $count += sizeof($draftMap[$submissionID->id]);
            $possibleSubmissions[$submissionID->id] = $count;
        }
    }

    //We want to sort the possible submissions so that the smallest number of review ones are up top
    asort($possibleSubmissions);

    //Take the top three
    if(sizeof($possibleSubmissions) < $assignments->defaultNumberOfReviews)
        throw new Exception("Not enough submissions to assign!");

    $i = 0;
    foreach($possibleSubmissions as $submissionID => $_)
    {
        $assignment->createMatch(new SubmissionID($submissionID), $USERID);
        $i++;
        if($i >= $assignment->defaultNumberOfReviews)
            break;
    }

    redirect_to_main();
}catch(Exception $e){
    render_exception_page($e);
}
?>
