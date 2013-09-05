<?php

require_once(dirname(__FILE__)."/../../../inc/common.php");
require_once("peerreviewleaderboard/inc/peerreviewleaderboardassignment.php");

class PDOPeerReviewLeaderBoardAssignmentDataManager extends AssignmentDataManager
{
    private $db;

    function __construct($type, PDODataManager $dataMgr)
    {
        parent::__construct($type, $dataMgr);

        $this->db = $dataMgr->getDatabase();
    }

    function loadAssignment(AssignmentID $assignmentID)
    {
        $sh = $this->db->prepare("SELECT name FROM assignments WHERE assignmentID=?;");
        $sh->execute(array($assignmentID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get group picker assignment with id $assignmentID");
        $assignment = new PeerReviewLeaderBoardAssignment($assignmentID, $res->name, $this);

        return $assignment;
    }

    function saveAssignment(Assignment $assignment, $newAssignment)
    {
    }

    function getLeaderResults()
    {
        global $dataMgr;

        $userScores = array();
        $aliases = $dataMgr->getUserAliasMap();

        $sh = $this->db->prepare("SELECT userID, reviewPoints FROM peer_review_assignment_review_marks marks JOIN peer_review_assignment_matches matches ON marks.matchID = matches.matchID JOIN users ON userID = reviewerID WHERE courseId = ? ORDER BY marks.matchID, userID;");
        $sh->execute(array($dataMgr->courseID));

        while($res = $sh->fetch()){
            if(!array_key_exists($res->userID, $userScores)){
                $userScores[$res->userID] = 0;
            }
            $userScores[$res->userID] = max($userScores[$res->userID] + $res->reviewPoints, 0);
        }

        //Sort this in a non insane way http://en.wikipedia.org/wiki/Schwartzian_transform
        $results = array();
        foreach($userScores as $userID => $score){
            $results[] = array($score, $userID);
        }
        rsort($results);
        foreach($results as $index => $row){
            $obj = new stdclass;
            $obj->points = $row[0];
            $obj->alias = $aliases[$row[1]];
            $obj->userID = $row[1];
            $results[$index] = $obj;
        }
        
        return $results;
    }

}


