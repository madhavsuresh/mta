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

        $sh = $this->db->prepare("SELECT userID, alias, SUM(reviewPoints) as points FROM peer_review_assignment_review_marks marks JOIN peer_review_assignment_matches matches ON marks.matchID = matches.matchID JOIN users ON userID = reviewerID WHERE courseId = ? GROUP BY userID ORDER BY points DESC, userID;");
        $sh->execute(array($dataMgr->courseID));
        return $sh->fetchAll();
    }

}


