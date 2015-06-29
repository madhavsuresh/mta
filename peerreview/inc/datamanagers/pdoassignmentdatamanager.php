<?php
require_once("inc/assignmentdatamanager.php");
require_once("peerreview/inc/common.php");
require_once("peerreview/inc/peerreviewassignment.php");
require_once("peerreview/inc/mark.php");
require_once("peerreview/inc/reviewmark.php");
require_once("peerreview/inc/essay.php");
require_once("peerreview/inc/image.php");
require_once("peerreview/inc/code.php");
require_once("peerreview/inc/articleresponse.php");
require_once("peerreview/inc/review.php");
require_once("peerreview/inc/spotcheck.php");
require_once("peerreview/inc/appeal.php");

abstract class PDOPeerReviewSubmissionHelper
{
    function __construct($db)
    {
        $this->db = $db;
    }
    abstract function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment);
    abstract function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment);

    abstract function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID);
    abstract function saveAssignmentSubmission(PeerReviewAssignment $assignment, Submission $submission, $isNewSubmission);
    //Because PHP doesn't do multiple inheritance, we have to define this method all over the place
    protected function prepareQuery($name, $query)
    {
        if(!isset($this->$name)) {
            $this->$name = $this->db->prepare($query);
        }
        return $this->$name;
    }
}




class PDOPeerReviewAssignmentDataManager extends AssignmentDataManager
{
    private $db;
    private $submissionHelpers = array();

    function __construct($type, PDODataManager $dataMgr)
    {
        global $PEER_REVIEW_SUBMISSION_TYPES;
        parent::__construct($type, $dataMgr);

        $this->db = $dataMgr->getDatabase();

        $this->submissionExistsByMatchQuery = $this->db->prepare("SELECT submissionID FROM peer_review_assignment_matches WHERE matchID=?;");
        $this->submissionExistsByAuthorQuery = $this->db->prepare("SELECT submissionID FROM peer_review_assignment_submissions WHERE assignmentID=? AND authorID=?;");
        $this->submissionExistsQuery = $this->db->prepare("SELECT submissionID FROM peer_review_assignment_submissions WHERE submissionID=?;");

        $this->reviewQuestionsCache = array();

        foreach($PEER_REVIEW_SUBMISSION_TYPES as $type => $_)
        {
            $helperType = $type."PDOPeerReviewSubmissionHelper";
            $this->submissionHelpers[$type] = new $helperType($this->db);
        }
    }

	function from_unixtime($seconds)
	{
		$driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($driver == 'sqlite')
			return "datetime($seconds,'unixepoch')";
		elseif($driver == 'mysql') 
			return "FROM_UNIXTIME($seconds)";
	}
	
	function unix_timestamp($seconds)
	{
		$driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($driver == 'sqlite')
			return "strftime('%s',$seconds)";
		elseif($driver == 'mysql') 
			return "UNIX_TIMESTAMP($seconds)";
	}
	
	function random()
	{
		$driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($driver == 'sqlite')
			return "RANDOM()";
		elseif($driver == 'mysql') 
			return "RAND()";
	}

    function loadAssignment(AssignmentID $assignmentID)
    {
        global $PEER_REVIEW_SUBMISSION_TYPES;
        #Go and include the assignment page
        /*$%$*/$sh = $this->prepareQuery("loadAssignmentQuery", "SELECT name, submissionQuestion, submissionType, ".$this->unix_timestamp("submissionStartDate")." as submissionStartDate, ".$this->unix_timestamp("submissionStopDate")." as submissionStopDate, ".$this->unix_timestamp("reviewStartDate")." as reviewStartDate, ".$this->unix_timestamp("reviewStopDate")." as reviewStopDate, ".$this->unix_timestamp("markPostDate")." as markPostDate, ".$this->unix_timestamp("appealStopDate")." as appealStopDate, maxSubmissionScore, maxReviewScore, defaultNumberOfReviews, allowRequestOfReviews, showMarksForReviewsReceived, showOtherReviewsByStudents, showOtherReviewsByInstructors, showMarksForOtherReviews, showMarksForReviewedSubmissions, showPoolStatus, calibrationMinCount, calibrationMaxScore, calibrationThresholdMSE, calibrationThresholdScore, extraCalibrations, ".$this->unix_timestamp("calibrationStartDate")." as calibrationStartDate, ".$this->unix_timestamp("calibrationStopDate")." as calibrationStopDate FROM peer_review_assignment JOIN assignments ON assignments.assignmentID = peer_review_assignment.assignmentID WHERE peer_review_assignment.assignmentID=?;");
        $sh->execute(array($assignmentID));
        if(!$res = $sh->fetch())
        {
            throw new Exception("Could not get assignment '$assignmentID'");
        }

        $assignment = new PeerReviewAssignment($assignmentID, $res->name, $this);

        //Start copying things accross
        $assignment->submissionQuestion = $res->submissionQuestion;
        $assignment->submissionType = $res->submissionType;

        $assignment->submissionStartDate = $res->submissionStartDate;
        $assignment->submissionStopDate  = $res->submissionStopDate;

        $assignment->reviewStartDate = $res->reviewStartDate;
        $assignment->reviewStopDate  = $res->reviewStopDate;

        $assignment->markPostDate = $res->markPostDate;
        $assignment->appealStopDate = $res->appealStopDate;

        $assignment->maxSubmissionScore = $res->maxSubmissionScore;
        $assignment->maxReviewScore = $res->maxReviewScore;
        $assignment->defaultNumberOfReviews = $res->defaultNumberOfReviews;
        $assignment->allowRequestOfReviews = $res->allowRequestOfReviews;

        $assignment->showMarksForReviewsReceived = $res->showMarksForReviewsReceived;
        $assignment->showOtherReviewsByStudents        = $res->showOtherReviewsByStudents;
        $assignment->showOtherReviewsByInstructors     = $res->showOtherReviewsByInstructors;
        $assignment->showMarksForOtherReviews    = $res->showMarksForOtherReviews;
        $assignment->showMarksForReviewedSubmissions = $res->showMarksForReviewedSubmissions;
        $assignment->showPoolStatus = $res->showPoolStatus;    
		/*$%$
        $assignment->reviewScoreMaxDeviationForGood = $res->reviewScoreMaxDeviationForGood;
        $assignment->reviewScoreMaxCountsForGood = $res->reviewScoreMaxCountsForGood;
        $assignment->reviewScoreMaxDeviationForPass = $res->reviewScoreMaxDeviationForPass;
        $assignment->reviewScoreMaxCountsForPass = $res->reviewScoreMaxCountsForPass;
		*/
		$assignment->calibrationMinCount = $res->calibrationMinCount;
		$assignment->calibrationMaxScore = $res->calibrationMaxScore;
		$assignment->calibrationThresholdMSE = $res->calibrationThresholdMSE;
		$assignment->calibrationThresholdScore = $res->calibrationThresholdScore;
		$assignment->extraCalibrations = $res->extraCalibrations;
		$assignment->calibrationStartDate = $res->calibrationStartDate;
		$assignment->calibrationStopDate = $res->calibrationStopDate;
		
        //Now we need to get the settings for our type
        if(!array_key_exists($assignment->submissionType, $PEER_REVIEW_SUBMISSION_TYPES))
            throw new Exception("Unknown submission type '$assignment->submissionType'");

        $this->submissionHelpers[$assignment->submissionType]->loadAssignmentSubmissionSettings($assignment);

        $sh = $this->prepareQuery("loadAssignmentCalibPoolsQuery", "SELECT poolAssignmentID FROM peer_review_assignment_calibration_pools WHERE assignmentID = ?");
        $sh->execute(array($assignmentID));
        $assignment->calibrationPoolAssignmentIds = array();
        while($res = $sh->fetch()){
            $assignment->calibrationPoolAssignmentIds[] = $res->poolAssignmentID;
        }

        return $assignment;
    }

    function getAssignmentIDForSubmissionID(SubmissionID $id)
    {
        $sh = $this->prepareQuery("getAssignmentIDForSubmissionIDQuery", "SELECT assignmentID FROM peer_review_assignment_submissions WHERE submissionID = ?;");
        $sh->execute(array($id));
        if(!$res = $sh->fetch())
        {
            throw new Exception("Could not get assignment for submission '$id'");
        }
        return new AssignmentID($res->assignmentID);
    }
    
    function getAssignmentIDForMatchID(MatchID $matchID)
    {
        $sh = $this->db->prepare("SELECT assignmentID from peer_review_assignment_submissions subs JOIN peer_review_assignment_matches matches ON subs.submissionID = matches.submissionID WHERE matchID = ?;");
        $sh->execute(array($matchID));
        if($res = $sh->fetch()){
            return new AssignmentID($res->assignmentID);
        }
        throw new Exception("Failed to find assignment for match $matchID");
    }


    function deleteAssignment(PeerReviewAssignment $assignment)
    {
        //The magic of foreign key constraints....
    }

    function saveAssignment(Assignment $assignment, $newAssignment)
    {
        if($newAssignment)
        {
            /*$%$*/$sh = $this->db->prepare("INSERT INTO peer_review_assignment (submissionQuestion, submissionType, submissionStartDate, submissionStopDate, reviewStartDate, reviewStopDate, markPostDate, appealStopDate, maxSubmissionScore, maxReviewScore, defaultNumberOfReviews, allowRequestOfReviews, showMarksForReviewsReceived, showOtherReviewsByStudents, showOtherReviewsByInstructors, showMarksForOtherReviews, showMarksForReviewedSubmissions, showPoolStatus, calibrationMinCount, calibrationMaxScore, calibrationThresholdMSE, calibrationThresholdScore, extraCalibrations, calibrationStartDate, calibrationStopDate, assignmentID) VALUES (?, ?, ".$this->from_unixtime("?").", ".$this->from_unixtime("?").", ".$this->from_unixtime("?").", ".$this->from_unixtime("?").", ".$this->from_unixtime("?").", ".$this->from_unixtime("?").", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ".$this->from_unixtime("?").", ".$this->from_unixtime("?").", ?);");
        }
        else
        {
            /*$%$*/$sh = $this->db->prepare("UPDATE peer_review_assignment SET submissionQuestion=?, submissionType=?, submissionStartDate=".$this->from_unixtime("?").", submissionStopDate=".$this->from_unixtime("?").", reviewStartDate=".$this->from_unixtime("?").", reviewStopDate=".$this->from_unixtime("?").", markPostDate=".$this->from_unixtime("?").", appealStopDate=".$this->from_unixtime("?").", maxSubmissionScore=?, maxReviewScore=?, defaultNumberOfReviews=?, allowRequestOfReviews=?, showMarksForReviewsReceived=?, showOtherReviewsByStudents=?, showOtherReviewsByInstructors=?, showMarksForOtherReviews=?, showMarksForReviewedSubmissions=?, showPoolStatus=?, calibrationMinCount=?, calibrationMaxScore=?, calibrationThresholdMSE=?, calibrationThresholdScore=?, extraCalibrations=?, calibrationStartDate=".$this->from_unixtime("?").", calibrationStopDate=".$this->from_unixtime("?")." WHERE assignmentID=?;");
        }
        $sh->execute(array(
            $assignment->submissionQuestion,
            $assignment->submissionType,
            $assignment->submissionStartDate,
            $assignment->submissionStopDate,
            $assignment->reviewStartDate,
            $assignment->reviewStopDate,
            $assignment->markPostDate,
            $assignment->appealStopDate,
            $assignment->maxSubmissionScore,
            $assignment->maxReviewScore,
            $assignment->defaultNumberOfReviews,
            $assignment->allowRequestOfReviews,
            $assignment->showMarksForReviewsReceived,
            $assignment->showOtherReviewsByStudents,
            $assignment->showOtherReviewsByInstructors,
            $assignment->showMarksForOtherReviews,
            $assignment->showMarksForReviewedSubmissions,
            $assignment->showPoolStatus,
            /*$%$
            $assignment->reviewScoreMaxDeviationForGood,
            $assignment->reviewScoreMaxCountsForGood,
            $assignment->reviewScoreMaxDeviationForPass,
            $assignment->reviewScoreMaxCountsForPass,
			 */
			$assignment->calibrationMinCount,
			$assignment->calibrationMaxScore,
			$assignment->calibrationThresholdMSE,
			$assignment->calibrationThresholdScore,
			$assignment->extraCalibrations,
			$assignment->calibrationStartDate,
            $assignment->calibrationStopDate,
            $assignment->assignmentID
        ));

        //Nuke the calibration pool ids, and add them back in
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_calibration_pools WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $sh = $this->db->prepare("INSERT INTO peer_review_assignment_calibration_pools (assignmentID, poolAssignmentID) VALUES (?, ?);");
        foreach($assignment->calibrationPoolAssignmentIds as $id)
        {
            $sh->execute(array($assignment->assignmentID, $id));
        }

        //Now we need to save the data for our type
        $this->submissionHelpers[$assignment->submissionType]->saveAssignmentSubmissionSettings($assignment, $newAssignment);
    }

    function saveDeniedUsers(PeerReviewAssignment $assignment, $users)
    {
        $this->db->beginTransaction();
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_denied WHERE assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));

        $sh = $this->db->prepare("INSERT INTO peer_review_assignment_denied (assignmentID, userID) VALUES (?, ?);");
        foreach($users as $userid)
        {
            $sh->execute(array($assignment->assignmentID, $userid));
        }
        $this->db->commit();
    }

    function saveIndependentUsers(PeerReviewAssignment $assignment, $users)
    {
        $this->db->beginTransaction();
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_independent WHERE assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));

        $sh = $this->db->prepare("INSERT INTO peer_review_assignment_independent (assignmentID, userID) VALUES (?, ?);");
        foreach($users as $userid)
        {
            $sh->execute(array($assignment->assignmentID, $userid));
        }
        $this->db->commit();
    }

    function getDeniedUsers(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("getDeniedUsersQuery", "SELECT userID from peer_review_assignment_denied WHERE assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));

        $deniedUsers = array();
        while($res = $sh->fetch())
        {
            $deniedUsers[$res->userID] = new UserID($res->userID);
        }
        return $deniedUsers;
    }

    function getIndependentUsers(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("getIndependentUsersQuery", "SELECT userID from peer_review_assignment_independent WHERE assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));

        $independentUsers = array();
        while($res = $sh->fetch())
        {
            $independentUsers[$res->userID] = new UserID($res->userID);
        }
        return $independentUsers;
    }

    function getReviewQuestions(PeerReviewAssignment $assignment)
    {
        if(isset($this->reviewQuestionsCache[$assignment->assignmentID->id]))
        {
            return $this->reviewQuestionsCache[$assignment->assignmentID->id];
        }
        else
        {
            $sh = $this->db->prepare("SELECT questionID FROM peer_review_assignment_questions WHERE assignmentID=? ORDER BY displayPriority DESC;");
            $sh->execute(array($assignment->assignmentID));

            $questions = array();
            while($res = $sh->fetch())
            {
                $questions[] = $this->getReviewQuestion($assignment, new QuestionID($res->questionID));
            }
            $this->reviewQuestionsCache[$assignment->assignmentID->id] = $questions;
            return $questions;
        }
    }

    function getReviewQuestion(PeerReviewAssignment $assignment, QuestionID $questionID)
    {
        $sh = $this->prepareQuery("getReviewQuestionQuery", "SELECT questionName, questionText, questionType, hidden, displayPriority FROM peer_review_assignment_questions WHERE questionID=?;");
        $sh->execute(array($questionID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get question with id '$questionID'");
        $type = $res->questionType;
        //Try and instantiate the class
        if(!class_exists($type))
            throw new Exception("Unknown Question type '$type'");
        $question = new $type($questionID, $res->questionName, $res->questionText, $res->hidden, $res->displayPriority);

        switch($type)
        {
        case "TextAreaQuestion":
            //Get the minLength
            $sh = $this->prepareQuery("getTextAreaReviewQuestionQuery", "SELECT minLength FROM peer_review_assignment_text_options WHERE questionID=?;");
            $sh->execute(array($question->questionID));
            if($res = $sh->fetch())
                $question->minLength = $res->minLength;
            else
                $question->minLength = 0;
            break;
        case "RadioButtonQuestion":
            $sh = $this->prepareQuery("getRadioButtonReviewQuestionQuery", "SELECT label, score FROM peer_review_assignment_radio_options WHERE questionID=? ORDER BY `index`;");
            $sh->execute(array($question->questionID));
            $question->options = array();
            while($res = $sh->fetch())
            {
                $question->options[] = new RadioButtonOption($res->label, $res->score);
            }
            break;
        default:
            throw new Exception("Unhandled question type '$type'");
        }
        return $question;
    }

    function saveReviewQuestion(PeerReviewAssignment $assignment, ReviewQuestion $question)
    {
        $this->db->beginTransaction();
        //Do we need to insert it first?
        $added = false;
        if(is_null($question->questionID))
        {
            $sh = $this->db->prepare("INSERT INTO peer_review_assignment_questions (assignmentID, questionName, questionText, questionType, hidden, displayPriority) SELECT :assignmentID, :name, :text, :type, :hidden, COUNT(assignmentID) FROM peer_review_assignment_questions WHERE assignmentID=:assignmentID");
            $sh->execute(array("assignmentID"=>$assignment->assignmentID, "name"=>$question->name, "text"=>$question->question, "type"=>get_class($question), "hidden"=>$question->hidden));
            $question->questionID = new QuestionID($this->db->lastInsertID());
            $added = true;
        }
        else
        {
            $sh = $this->db->prepare("UPDATE peer_review_assignment_questions SET questionName=?, questionText=?, hidden=? WHERE questionID=?;");
            $sh->execute(array($question->name, $question->question, $question->hidden, $question->questionID));
        }
        //Now do the rest of the saving for each type
        switch(get_class($question))
        {
        case "TextAreaQuestion":
            //All we need to do is record the min number of words
            if($added) {
                $sh = $this->prepareQuery("insertTextAreaReviewQuestionQuery", "INSERT INTO peer_review_assignment_text_options (minLength, questionID) VALUES (?, ?);");
            } else {
                $sh = $this->prepareQuery("updateTextAreaReviewQuestionQuery", "UPDATE peer_review_assignment_text_options SET minLength =? WHERE questionID=?;");
            }
            $sh->execute(array($question->minLength, $question->questionID));
            break;
        case "RadioButtonQuestion":
            //Delete all the old options for this question
            $sh = $this->prepareQuery("deleteRadioButtonReviewQuestionQuery", "DELETE FROM peer_review_assignment_radio_options WHERE questionID=?;");
            $sh->execute(array($question->questionID));
            //Now insert all the options
            $sh = $this->prepareQuery("insertRadioButtonReviewQuestionQuery", "INSERT INTO peer_review_assignment_radio_options (questionID, `index`, label, score) VALUES (?, ?, ?, ?);");

            $i = 0;
            foreach($question->options as $option)
            {
                $sh->execute(array($question->questionID, $i, $option->label, $option->score));
                $i++;
            }
            break;
        default:
            throw new Exception("Uknown question type '".get_class($question)."'");
        }

        $this->db->commit();
    }

    function moveReviewQuestionUp(PeerReviewAssignment $assignment, QuestionID $id)
    {
        $this->db->beginTransaction();
        $question = $this->getReviewQuestion($assignment, $id);
        $sh = $this->db->prepare("SELECT questionID FROM peer_review_assignment_questions WHERE assignmentID = ? AND displayPriority = ?;");
        $sh->execute(array($assignment->assignmentID, $question->displayPriority+1));
        if(!$res = $sh->fetch())
            return;
        $sh = $this->db->prepare("UPDATE peer_review_assignment_questions SET displayPriority = ? - displayPriority WHERE questionID IN (?, ?);");
        $sh->execute(array(2*$question->displayPriority+1, $id, $res->questionID));
        $this->db->commit();
    }

    function moveReviewQuestionDown(PeerReviewAssignment $assignment, QuestionID $id)
    {
        $this->db->beginTransaction();
        $question= $this->getReviewQuestion($assignment, $id);
        $sh = $this->db->prepare("SELECT questionID FROM peer_review_assignment_questions WHERE assignmentID = ? AND displayPriority = ?;");
        $sh->execute(array($assignment->assignmentID, $question->displayPriority-1));
        if(!$res = $sh->fetch())
            return;
        $sh = $this->db->prepare("UPDATE peer_review_assignment_questions SET displayPriority = ? - displayPriority WHERE questionID IN (?, ?);");
        $sh->execute(array(2*$question->displayPriority-1, $id, $res->questionID));
        $this->db->commit();
    }

    function deleteReviewQuestion(PeerReviewAssignment $assignment, QuestionID $id)
    {
        $sh = $this->db->prepare("DELETE from peer_review_assignment_questions WHERE questionID = ?;");
        $sh->execute(array($id));
    }

    function getAuthorSubmissionMap(PeerReviewAssignment $assignment)
    {
        $sh = $this->db->prepare("SELECT authorID, submissionID FROM peer_review_assignment_submissions LEFT OUTER JOIN peer_review_assignment_denied ON peer_review_assignment_submissions.authorID = peer_review_assignment_denied.userID AND peer_review_assignment_submissions.assignmentID = peer_review_assignment_denied.assignmentID WHERE peer_review_assignment_denied.userID IS NULL AND peer_review_assignment_submissions.assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->authorID] = new SubmissionID($res->submissionID);
        }
        return $map;
    }
	
	//Modified original function to exclude submissions that are for calibration and have calibration reviews
    function getAuthorSubmissionMap_(PeerReviewAssignment $assignment)
    {
    	//$sh = $this->db->prepare("SELECT authorID, submissionID FROM peer_review_assignment_submissions LEFT OUTER JOIN peer_review_assignment_denied ON peer_review_assignment_submissions.authorID = peer_review_assignment_denied.userID AND peer_review_assignment_submissions.assignmentID = peer_review_assignment_denied.assignmentID JOIN users ON users.userID = peer_review_assignment_submissions.authorID WHERE peer_review_assignment_denied.userID IS NULL AND submissionID NOT IN (SELECT submissionID FROM peer_review_assignment_matches WHERE calibrationState = 'key' OR calibrationState = 'attempt') AND users.userType = 'student' AND peer_review_assignment_submissions.assignmentID = ?;");
        $sh = $this->db->prepare("SELECT authorID, submissionID FROM peer_review_assignment_submissions LEFT OUTER JOIN peer_review_assignment_denied ON peer_review_assignment_submissions.authorID = peer_review_assignment_denied.userID AND peer_review_assignment_submissions.assignmentID = peer_review_assignment_denied.assignmentID WHERE peer_review_assignment_denied.userID IS NULL AND submissionID NOT IN (SELECT submissionID FROM peer_review_assignment_matches WHERE calibrationState = 'key' OR calibrationState = 'attempt') AND peer_review_assignment_submissions.assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->authorID] = new SubmissionID($res->submissionID);
        }
        return $map;
    }
	
    function getActiveAuthorSubmissionMap_(PeerReviewAssignment $assignment)
    {
    	//$sh = $this->db->prepare("SELECT authorID, submissionID FROM peer_review_assignment_submissions LEFT OUTER JOIN peer_review_assignment_denied ON peer_review_assignment_submissions.authorID = peer_review_assignment_denied.userID AND peer_review_assignment_submissions.assignmentID = peer_review_assignment_denied.assignmentID JOIN users ON users.userID = peer_review_assignment_submissions.authorID WHERE peer_review_assignment_denied.userID IS NULL AND submissionID NOT IN (SELECT submissionID FROM peer_review_assignment_matches WHERE calibrationState = 'key' OR calibrationState = 'attempt') AND users.userType = 'student' AND peer_review_assignment_submissions.assignmentID = ?;");
        $sh = $this->db->prepare("SELECT authorID, submissionID FROM peer_review_assignment_submissions LEFT OUTER JOIN peer_review_assignment_denied ON peer_review_assignment_submissions.authorID = peer_review_assignment_denied.userID AND peer_review_assignment_submissions.assignmentID = peer_review_assignment_denied.assignmentID JOIN users ON peer_review_assignment_submissions.authorID = users.userID WHERE peer_review_assignment_denied.userID IS NULL AND submissionID NOT IN (SELECT submissionID FROM peer_review_assignment_matches WHERE calibrationState = 'key' OR calibrationState = 'attempt') AND users.dropped = 0 AND peer_review_assignment_submissions.assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->authorID] = new SubmissionID($res->submissionID);
        }
        return $map;
    }
	
    function getReviewerAssignment(PeerReviewAssignment $assignment)
    {
    	//$sh = $this->db->prepare("SELECT peer_review_assignment_matches.submissionID, reviewerID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE peer_review_assignment_submissions.assignmentID = ? AND instructorForced = 0 ORDER BY matchID;");
    	$sh = $this->db->prepare("SELECT peer_review_assignment_matches.submissionID, reviewerID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE peer_review_assignment_submissions.assignmentID = ? AND instructorForced = 0 AND calibrationState <> 'attempt' ORDER BY matchID;");
        $sh->execute(array($assignment->assignmentID));
        $reviewerAssignment = array();
        while($res = $sh->fetch())
        {
            if(!array_key_exists($res->submissionID, $reviewerAssignment))
            {
                $reviewerAssignment[$res->submissionID] = array();
            }
            $reviewerAssignment[$res->submissionID][] = new UserID($res->reviewerID);
        }
        return $reviewerAssignment;
    }

    function saveReviewerAssignment(PeerReviewAssignment $assignment, $reviewerAssignment)
    {
        $this->db->beginTransaction();
        //We need to create matches for everything in the list here
        $checkForMatch = $this->db->prepare("SELECT matchID FROM peer_review_assignment_matches WHERE submissionID=? AND reviewerID = ?;");
        $insertMatch = $this->db->prepare("INSERT INTO peer_review_assignment_matches (submissionID, reviewerID, instructorForced) VALUES (?, ?, 0);");
       	$insertCovertMatch = $this->db->prepare("INSERT INTO peer_review_assignment_matches (submissionID, reviewerID, instructorForced, calibrationState) VALUES (?, ?, 0, 'covert');");

        //Make a dictionary for the clean query
  		$cleanQueries = array();

        $authors_ = $this->getAuthorSubmissionMap_($assignment);
		$authors = $this->getAuthorSubmissionMap($assignment);
		$authors = shuffle_assoc2($authors);
        foreach($authors as $authorID => $submissionID)
        {
            //See if this match exists
            if(array_key_exists($submissionID->id, $reviewerAssignment))
            {
            	$reviewers = $reviewerAssignment[$submissionID->id];
            	if(array_key_exists($authorID, $authors_))
				{
	                foreach($reviewers as $reviewerID)
	                {
	                    $checkForMatch->execute(array($submissionID, $reviewerID));
	                    if(!$res = $checkForMatch->fetch())
	                    {
	                        //We need to insert this match
	                        $insertMatch->execute(array($submissionID, $reviewerID));
	                    }
	                }
                }
				else
				{
					foreach($reviewers as $reviewerID)
	                {
	                	$checkForMatch->execute(array($submissionID, $reviewerID));
	                    if(!$res = $checkForMatch->fetch())
	                    {	
							$insertCovertMatch->execute(array($submissionID, $reviewerID));
						}
					}
				}
            }
            else
            {
                $reviewers = array();
            }
            //Clean up any extra reviews that are not insructor forced
            $sh = $this->prepareCleanQuery($cleanQueries, $this->db, sizeof($reviewers));
            //We need to put the submission ID in this array
            array_unshift($reviewers, $submissionID);
            $sh->execute($reviewers);
        }

        $this->db->commit();
    }

	//Strictly a helper function for main function saveReviewerAssignment
    function prepareCleanQuery(&$map, $db, $numUsers)
    {
        if(!array_key_exists($numUsers, $map))
        {
            if($numUsers == 0)
            {
                $map[$numUsers] = $db->prepare("DELETE FROM peer_review_assignment_matches WHERE submissionID = ? AND instructorForced=0 AND (calibrationState = 'none' OR calibrationState = 'covert');");
            }
            else
            {
                $paramStr = "";
                for($i=0; $i < $numUsers-1;$i++) { $paramStr.="?,";}
                $paramStr.="?";
                $map[$numUsers] = $db->prepare("DELETE FROM peer_review_assignment_matches WHERE submissionID = ? AND reviewerID NOT IN ($paramStr) AND instructorForced=0 AND (calibrationState = 'none' OR calibrationState = 'covert');");
            }
        }
        return $map[$numUsers];
    }

    function getAssignedReviews(PeerReviewAssignment $assignment, UserID $reviewerID)
    {
        //$sh = $this->db->prepare("SELECT matches.matchID, matches.submissionID FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON subs.submissionID = matches.submissionID LEFT OUTER JOIN peer_review_assignment_calibration_matches calib ON matches.matchID = calib.matchID  WHERE subs.assignmentID = ? AND reviewerID = ? AND instructorForced = 0 AND calib.matchID IS NULL ORDER BY matches.matchID;");
        //$sh->execute(array($assignment->assignmentID, $reviewerID));
        $sh = $this->db->prepare("SELECT matches.matchID, matches.submissionID FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON subs.submissionID = matches.submissionID WHERE subs.assignmentID = ? AND reviewerID = ? AND instructorForced = 0 AND (matches.calibrationState = 'none' OR matches.calibrationState = 'covert') ORDER BY matches.matchID;");
        $sh->execute(array($assignment->assignmentID, $reviewerID));
        $assigned = array();
        while($res = $sh->fetch())
        {
            $assigned[] = new MatchID($res->matchID);
        }
        return $assigned;
    }
    
    function getMarkerToSubmissionsMap(PeerReviewAssignment $assignment)
    {
        $sh = $this->db->prepare("SELECT matches.reviewerID, matches.submissionID FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON subs.submissionID = matches.submissionID WHERE subs.assignmentID = ? AND instructorForced = 1 AND matches.calibrationState = 'none';");
        $sh->execute(array($assignment->assignmentID));
        $map = array();
        while($res = $sh->fetch())
        {
        	if(!array_key_exists($res->reviewerID, $map))
        		$map[$res->reviewerID] = array();
            $map[$res->reviewerID][$res->submissionID] = new SubmissionID($res->submissionID);
        }
        return $map;
    }
    
    function getAssignedCalibrationReviews(PeerReviewAssignment $assignment, UserID $reviewerID)
    {
        //$sh = $this->db->prepare("SELECT matches.matchID, matches.submissionID FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON subs.submissionID = matches.submissionID LEFT OUTER JOIN peer_review_assignment_calibration_matches calib ON matches.matchID = calib.matchID  WHERE calib.assignmentID = ? AND reviewerID = ? AND instructorForced = 0 AND calib.matchID IS NOT NULL ORDER BY matches.matchID;");
        //$sh->execute(array($assignment->assignmentID, $reviewerID));
        $sh = $this->db->prepare("SELECT matches.matchID, matches.submissionID FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON subs.submissionID = matches.submissionID WHERE subs.assignmentID = ? AND reviewerID = ? AND instructorForced = 0 AND matches.calibrationState = 'attempt' ORDER BY matches.matchID;");
        $sh->execute(array($assignment->assignmentID, $reviewerID));
        $assigned = array();
        while($res = $sh->fetch())
        {
            $assigned[] = new MatchID($res->matchID);
        }
        return $assigned;
    }
	
    function getNewCalibrationSubmissionForUser(PeerReviewAssignment $assignment, UserID $userid)
    {
        //$sh = $this->prepareQuery("getNewCalibSubmissionForUserQuery", "SELECT submissionID FROM `peer_review_assignment_submissions` subs LEFT OUTER JOIN peer_review_assignment_calibration_pools pools ON subs.assignmentID = pools.poolAssignmentID WHERE pools.assignmentID = ? AND submissionID NOT IN ( SELECT submissionID from peer_review_assignment_matches WHERE peer_review_assignment_matches.reviewerID = ?) ORDER BY ".$this->random()." LIMIT 1;");
		$sh = $this->prepareQuery("getNewCalibSubmissionForUserQuery", "SELECT subs.submissionID FROM `peer_review_assignment_submissions` subs JOIN peer_review_assignment_matches matches ON subs.submissionID = matches.submissionID WHERE subs.assignmentID = ? AND matches.calibrationState = 'key' AND subs.submissionID NOT IN ( SELECT submissionID from peer_review_assignment_matches WHERE peer_review_assignment_matches.reviewerID = ?) ORDER BY ".$this->random()." LIMIT 1;");
        
        $sh->execute(array($assignment->assignmentID, $userid));

        if($res = $sh->fetch()) {
            return new SubmissionID($res->submissionID);
        }
        return NULL;
    }
    
    function getNewCalibrationSubmissionForUserRestricted(PeerReviewAssignment $assignment, UserID $userid, $topicIndex)
    {
    	$sh = $this->prepareQuery("getNewCalibSubmissionForUserRestrictedQuery", "SELECT subs.submissionID FROM `peer_review_assignment_submissions` subs, peer_review_assignment_matches matches, peer_review_assignment_essays essays WHERE subs.submissionID = essays.submissionID AND subs.submissionID = matches.submissionID AND subs.assignmentID = ? AND matches.calibrationState = 'key' AND subs.submissionID NOT IN ( SELECT submissionID from peer_review_assignment_matches WHERE peer_review_assignment_matches.reviewerID = ?) AND essays.topicIndex <> ? ORDER BY ".$this->random()." LIMIT 1;");
    	
    	$sh->execute(array($assignment->assignmentID, $userid, $topicIndex));

        if($res = $sh->fetch()) {
            return new SubmissionID($res->submissionID);
        }
        return NULL;
    }

    function deniedUser(PeerReviewAssignment $assignment, $userID)
    {
        $sh = $this->prepareQuery("deniedUserQuery", "SELECT userID FROM peer_review_assignment_denied WHERE assignmentID=? AND userID=?;");
        $sh->execute(array($assignment->assignmentID, $userID));
        return $sh->fetch() != null;
    }

    function independentUser(PeerReviewAssignment $assignment, $userID)
    {
        $sh = $this->prepareQuery("independentUserQuery", "SELECT userID FROM peer_review_assignment_independent WHERE assignmentID=? AND userID=?;");
        $sh->execute(array($assignment->assignmentID, $userID));
        return $sh->fetch() != null;
    }

    function submissionExists(PeerReviewAssignment $assignment, MechanicalTA_ID $id)
    {
        switch(get_class($id))
        {
        case "SubmissionID":
            $this->submissionExistsQuery->execute(array($id));
            return  $this->submissionExistsQuery->fetch() != NULL;
        case "UserID":
            $this->submissionExistsByAuthorQuery->execute(array($assignment->assignmentID, $id));
            return $this->submissionExistsByAuthorQuery->fetch() != NULL;
        case "MatchID":
            $this->submissionExistsByMatchQuery->execute(array($id));
            return $this->submissionExistsByMatchQuery->fetch() != NULL;
        default:
            throw new Exception("Can't lookup an submission from a '".get_class($id)."'");
        }
    }

    function reviewExists(PeerReviewAssignment $assignment, MatchID $id)
    {
        $sh = $this->prepareQuery("reviewExistsQuery", "SELECT count(*) as c FROM peer_review_assignment_review_answers WHERE matchID = ?;");
        $sh->execute(array($id));

        return $sh->fetch()->c > 0;
    }

    function reviewDraftExists(PeerReviewAssignment $assignment, MatchID $id)
    {
        $sh = $this->prepareQuery("reviewExistsDraftQuery", "SELECT count(*) as c FROM peer_review_assignment_review_answers_drafts WHERE matchID = ?;");
        $sh->execute(array($id));

        return $sh->fetch()->c > 0;
    }

    function getSubmissionID(PeerReviewAssignment $assignment, MechanicalTA_ID $id)
    {
        switch(get_class($id))
        {
        case "UserID":
            $this->submissionExistsByAuthorQuery->execute(array($assignment->assignmentID, $id));
            $res = $this->submissionExistsByAuthorQuery->fetch();
            break;
        case "MatchID":
            $this->submissionExistsByMatchQuery->execute(array($id));
            $res = $this->submissionExistsByMatchQuery->fetch();
            break;
        default:
            throw new Exception("Can't lookup an submission from a '".get_class($id)."'");
        }
        if($res == NULL)
            throw new Exception("Could not find an submission by author '$id'");
        return new SubmissionID($res->submissionID);
    }

    function getSubmissionMark(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->prepareQuery("getSubmissionMarkQuery", "SELECT score, comments, automatic, ".$this->unix_timestamp("submissionMarkTimestamp")." as submissionMarkTimestamp FROM peer_review_assignment_submission_marks WHERE submissionID=?;");
        $sh->execute(array($submissionID));
        if($res = $sh->fetch())
        {
        	$mark = new Mark($res->score, $res->comments, $res->automatic);
			$mark->markTimestamp = $res->submissionMarkTimestamp;
            return $mark;
        }
        return new Mark();
    }

    function getReviewMark(PeerReviewAssignment $assignment, MatchID $matchID)
    {
        $sh = $this->prepareQuery("getReviewMarkQuery", "SELECT score, comments, automatic, reviewPoints, ".$this->unix_timestamp("reviewMarkTimestamp")." as reviewMarkTimestamp FROM peer_review_assignment_review_marks WHERE matchID=?;");
        $sh->execute(array($matchID));
        if($res = $sh->fetch())
        {
        	$reviewMark = new ReviewMark($res->score, $res->comments, $res->automatic, $res->reviewPoints);
        	$reviewMark->markTimestamp = $res->reviewMarkTimestamp;
            return $reviewMark;
        }
        return new ReviewMark();
    }

    function removeReviewMark(PeerReviewAssignment $assignment, MatchID $matchID)
    {
        $sh = $this->prepareQuery("removeReviewMarkQuery", "DELETE FROM peer_review_assignment_review_marks WHERE matchID=?;");
        $sh->execute(array($matchID));
    }

    function saveSubmissionMark(PeerReviewAssignment $assignment, Mark $mark, SubmissionID $submissionID)
    {
    	global $NOW;
		
		$array = array("submissionID" => $submissionID, "score"=>$mark->score, "comments"=>$mark->comments, "automatic"=>(int)$mark->isAutomatic, "submissionMarkTimestamp"=>$NOW);
    	switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->prepareQuery("saveSubmissionMarkQuery", "INSERT INTO peer_review_assignment_submission_marks (submissionID, score, comments, automatic, submissionMarkTimestamp) VALUES (:submissionID, :score, :comments, :automatic, :submissionMarkTimestamp) ON DUPLICATE KEY UPDATE score=:score, comments=:comments, automatic=:automatic, submissionMarkTimestamp=".$this->from_unixtime(":submissionMarkTimestamp").";");
				$sh->execute($array);
       			break;
			case 'sqlite':
				$sh = $this->prepareQuery("saveSubmissionMarkQuery", "INSERT OR IGNORE INTO peer_review_assignment_submission_marks (submissionID, score, comments, automatic, submissionMarkTimestamp) VALUES (:submissionID, :score, :comments, :automatic, :submissionMarkTimestamp);");
				$sh->execute($array);
				$sh = $this->prepareQuery("saveSubmissionMarkQuery2", "UPDATE peer_review_assignment_submission_marks SET score=:score, comments=:comments, automatic=:automatic, submissionMarkTimestamp=".$this->from_unixtime(":submissionMarkTimestamp")." WHERE submissionID=:submissionID;");
				$sh->execute($array);
       			break;
		}
    }

    function saveReviewMark(PeerReviewAssignment $assignment, ReviewMark $mark, MatchID $matchID)
    {
    	global $NOW;
    	
    	$array = array("matchID" => $matchID, "score"=>$mark->score, "comments"=>$mark->comments, "automatic"=>(int)$mark->isAutomatic, "reviewPoints"=>$mark->reviewPoints, "reviewMarkTimestamp"=>$NOW);
    	switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->prepareQuery("saveReviewMarkQuery", "INSERT INTO peer_review_assignment_review_marks (matchID, score, comments, automatic, reviewPoints, reviewMarkTimestamp) VALUES (:matchID, :score, :comments, :automatic, :reviewPoints, FROM_UNIXTIME(:reviewMarkTimestamp)) ON DUPLICATE KEY UPDATE score=:score, comments=:comments, automatic=:automatic, reviewPoints=:reviewPoints, reviewMarkTimestamp=FROM_UNIXTIME(:reviewMarkTimestamp);");
       			$sh->execute($array);
       			break;
			case 'sqlite':
				$sh = $this->prepareQuery("saveReviewMarkQuery", "INSERT OR IGNORE INTO peer_review_assignment_review_marks (matchID, score, comments, automatic, reviewPoints, reviewMarkTimestamp) VALUES (:matchID, :score, :comments, :automatic, :reviewPoints, datetime(:reviewMarkTimestamp,'unixepoch'));");
				$sh->execute($array);
				$sh = $this->prepareQuery("saveReviewMarkQuery2", "UPDATE peer_review_assignment_review_marks SET score=:score, comments=:comments, automatic=:automatic, reviewPoints=:reviewPoints, reviewMarkTimestamp=datetime(:reviewMarkTimestamp,'unixepoch') WHERE matchID=:matchID;");
				$sh->execute($array);
       			break;
		}
    }

    function deleteSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_submissions WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_submission_marks WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
    }

    function getSubmission(PeerReviewAssignment $assignment, $id)
    {
        switch(get_class($id))
        {
        case "SubmissionID":
            //They want the submission with this id
            $sh = $this->prepareQuery("getSubmissionQuery","SELECT submissionID, authorID, noPublicUse, ".$this->unix_timestamp("submissionTimestamp")." as submissionTimestamp FROM peer_review_assignment_submissions WHERE submissionID=?;");
            $sh->execute(array($id));
            $res = $sh->fetch();
            break;
        case "UserID":
            //They want to get the submission by the author
            $sh = $this->prepareQuery("getSubmissionByAuthorQuery", "SELECT submissionID, authorID, noPublicUse, ".$this->unix_timestamp("submissionTimestamp")." as submissionTimestamp FROM peer_review_assignment_submissions WHERE assignmentID=? AND authorID=?;");
            $sh->execute(array($assignment->assignmentID, $id));
            $res = $sh->fetch();
            break;
        case "MatchID":
            //They want to get the submission for the given review
            $sh = $this->prepareQuery("getSubmissionByMatchQuery", "SELECT peer_review_assignment_submissions.submissionID, authorID, noPublicUse, ".$this->unix_timestamp("submissionTimestamp")." as submissionTimestamp FROM peer_review_assignment_submissions JOIN peer_review_assignment_matches ON peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID WHERE matchID=?;");
            $sh->execute(array($id));
            $res = $sh->fetch();
            break;
        default:
            throw new Exception("Can't lookup an submission from a '".get_class($id)."'");
        }
        if(!$res)
            throw new Exception("Could not get submission id by ".get_class($id)." '$id'");

        $submission = $this->submissionHelpers[$assignment->submissionType]->getAssignmentSubmission($assignment, new SubmissionID($res->submissionID));
        $submission->authorID = new UserID($res->authorID);
        $submission->noPublicUse = $res->noPublicUse;
		$submission->submissionTimestamp = $res->submissionTimestamp;
        return $submission;
    }

    function saveSubmission(PeerReviewAssignment $assignment, Submission $submission)
    {
    	global $NOW;
		
        $isNewSubmission = !isset($submission->submissionID) OR is_null($submission->submissionID);
        if($isNewSubmission)
        {
            $sh = $this->db->prepare("INSERT INTO peer_review_assignment_submissions (assignmentID, authorID, noPublicUse, submissionTimestamp) VALUES(?, ?, ?, ".$this->from_unixtime("?").");");
            $sh->execute(array($assignment->assignmentID, $submission->authorID, $submission->noPublicUse, $NOW));
            $submission->submissionID = new SubmissionID($this->db->lastInsertID());
        }
        else
        {
            $sh = $this->db->prepare("UPDATE peer_review_assignment_submissions SET noPublicUse=?, submissionTimestamp=".$this->from_unixtime("?")." WHERE submissionID=?;");
            $sh->execute(array($submission->noPublicUse, $NOW, $submission->submissionID));
        }
        $this->submissionHelpers[$assignment->submissionType]->saveAssignmentSubmission($assignment, $submission, $isNewSubmission);
    }


    function getUserIDForInstructorReview(PeerReviewAssignment $assignment, UserID $baseID, $username, SubmissionID $submissionID)
    {
        global $dataMgr;
        //Try and find an unused shadow id
        $sh = $this->db->prepare("SELECT userID from users WHERE NOT EXISTS (SELECT * from peer_review_assignment_matches WHERE userID = reviewerID AND submissionID = ?) AND ((userType = 'shadowinstructor' AND substr(username, 1, ?) = ?) OR userID = ?) ORDER BY userID LIMIT 1;");
        $basename = $username."__shadow";
        $sh->execute(array(
            $submissionID,
            strlen($basename),
            $basename,
            $baseID
        ));

        if($res = $sh->fetch())
        {
            //Return the id
            return new UserID($res->userID);
        }
        else
        {
            //We have to go and create a new shadow user
            $sh = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE userType = 'shadowinstructor' AND substr(username, 1, ?) = ? AND courseId = ?;");
            $sh->execute(array(strlen($basename), $basename, $dataMgr->courseID));
            $nameInfo = $this->dataMgr->getUserFirstAndLastNames($baseID);
            $count = $sh->fetch()->count;
            return $this->dataMgr->addUser($basename.$count, $nameInfo->firstName, $nameInfo->lastName." (".($count+1).")", 0, 'shadowinstructor');
        }
    }

    function getUserIDForAnonymousReview(PeerReviewAssignment $assignment, UserID $baseID, $username, SubmissionID $submissionID)
    {
        global $dataMgr;
        //Try and find an unused anonymous id
        $sh = $this->db->prepare("SELECT userID from users WHERE NOT EXISTS (SELECT * from peer_review_assignment_matches WHERE userID = reviewerID AND submissionID = ?) AND (userType = 'anonymous' AND substr(username, 1, ?) = ?) ORDER BY userID LIMIT 1;");
        $basename = $username."__anonymous";
        $sh->execute(array(
            $submissionID,
            strlen($basename),
            $basename
        ));

        if($res = $sh->fetch())
        {
            //Return the id
            return new UserID($res->userID);
        }
        else
        {
            //We have to go and create a new shadow user
            $sh = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE userType = 'anonymous' AND substr(username, 1, ?) = ? AND courseId = ?;");
            $sh->execute(array(strlen($basename), $basename, $dataMgr->courseID));
            $nameInfo = $this->dataMgr->getUserFirstAndLastNames($baseID);
            $count = $sh->fetch()->count;
            $lastName = $nameInfo->lastName;
            if($count > 0)
                $lastName.= " (".($count+1).")";
            return $this->dataMgr->addUser($basename.$count, "Anonymous ".$nameInfo->firstName, $lastName, 0, 'anonymous');
        }
    }

	function getUserIDForCopyingReview(PeerReviewAssignment $assignment, UserID $baseID, $username, SubmissionID $submissionID)
    {
        global $dataMgr;
        //Try and find an unused anonymous id
        $sh = $this->db->prepare("SELECT userType FROM users WHERE userID = ?;");
		$sh->execute(array($baseID));
		$userType = $sh->fetch()->userType;
		
		if($userType = 'anonymous' OR $userType = 'shadowmarker' OR $userType = 'shadowinstructor' OR $userType = 'marker' OR $userType = 'instructor')
			$isShadow = true;
		else 
			$isShadow = false;
	
		if($isShadow)
		{
			//trim username to its original
			$index = strrpos($username, '__anonymous');
			if($index===false); else
			$username = substr($username, 0, $index);
		}
		$basename = $username."__anonymous";	
        
        $sh = $this->db->prepare("SELECT userID from users, assignments WHERE assignments.courseID = users.courseID AND assignments.assignmentID = ? AND NOT EXISTS (SELECT * from peer_review_assignment_matches WHERE userID = reviewerID AND submissionID = ?) AND (userType = 'anonymous' AND substr(username, 1, ?) = ?) ORDER BY userID LIMIT 1;");
        $sh->execute(array(
        	$assignment->assignmentID,
            $submissionID,
            strlen($basename),
            $basename
        ));

        if($res = $sh->fetch())
        {
            //Return the id
            return new UserID($res->userID);
        }
        else
        {
            //We have to go and create a new shadow user
            $sh = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE userType = 'anonymous' AND substr(username, 1, ?) = ? AND courseId = ?;");
            $sh->execute(array(strlen($basename), $basename, $dataMgr->courseID));
            $nameInfo = $this->dataMgr->getUserFirstAndLastNames($baseID);
            $count = $sh->fetch()->count;
            $lastName = $nameInfo->lastName;
            $firstName = $nameInfo->firstName;
            if($count > 0)
            {
            	if($isShadow)
            	{
            		$i = strpos($firstName, "Anonymous");
            		if($k===false); else $firstName = substr($firstName, 10, strlen($firstName));
			        $j= strrpos($lastName, " (");
					if($j===false); else $lastName = substr($lastName, 0, $j);
                }
				$firstName = "Anonymous ".$firstName;
				$lastName.= " (".($count+1).")";
            }
			
			return $this->dataMgr->addUser($basename.$count, $firstName, $lastName, 0, 'anonymous');
        }
    }

    function getUserIDForAnonymousSubmission(PeerReviewAssignment $assignment, UserID $baseID, $username)
    {
        global $dataMgr;
        //Try and find an unused anonymous id
        $sh = $this->db->prepare("SELECT userID from users WHERE NOT EXISTS (SELECT * from peer_review_assignment_submissions WHERE userID = authorID AND assignmentID= ?) AND (userType = 'anonymous' AND substr(username, 1, ?) = ? AND courseID = ?) ORDER BY userID LIMIT 1;");
        $basename = $username."__anonymous";
        $sh->execute(array(
            $assignment->assignmentID,
            strlen($basename),
            $basename,
            $dataMgr->courseID
        ));

        if($res = $sh->fetch())
        {
            //Return the id
            return new UserID($res->userID);
        }
        else
        {
            //We have to go and create a new shadow user
            $sh = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE userType = 'anonymous' AND substr(username, 1, ?) = ? AND courseId = ?;");
            $sh->execute(array(strlen($basename), $basename, $dataMgr->courseID));
            $nameInfo = $this->dataMgr->getUserFirstAndLastNames($baseID);
            $count = $sh->fetch()->count;
            $lastName = $nameInfo->lastName;
            if($count > 0)
                $lastName.= " (".($count+1).")";
            return $this->dataMgr->addUser($basename.$count, "Anonymous ".$nameInfo->firstName, $lastName, 0, 'anonymous');
        }
    }

	//modified function getUserIDForAnonymousSubmission to also handle copying submissions from anonymous 
	//and make the newly generated usernames, firstnames and lastnames more manageable 
	function getUserIDForCopyingSubmission(PeerReviewAssignment $assignment, UserID $baseID, $username)
	{
        global $dataMgr;
		
		$sh = $this->db->prepare("SELECT userType FROM users WHERE userID = ?;");
		$sh->execute(array($baseID));
		$userType = $sh->fetch()->userType;
		
		if($userType = 'anonymous' OR $userType = 'shadowmarker' OR $userType = 'shadowinstructor' OR $userType = 'marker' OR $userType = 'instructor')
		{
			$isShadow = true;
		}
		else 
			$isShadow = false;
	
		if($isShadow)
		{
			//trim username to its original
			$index = strrpos($username, '__anonymous');
			if($index===false); else
			$username = substr($username, 0, $index);
		}
		$basename = $username."__anonymous";	

        //Try and find an unused anonymous id
        $sh = $this->db->prepare("SELECT userID from users WHERE NOT EXISTS (SELECT * from peer_review_assignment_submissions WHERE userID = authorID AND assignmentID= ?) AND (userType = 'anonymous' AND substr(username, 1, ?) = ? AND courseID = ?) ORDER BY userID LIMIT 1;");
        $sh->execute(array(
            $assignment->assignmentID,
            strlen($basename),
            $basename,
            $dataMgr->courseID
        ));

        if($res = $sh->fetch())
        {
            //Return the id
            return new UserID($res->userID);
        }
        else
        {
            //We have to go and create a new shadow user
            $sh = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE userType = 'anonymous' AND substr(username, 1, ?) = ? AND courseId = ?;");
            $sh->execute(array(strlen($basename), $basename, $dataMgr->courseID));
            $nameInfo = $this->dataMgr->getUserFirstAndLastNames($baseID);
            $count = $sh->fetch()->count;
            $lastName = $nameInfo->lastName;
            $firstName = $nameInfo->firstName;
            if($count > 0)
            {
            	if($isShadow)
            	{
            		$i = strpos($firstName, "Anonymous");
            		if($k===false); else $firstName = substr($firstName, 10, strlen($firstName));
			        $j= strrpos($lastName, " (");
					if($j===false); else $lastName = substr($lastName, 0, $j);
                }
				$firstName = "Anonymous ".$firstName;
				$lastName.= " (".($count+1).")";
            }
			
			return $this->dataMgr->addUser($basename.$count, $firstName, $lastName, 0, 'anonymous');
        }
	
	}

  	function createMatch(PeerReviewAssignment $assignment, SubmissionID $submissionID, UserID $reviewerID, $instructorForced=False, $calibrationState='none')
    {
      # Hacky bool translation
      if($instructorForced)
        $instructorForced = 1;
      else
        $instructorForced = 0;
	   
	  //$sh = $this->db->prepare("INSERT INTO peer_review_assignment_matches (submissionID, reviewerID, instructorForced) VALUES (?, ?, ?);");
      //$sh->execute(array($submissionID, $reviewerID, $instructorForced));
      $sh = $this->db->prepare("INSERT INTO peer_review_assignment_matches (submissionID, reviewerID, instructorForced, calibrationState) VALUES (?, ?, ?, ?);");
	  $sh->execute(array($submissionID, $reviewerID, $instructorForced, $calibrationState));
      return new MatchID($this->db->lastInsertID());
    }
    
    function assignCalibrationReview(PeerReviewAssignment $assignment, SubmissionID $submissionID, UserID $reviewerID, $required=false)
    {
      //Insert the match here
      $matchID = $this->createMatch($assignment, $submissionID, $reviewerID, false, 'attempt');

      # Hacky bool translation
      /*if($required)
        $required = 1;
      else
        $required = 0;
      $sh = $this->db->prepare("INSERT INTO peer_review_assignment_calibration_matches (matchID, assignmentID, required) VALUES (?, ?, ?);");
      $sh->execute(array($matchID, $assignment->assignmentID, $required));*/
      return $matchID;
    }

    function getInstructorMatchesForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->db->prepare("SELECT matches.matchID as matchID FROM peer_review_assignment_matches matches JOIN users ON users.userID = matches.reviewerID WHERE userType in ('instructor', 'marker', 'shadowinstructor', 'shadowmarker') AND submissionID = ?;");
        $sh->execute(array($submissionID));
        $ids = array();
        while($res = $sh->fetch()){
            $ids[] = new MatchID($res->matchID);
        }
        return $ids;
    }
	
	function getSingleInstructorReviewForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $ids = $this->getInstructorMatchesForSubmission($assignment, $submissionID);
        if(sizeof($ids) != 1){
            throw new Exception("Submission $submissionID does not have exactly 1 instructor review");
        }
        return $this->getReview($assignment, $ids[0]);
    }
	
	function getCalibrationKeyMatchesForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->db->prepare("SELECT matches.matchID as matchID FROM peer_review_assignment_matches matches WHERE matches.calibrationState = 'key' AND submissionID = ?;");
        $sh->execute(array($submissionID));
        $ids = array();
        while($res = $sh->fetch()){
            $ids[] = new MatchID($res->matchID);
        }
        return $ids;
    }

	function getSingleCalibrationKeyReviewForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $ids = $this->getCalibrationKeyMatchesForSubmission($assignment, $submissionID);
        if(sizeof($ids) != 1){
            throw new Exception("Submission $submissionID does not have exactly 1 calibration key review");
        }
        return $this->getReview($assignment, $ids[0]);
    }

    function removeMatch(PeerReviewAssignment $assignment, MatchID $matchID)
    {
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_matches WHERE matchID = ?;");
        $sh->execute(array($matchID));
    }
	
    function removeSpotChecks(PeerReviewAssignment $assignment)
    {
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_spot_checks WHERE submissionID IN (SELECT submissionID FROM peer_review_assignment_submissions where assignmentID = ?);");
        $sh->execute(array($assignment->assignmentID));
    }

    function getMatchID(PeerReviewAssignment $assignment, MechanicalTA_ID $id, UserID $reviewer = null)
    {
        if(!is_null($reviewer))
        {
            //They are looking up the review on a author-reviewer pair
            if(get_class($id) == "UserID")
            {
                //Do the query
                $sh = $this->prepareQuery("getMatchIDByAuthorReviewerPairQuery", "SELECT matchID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE assignmentID = ? AND peer_review_assignment_submissions.authorID=? AND reviewerID = ?;");
                $sh->execute(array($assignment->assignmentID, $id, $reviewer));
                $res = $sh->fetch();
                return new MatchID($res->matchID);
            }
            else if(get_class($id) == "SubmissionID")
            {
                //We better have a match id
                $sh = $this->prepareQuery("getMatchIDBySubmissionAndReviewerQuery", "SELECT matchID FROM peer_review_assignment_matches WHERE submissionID =? AND reviewerID = ?;");
                $sh->execute(array($id, $reviewer));
                $res = $sh->fetch();
                return new MatchID($res->matchID);
            }
            else
            {
               throw new Exception("This call wanted a user id as the second arg, but got ".get_class($id));
            }
        }
        else if(get_class($id) == "MatchID")
        {
            return $id;
        }
        throw new Exception("Unable to get a review using a ".get_class($id));
    }

    function getReviewerByMatch(PeerReviewAssignment $assignment, MatchID $id)
    {
        $sh = $this->db->prepare("SELECT reviewerID FROM peer_review_assignment_matches WHERE matchID = ?;");
        $sh->execute(array($id));
        $res = $sh->fetch();
        if($res)
            return new UserID($res->reviewerID);
        return NULL;
    }

    function getReview(PeerReviewAssignment $assignment, MechanicalTA_ID $id, UserID $reviewer = null)
    {
        if(!is_null($reviewer))
        {
            //They are looking up the review on a author-reviewer pair
            if(get_class($id) == "UserID")
            {
                //Do the query
                $sh = $this->prepareQuery("getReviewHeaderByAuthorReviewerPairQuery", "SELECT matchID, peer_review_assignment_matches.submissionID as submissionID, reviewerID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE assignmentID = ? AND peer_review_assignment_submissions.authorID=? AND reviewerID = ?;");
                $sh->execute(array($assignment->assignmentID, $id, $reviewer));
                $headerRes = $sh->fetch();
            }
            else if(get_class($id) == "SubmissionID")
            {
                //We better have a match id
                $sh = $this->prepareQuery("getReviewHeaderBySubmissionAndReviewerQuery", "SELECT matchID, peer_review_assignment_matches.submissionID as submissionID, reviewerID FROM peer_review_assignment_matches WHERE submissionID =? AND reviewerID = ?;");
                $sh->execute(array($id, $reviewer));
                $headerRes = $sh->fetch();
            }
            else
            {
               throw new Exception("This call wanted a user id as the second arg, but got ".get_class($id));
            }
        }
        else if(get_class($id) == "MatchID")
        {
            //We better have a match id
            $sh = $this->prepareQuery("getReviewHeaderByMatchQuery", "SELECT matchID, peer_review_assignment_matches.submissionID as submissionID, reviewerID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE matchID=?;");
            $sh->execute(array($id));
            $headerRes = $sh->fetch();
        }
        else
        {
            throw new Exception("Unable to get a review using a ".get_class($id));
        }
        if(!$headerRes)
            throw new Exception("Could not find review");

        $questionSH = $this->prepareQuery("getReviewByMatchQuery", "SELECT questionID, answerInt, answerText, ".$this->unix_timestamp("reviewTimestamp")." as reviewTimestamp FROM peer_review_assignment_review_answers WHERE matchID=?;");
        $questionSH->execute(array($headerRes->matchID));

        //Make a new review
        $review = new Review($assignment);
        $review->matchID = new MatchID($headerRes->matchID);
        $review->submissionID = new SubmissionID($headerRes->submissionID);
        $review->reviewerID = new UserID($headerRes->reviewerID);
        while($res = $questionSH->fetch())
        {
            $answer = new ReviewAnswer();
            if(!is_null($res->answerText))
                $answer->text = $res->answerText;
            if(!is_null($res->answerInt))
                $answer->int = $res->answerInt;

            $review->answers[$res->questionID] = $answer;
			//Timestamp for all answers are kept in the one reviewobject
			$review->reviewTimestamp = $res->reviewTimestamp;
        }
        return $review;
    }

    function getReviewDraft(PeerReviewAssignment $assignment, MechanicalTA_ID $id, UserID $reviewer = null)
    {
        if(!is_null($reviewer))
        {
            //They are looking up the review on a author-reviewer pair
            if(get_class($id) != "UserID")
                throw new Exception("This call wanted a user id as the second arg, but got ".get_class($id));
            //Do the query
            $sh = $this->prepareQuery("getReviewDraftHeaderByAuthorReviewerPairQuery", "SELECT matchID, peer_review_assignment_matches.submissionID, reviewerID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE assignmentID = ? AND peer_review_assignment_submissions.authorID=?, reviewerID = ?;");
            $sh->execute(array($assignment->assignmentID, $id, $reviewer));
            $headerRes = $sh->fetch();
        }
        else if(get_class($id) == "MatchID")
        {
            //We better have a match id
            $sh = $this->prepareQuery("getReviewDraftHeaderByMatchQuery", "SELECT matchID, peer_review_assignment_matches.submissionID, reviewerID FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID WHERE matchID=?;");
            $sh->execute(array($id));
            $headerRes = $sh->fetch();
        }
        else
        {
            throw new Exception("Unable to get a review using a ".get_class($id));
        }
        if(!$headerRes)
            throw new Exception("Could not find review");

        $questionSH = $this->prepareQuery("getReviewDraftByMatchQuery", "SELECT questionID, answerInt, answerText FROM peer_review_assignment_review_answers_drafts WHERE matchID=?;");
        $questionSH->execute(array($headerRes->matchID));

        //Make a new review
        $review = new Review($assignment);
        $review->matchID = new MatchID($headerRes->matchID);
        $review->submissionID = new SubmissionID($headerRes->submissionID);
        $review->reviewerID = new UserID($headerRes->reviewerID);
        while($res = $questionSH->fetch())
        {
            $answer = new ReviewAnswer();
            if(!is_null($res->answerText))
                $answer->text = $res->answerText;
            if(!is_null($res->answerInt))
                $answer->int = $res->answerInt;

            $review->answers[$res->questionID] = $answer;
        }
        return $review;
    }

    function saveReview(PeerReviewAssignment $assignment, Review $review)
    {
    	global $NOW;
		
        $this->db->beginTransaction();
        $this->deleteReview($assignment, $review->matchID, false);
        $sh = $this->prepareQuery("insertReviewAnswerQuery", "INSERT INTO peer_review_assignment_review_answers (matchID, questionID, answerInt, answerText, reviewTimestamp) VALUES (?, ?, ?, ?, ".$this->from_unixtime("?").");");
        foreach($review->answers as $questionID => $answer)
        {
            $answerText = NULL;
            $answerInt = NULL;
            if(isset($answer->text) AND !is_null($answer->text))
                $answerText = $answer->text;
            if(isset($answer->int) AND !is_null($answer->int))
                $answerInt = $answer->int;
            $sh->execute(array($review->matchID, $questionID, $answerInt, $answerText, $NOW));
        }
        $this->db->commit();
    }

    function saveReviewDraft(PeerReviewAssignment $assignment, Review $review)
    {
        $this->db->beginTransaction();
        $this->deleteReviewDraft($assignment, $review->matchID);
        $sh = $this->prepareQuery("insertReviewDraftsAnswerQuery", "INSERT INTO peer_review_assignment_review_answers_drafts (matchID, questionID, answerInt, answerText) VALUES (?, ?, ?, ?);");
        foreach($review->answers as $questionID => $answer)
        {
            $answerText = NULL;
            $answerInt = NULL;
            if(isset($answer->text) AND !is_null($answer->text))
                $answerText = $answer->text;
            if(isset($answer->int) AND !is_null($answer->int))
                $answerInt = $answer->int;
            $sh->execute(array($review->matchID, $questionID, $answerInt, $answerText));
        }
        $this->db->commit();
    }

    function deleteReview(PeerReviewAssignment $assignment, MatchID $id, $removeMatch=true, $onlyIfForced=true)
    {
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_review_answers WHERE matchID = ?;");
        $sh->execute(array($id));
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_review_marks WHERE matchID = ?;");
        $sh->execute(array($id));
        if($removeMatch)
        {
            if($onlyIfForced) {
                $sh = $this->db->prepare("DELETE FROM peer_review_assignment_matches WHERE matchID = ? AND instructorForced = 1;");
            } else {
                $sh = $this->db->prepare("DELETE FROM peer_review_assignment_matches WHERE matchID = ?;");
            }
            $sh->execute(array($id));
        }
    }
    function deleteReviewDraft(PeerReviewAssignment $assignment, MatchID $id)
    {
        $sh = $this->db->prepare("DELETE FROM peer_review_assignment_review_answers_drafts WHERE matchID = ?;");
        $sh->execute(array($id));
    }

    function getMatchesForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->prepareQuery("getMatchesForSubmissionQuery", "SELECT matchID FROM peer_review_assignment_matches JOIN users ON reviewerID = userID WHERE submissionID = ? ORDER BY userType, matchID;");
        $sh->execute(array($submissionID));

        $matches = array();
        while($res = $sh->fetch())
        {
            $matches[] = new MatchID($res->matchID);
        }
        return $matches;
    }

    function getReviewsForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $matches = $this->getMatchesForSubmission($assignment, $submissionID);

        $reviews = array();
        foreach($matches as $matchID) {
            $reviews[] = $this->getReview($assignment, $matchID);
        }
        return $reviews;
    }
    
    //Only get matches whose reviewer is a student and is not for calibration
    function getStudentMatchesForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->prepareQuery("getStudentMatchesForSubmissionQuery", "SELECT matchID FROM peer_review_assignment_matches JOIN users ON reviewerID = userID WHERE submissionID = ? AND userType = 'student' AND calibrationState = 'none' ORDER BY matchID;");
        $sh->execute(array($submissionID));

        $matches = array();
        while($res = $sh->fetch())
        {
            $matches[] = new MatchID($res->matchID);
        }
        return $matches;
    }
	
	function getStudentReviewsForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $matches = $this->getStudentMatchesForSubmission($assignment, $submissionID);

        $reviews = array();
        foreach($matches as $matchID) {
            $reviews[] = $this->getReview($assignment, $matchID);
        }
        return $reviews;
    }

    function saveSpotCheck(PeerReviewAssignment $assignment, SpotCheck $check)
    {
    	$array = array("submissionID"=>$check->submissionID, "checkerID"=>$check->checkerID, "status"=>$check->status);
    	switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->prepareQuery("saveSpotCheckQuery", "INSERT INTO peer_review_assignment_spot_checks (submissionID, checkerID, status) VALUES (:submissionID, :checkerID, :status) ON DUPLICATE KEY UPDATE checkerID=:checkerID, status=:status;");
       			$sh->execute($array);
       			break;
			case 'sqlite':
				$sh = $this->prepareQuery("saveSpotCheckQuery", "INSERT OR IGNORE INTO peer_review_assignment_spot_checks (submissionID, checkerID, status) VALUES (:submissionID, :checkerID, :status);");
				$sh->execute($array);
				$sh = $this->prepareQuery("saveSpotCheckQuery2", "UPDATE peer_review_assignment_spot_checks SET checkerID=:checkerID, status=:status WHERE submissionID=:submissionID;");
				$sh->execute($array);
       			break;
		}
    }

    function getSpotCheck(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->prepareQuery("getSpotCheckQuery", "SELECT submissionID, checkerID, status FROM peer_review_assignment_spot_checks checks WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
        $res = $sh->fetch();
        if(!$res)
            throw new Exception("No spot check for submission $submissionID");
        return new SpotCheck(new SubmissionID($res->submissionID), new UserID($res->checkerID), $res->status);
    }

    function getSpotCheckMap(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("getSpotCheckMapQuery", "SELECT checks.submissionID, checkerID, status FROM peer_review_assignment_spot_checks checks JOIN peer_review_assignment_submissions subs ON checks.submissionID = subs.submissionID WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $checks = array();
        while($res = $sh->fetch())
        {
            $checks[$res->submissionID] = new SpotCheck(new SubmissionID($res->submissionID), new UserID($res->checkerID), $res->status);
        }
        return $checks;
    }

    function getSpotChecksForMarker(PeerReviewAssignment $assignment, UserID $userID)
    {
        $sh = $this->prepareQuery("getSpotChecksForMarkerQuery", "SELECT checks.submissionID, checkerID, status FROM peer_review_assignment_spot_checks checks JOIN peer_review_assignment_submissions subs ON checks.submissionID = subs.submissionID WHERE assignmentID = ? AND checkerID = ?;");
        $sh->execute(array($assignment->assignmentID,$userID));

        $checks = array();
        while($res = $sh->fetch())
        {
            $checks[$res->submissionID] = new SpotCheck(new SubmissionID($res->submissionID), new UserID($res->checkerID), $res->status);
        }
        return $checks;
    }
    
    function touchSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID, UserID $userID)
    {
        global $NOW;
		$array = array("submissionID"=>$submissionID, "instructorID"=>$userID, "timestamp"=>$NOW);
		switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->prepareQuery("touchSubmissionQuery", "INSERT INTO peer_review_assignment_instructor_review_touch_times (submissionID, instructorID, timestamp) VALUES (:submissionID, :instructorID, FROM_UNIXTIME(:timestamp)) ON DUPLICATE KEY UPDATE timestamp=FROM_UNIXTIME(:timestamp);");
       			$sh->execute($array);
       			break;
			case 'sqlite':
				$sh = $this->prepareQuery("touchSubmissionQuery", "INSERT OR IGNORE INTO peer_review_assignment_instructor_review_touch_times (submissionID, instructorID, timestamp) VALUES (:submissionID, :instructorID, datetime(:timestamp,'unixepoch'));");
				$sh->execute($array);
				$sh = $this->prepareQuery("touchSubmissionQuery2", "UPDATE peer_review_assignment_instructor_review_touch_times SET timestamp=datetime(:timestamp,'unixepoch') WHERE submissionID = :submissionID AND instructorID = :instructorID;");
				$sh->execute($array);
       			break;
		}
    }

    function getTouchesForSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $sh = $this->prepareQuery("getTouchesForSubmissionQuery", "SELECT submissionID, instructorID as userID, ".$this->unix_timestamp("timestamp")." as timestamp FROM peer_review_assignment_instructor_review_touch_times WHERE submissionID = ? ORDER BY timestamp DESC;");
        $sh->execute(array($submissionID));

        return $sh->fetchAll();
    }

    function getAssignmentStatistics(PeerReviewAssignment $assignment)
    {
        global $dataMgr;
        $stats = new stdClass;

        $sh = $this->prepareQuery("numSubmissionsQuery", "SELECT count(*) as c FROM peer_review_assignment_submissions subs LEFT OUTER JOIN peer_review_assignment_denied denied ON subs.assignmentID = denied.assignmentID AND subs.authorID = denied.userID WHERE denied.userID is null AND subs.assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));
        $stats->numSubmissions = $sh->fetch()->c;

        $sh = $this->prepareQuery("numPossibleSubmissionsQuery", "SELECT count(*) as c from users WHERE courseID = ? AND userType = 'student' AND userID not in (SELECT users.userID from users LEFT OUTER JOIN peer_review_assignment_denied denied ON users.userID = denied.userID WHERE assignmentID = ?);");
        $sh->execute(array($dataMgr->courseID, $assignment->assignmentID));
        $stats->numPossibleSubmissions = $sh->fetch()->c;
        $sh = $this->prepareQuery("numStudentReviewsQuery","SELECT count(distinct matches.matchID) as c FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON matches.submissionID = subs.submissionID JOIN peer_review_assignment_review_answers ans ON matches.matchID = ans.matchID WHERE assignmentID=? AND instructorForced = 0;");

        $sh->execute(array($assignment->assignmentID));
        $stats->numStudentReviews = $sh->fetch()->c;

        $sh = $this->prepareQuery("numPossibleStudentReviewsQuery", "SELECT COUNT(*) as c FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON matches.submissionID = subs.submissionID WHERE assignmentID=? AND instructorForced = 0;");
        $sh->execute(array($assignment->assignmentID));
        $stats->numPossibleStudentReviews = $sh->fetch()->c;

        $sh = $this->prepareQuery("numUnmarkedSubmissionsQuery","SELECT COUNT(*) as c FROM peer_review_assignment_submissions subs LEFT OUTER JOIN peer_review_assignment_denied denied ON subs.assignmentID = denied.assignmentID AND subs.authorID = denied.userID JOIN peer_review_assignment_submission_marks marks ON subs.submissionID = marks.submissionID WHERE denied.userID is null AND subs.assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));
        $stats->numUnmarkedSubmissions = $stats->numSubmissions - $sh->fetch()->c;

        $sh = $this->prepareQuery("numUnmarkedReviewsQuery","SELECT count(distinct matches.matchID) as c FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON matches.submissionID = subs.submissionID JOIN peer_review_assignment_review_answers ans ON matches.matchID = ans.matchID JOIN peer_review_assignment_review_marks marks ON marks.matchID = matches.matchID WHERE assignmentID=? AND instructorForced = 0;");
        $sh->execute(array($assignment->assignmentID));
        $stats->numUnmarkedReviews = $stats->numStudentReviews - $sh->fetch()->c;

        $sh = $this->prepareQuery("numPendingAppealsQuery","SELECT COUNT(matches.matchID) as c FROM peer_review_assignment_appeal_messages messages LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID JOIN users ON messages.authorID = users.userID WHERE messages2.appealMessageID IS NULL AND submissions.assignmentID = ? AND users.userType='student';");
        $sh->execute(array($assignment->assignmentID));
        $stats->numPendingAppeals = $sh->fetch()->c;

        $sh = $this->prepareQuery("numPendingSpotChecksQuery","SELECT COUNT(*) as c FROM peer_review_assignment_spot_checks checks JOIN peer_review_assignment_submissions subs ON subs.submissionID = checks.submissionID WHERE status = 'pending' AND assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));
        $stats->numPendingSpotChecks = $sh->fetch()->c;

        return $stats;
    }

    function getAssignmentStatisticsForUser(PeerReviewAssignment $assignment, UserID $user)
    {
        $stats = new stdClass;

        $sh = $this->prepareQuery("numUnmarkedSubmissionsForUserQuery","SELECT count(*) as c FROM peer_review_assignment_submissions subs LEFT OUTER JOIN peer_review_assignment_matches matches ON subs.submissionID = matches.submissionID LEFT OUTER JOIN peer_review_assignment_submission_marks marks ON subs.submissionID = marks.submissionID WHERE marks.score is null AND assignmentID=? AND matches.reviewerID = ?;");
        $sh->execute(array($assignment->assignmentID, $user));
        $stats->numUnmarkedSubmissions = $sh->fetch()->c;

        $sh = $this->prepareQuery("numUnmarkedReviewsForUserQuery","SELECT count(distinct matches.matchID) as c FROM peer_review_assignment_matches matches LEFT OUTER JOIN peer_review_assignment_review_marks marks ON matches.matchID = marks.matchID LEFT OUTER JOIN peer_review_assignment_review_answers ans ON ans.matchID = matches.matchID WHERE matches.submissionID IN (SELECT subs.submissionID FROM peer_review_assignment_submissions subs LEFT OUTER JOIN peer_review_assignment_matches matches ON subs.submissionID = matches.submissionID LEFT OUTER JOIN peer_review_assignment_submission_marks marks ON subs.submissionID = marks.submissionID WHERE assignmentID=:assignment AND matches.reviewerID = :user) AND marks.score is NULL AND ans.matchID is not null AND matches.reviewerID != :user;");
        $sh->execute(array("assignment"=>$assignment->assignmentID, "user"=>$user));
        $stats->numUnmarkedReviews = $sh->fetch()->c;

        $sh = $this->prepareQuery("numPendingSpotChecksForUserQuery","SELECT COUNT(*) as c FROM peer_review_assignment_spot_checks checks JOIN peer_review_assignment_submissions subs ON subs.submissionID = checks.submissionID WHERE status = 'pending' AND assignmentID = ? AND checkerID = ?;");
        $sh->execute(array($assignment->assignmentID, $user));
        $stats->numPendingSpotChecks = $sh->fetch()->c;

        return $stats;
    }

    function getReviewMap(PeerReviewAssignment $assignment)
    {
        //First, figure out what should be there
        $reviewMap = array();

        //This is a beast, all it does is grab a list of submission-reviewer ids for the current assignment, that actually have something in the answers array, ordering by user type then match id
        //It also indicates if a match has answers (questionID != NULL)
        $sh = $this->prepareQuery("getExistingReviewerMapQuery", "SELECT peer_review_assignment_matches.submissionID, peer_review_assignment_matches.reviewerID, peer_review_assignment_review_answers.questionID, peer_review_assignment_matches.matchID, peer_review_assignment_matches.instructorForced FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID LEFT OUTER JOIN peer_review_assignment_review_answers ON peer_review_assignment_matches.matchID = peer_review_assignment_review_answers.matchID JOIN users ON peer_review_assignment_matches.reviewerID = users.userID WHERE peer_review_assignment_submissions.assignmentID = ? GROUP BY peer_review_assignment_matches.matchID ORDER BY users.userType, peer_review_assignment_matches.matchID;");
        $sh->execute(array($assignment->assignmentID));
        while($res = $sh->fetch())
        {
            if(!array_key_exists($res->submissionID, $reviewMap))
            {
                $reviewMap[$res->submissionID] = array();
            }
            $obj = new stdClass();
            $obj->reviewerID = new UserID($res->reviewerID);
            $obj->exists = !is_null($res->questionID);
            $obj->matchID = new MatchID($res->matchID);
            $obj->instructorForced = $res->instructorForced;
            $reviewMap[$res->submissionID][] = $obj;
        }
        return $reviewMap;
    }
    
    function getReviewsForMarker(PeerReviewAssignment $assignment, UserID $reviewerID)
    {
        $reviews = array();
		
        $sh = $this->prepareQuery("getReviewsForMarkerQuery", "SELECT peer_review_assignment_matches.submissionID, peer_review_assignment_review_answers.questionID, peer_review_assignment_matches.matchID, peer_review_assignment_matches.instructorForced FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID LEFT OUTER JOIN peer_review_assignment_review_answers ON peer_review_assignment_matches.matchID = peer_review_assignment_review_answers.matchID JOIN users ON peer_review_assignment_matches.reviewerID = users.userID WHERE peer_review_assignment_submissions.assignmentID = ? AND peer_review_assignment_matches.reviewerID = ? GROUP BY peer_review_assignment_matches.matchID ORDER BY users.userType, peer_review_assignment_matches.matchID;");
        $sh->execute(array($assignment->assignmentID, $reviewerID));
        while($res = $sh->fetch())
        {
            $obj = new stdClass();
            $obj->submissionID = new SubmissionID($res->submissionID);
            $obj->exists = !is_null($res->questionID);
            $obj->matchID = new MatchID($res->matchID);
            $obj->instructorForced = $res->instructorForced;
            $reviews[] = $obj;
        }
        return $reviews;
    }

    //Get calibration reviews for calibration submissions 
    function getCorrectReviewMap(PeerReviewAssignment $assignment)
    {
        //First, figure out what should be there
        $reviewMap = array();

        $sh = $this->prepareQuery("getCorrectReviewerMapQuery", "SELECT matches.submissionID, matches.reviewerID, answers.questionID, matches.matchID, matches.instructorForced FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON matches.submissionID = subs.submissionID LEFT OUTER JOIN peer_review_assignment_review_answers answers ON matches.matchID = answers.matchID JOIN users ON matches.reviewerID = users.userID WHERE matches.calibrationState = 'key' AND subs.assignmentID = ? GROUP BY matches.matchID ORDER BY users.userType, matches.matchID;"); 
        $sh->execute(array($assignment->assignmentID));
        while($res = $sh->fetch())
        {
            if(!array_key_exists($res->submissionID, $reviewMap))
            {
                $reviewMap[$res->submissionID] = array();
            }
            $obj = new stdClass();
            $obj->reviewerID = new UserID($res->reviewerID);
            $obj->exists = !is_null($res->questionID);
            $obj->matchID = new MatchID($res->matchID);
            $obj->instructorForced = $res->instructorForced;
            $reviewMap[$res->submissionID][] = $obj;
        }
        return $reviewMap;
    }

    function getReviewDraftMap(PeerReviewAssignment $assignment)
    {
        //First, figure out what should be there
        $reviewMap = array();

        //This is a beast, all it does is grab a list of submission-reviewer ids for the current assignment, that actually have something in the answers array, ordering by user type then match id
        //It also indicates if a match has answers (questionID != NULL)
        $sh = $this->prepareQuery("getExistingReviewerDraftMapQuery", "SELECT peer_review_assignment_matches.submissionID, peer_review_assignment_matches.reviewerID, peer_review_assignment_review_answers_drafts.questionID, peer_review_assignment_matches.matchID, peer_review_assignment_matches.instructorForced FROM peer_review_assignment_matches JOIN peer_review_assignment_submissions ON peer_review_assignment_matches.submissionID = peer_review_assignment_submissions.submissionID LEFT OUTER JOIN peer_review_assignment_review_answers_drafts ON peer_review_assignment_matches.matchID = peer_review_assignment_review_answers_drafts.matchID JOIN users ON peer_review_assignment_matches.reviewerID = users.userID WHERE peer_review_assignment_submissions.assignmentID = ? GROUP BY peer_review_assignment_matches.matchID ORDER BY users.userType, peer_review_assignment_matches.matchID;");
        $sh->execute(array($assignment->assignmentID));
        while($res = $sh->fetch())
        {
            if(!array_key_exists($res->submissionID, $reviewMap))
            {
                $reviewMap[$res->submissionID] = array();
            }
            $obj = new stdClass();
            $obj->reviewerID = new UserID($res->reviewerID);
            $obj->exists = !is_null($res->questionID);
            $obj->matchID = new MatchID($res->matchID);
            $obj->instructorForced = $res->instructorForced;
            $reviewMap[$res->submissionID][] = $obj;
        }
        return $reviewMap;
    }

    function getMatchScoreMap(PeerReviewAssignment $assignment)
    {
        $scoreMap = array();

        //Another beast. This avoids us from having to load all the reviews, and computes this all on the DB
        $sh = $this->prepareQuery("getMatchScoreMapQuery", "SELECT peer_review_assignment_review_answers.matchID, SUM(score) as score FROM peer_review_assignment_review_answers JOIN peer_review_assignment_matches ON peer_review_assignment_matches.matchID = peer_review_assignment_review_answers.matchID JOIN peer_review_assignment_submissions ON peer_review_assignment_submissions.submissionID = peer_review_assignment_matches.submissionID LEFT OUTER JOIN peer_review_assignment_radio_options ON peer_review_assignment_radio_options.questionID = peer_review_assignment_review_answers.questionID WHERE peer_review_assignment_radio_options.`index` = peer_review_assignment_review_answers.answerInt AND peer_review_assignment_submissions.assignmentID = ? GROUP BY peer_review_assignment_review_answers.matchID;");
        $sh->execute(array($assignment->assignmentID));

        while($res = $sh->fetch())
        {
            $scoreMap[$res->matchID] = $res->score;
        }

        return $scoreMap;
    }

    function appealExists(PeerReviewAssignment $assignment, MatchID $matchID, $appealType)
    {
        $sh = $this->prepareQuery("appealExistsQuery", "SELECT COUNT(appealMessageID) as c FROM peer_review_assignment_appeal_messages WHERE matchID = ? AND appealType = ?;");
        $sh->execute(array($matchID, $appealType));
        return $sh->fetch()->c > 0;
    }

    function getAppeal(PeerReviewAssignment $assignment, MatchID $matchID, $appealType)
    {
        $sh = $this->prepareQuery("getAppealQuery", "SELECT appealMessageID, authorID, text FROM peer_review_assignment_appeal_messages WHERE matchID = ? AND appealType = ? ORDER BY appealMessageID;");
        $sh->execute(array($matchID, $appealType));

        $appeal = new Appeal($matchID, $appealType);
        while($res = $sh->fetch())
        {
            $appeal->messages[] = new AppealMessage($res->appealMessageID, $appealType, $matchID, new UserID($res->authorID), $res->text);
        }

        return $appeal;
    }

    function saveAppealMessage(PeerReviewAssignment $assignment, AppealMessage $message)
    {
        if(!isset($message->appealMessageID) OR is_null($message->appealMessageID))
        {
            $sh = $this->db->prepare("INSERT INTO peer_review_assignment_appeal_messages (matchID, appealType, authorID, viewedByStudent, text) VALUES(?, ?, ?, 0, ?);");
            $sh->execute(array($message->matchID, $message->appealType, $message->authorID, $message->message));
            $message->appealMessageID = $this->db->lastInsertID();
        }
        else
        {
            $sh = $this->db->prepare("UPDATE peer_review_assignment_appeal_messages SET text = ? WHERE appealMessageID = ?;");
            $sh->execute(array($message->message, $message->appealMessageID));
        }
    }

    function markAppealAsViewedByStudent(PeerReviewAssignment $assignment, MatchID $matchID, $appealType)
    {
        $sh = $this->db->prepare("UPDATE peer_review_assignment_appeal_messages SET viewedByStudent = 1 WHERE matchID = ? AND appealType = ?;");
        $sh->execute(array($matchID, $appealType));
    }

    function hasNewAppealMessage(PeerReviewAssignment $assignment, MatchID $matchID, $appealType)
    {
        $sh = $this->prepareQuery("hasNewAppealMessageQuery", "SELECT COUNT(appealMessageID) as c FROM peer_review_assignment_appeal_messages WHERE matchID = ? AND appealType=? AND viewedByStudent=0;");
        $sh->execute(array($matchID, $appealType));
        return $sh->fetch()->c > 0;
    }

    function getReviewAppealMap(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("getReviewAppealMapQuery", "SELECT matches.matchID, users.userType='student' as needsResponse FROM peer_review_assignment_appeal_messages messages LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID JOIN users ON messages.authorID = users.userID WHERE messages2.appealMessageID IS NULL AND submissions.assignmentID = ? AND messages.appealType = 'review';");
        $sh->execute(array($assignment->assignmentID));

        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->matchID] = $res->needsResponse;
        }
        return $map;
    }

    function getReviewMarkAppealMap(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("getReviewMarkAppealMapQuery", "SELECT matches.matchID, users.userType='student' as needsResponse FROM peer_review_assignment_appeal_messages messages LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID JOIN users ON messages.authorID = users.userID WHERE messages2.appealMessageID IS NULL AND submissions.assignmentID = ? AND messages.appealType = 'reviewmark';");
        $sh->execute(array($assignment->assignmentID));

        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->matchID] = $res->needsResponse;
        }
        return $map;
    }
	
	//Strictly for peerreview/inc/appealstaskmanager.php
	/*function getAppealMapBySubmission(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("getReviewAppealMapBySubmissionQuery", "SELECT matches.submissionID, matches.matchID, users.userType='student' as needsResponse FROM peer_review_assignment_appeal_messages messages LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID JOIN users ON messages.authorID = users.userID WHERE messages2.appealMessageID IS NULL AND submissions.assignmentID = ? AND messages.appealType = 'review' ORDER BY matches.submissionID;");
        $sh->execute(array($assignment->assignmentID));

        $map = array();
        while($res = $sh->fetch())
        {
        	if(!array_key_exists($res->submissionID, $map))
			{
				$map[$res->submissionID] = new stdClass();
				$map[$res->submissionID]->review = array();
			}
            $map[$res->submissionID]->review[$res->matchID] = $res->needsResponse;
        }
		
		
		$sh = $this->prepareQuery("getReviewMarkAppealMapBySubmissionQuery", "SELECT matches.submissionID, matches.matchID, users.userType='student' as needsResponse FROM peer_review_assignment_appeal_messages messages LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID JOIN users ON messages.authorID = users.userID WHERE messages2.appealMessageID IS NULL AND submissions.assignmentID = ? AND messages.appealType = 'reviewmark' ORDER BY matches.submissionID;");
        $sh->execute(array($assignment->assignmentID));
		while($res = $sh->fetch())
        {
        	if(!array_key_exists($res->submissionID, $map))
			{
				$map[$res->submissionID] = new stdClass();
				$map[$res->submissionID]->reviewmark = array();
			}
            $map[$res->submissionID]->reviewmark[$res->matchID] = $res->needsResponse;
        }
        
        return $map;
    }*/

    function getMarkerToAppealedSubmissionsMap(PeerReviewAssignment $assignment)
    {
    	$sh = $this->prepareQuery("getMarkerToAppealedSubmissionsMapQuery", "SELECT submissionID, markerID FROM appeal_assignment WHERE submissionID IN (SELECT submissionID FROM peer_review_assignment_submissions WHERE assignmentID = ?);");
    	$sh->execute(array($assignment->assignmentID));
    	
    	$map = array();
    	while($res = $sh->fetch())
   		{
    		if(!array_key_exists($res->markerID, $map))
    		{
    			$map[$res->markerID] = array();
   			}
   			$map[$res->markerID][$res->submissionID] = new SubmissionID($res->submissionID);
    	}
    	return $map;
  	}
	
	//TODO: Should simply be Appealed Submissions to Marker Map
	function getAppealMatchToMarkerMap(PeerReviewAssignment $assignment)
    {
    	$sh = $this->prepareQuery("getAppealMatchToMarkerMapQuery", "SELECT matches.matchID, markerID FROM appeal_assignment JOIN peer_review_assignment_matches matches ON appeal_assignment.submissionID = matches.submissionID WHERE appeal_assignment.submissionID IN (SELECT submissionID FROM peer_review_assignment_submissions WHERE assignmentID = ?);");
    	$sh->execute(array($assignment->assignmentID));
    	
    	$map = array();
    	while($res = $sh->fetch())
   		{
			$map[$res->matchID] = $res->markerID;
    	}
    	return $map;
  	}
	
	function getUnansweredAppealsForMarker(PeerReviewAssignment $assignment, UserID $markerID)
    {
    	$sh = $this->prepareQuery("getUnansweredAppealsForMarkerQuery", "SELECT matches.matchID, messages.appealType FROM peer_review_assignment_appeal_messages messages LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID JOIN users ON messages.authorID = users.userID JOIN appeal_assignment ON appeal_assignment.submissionID = matches.submissionID WHERE messages2.appealMessageID IS NULL AND submissions.assignmentID = ? AND users.userType='student' AND appeal_assignment.markerID = ?;");
    	$sh->execute(array($assignment->assignmentID, $markerID));
    	
    	$unansweredAppeals = array();
    	while($res = $sh->fetch())
   		{
   			$obj = new stdClass();
			$obj->appealType = $res->appealType;
			$obj->matchID = $res->matchID;
			$unansweredAppeals[] = $obj;
    	}
    	return $unansweredAppeals;
  	}
	
	function assignAppeal(PeerReviewAssignment $assignment, MatchID $matchID)
	{
		global $dataMgr;
			
		$markerToAppealedSubmissionsMap = $assignment->getMarkerToAppealedSubmissionsMap();
		
		$submissionID = $assignment->getSubmissionID($matchID);
		
		$markers = $dataMgr->getMarkers();
		
		$markerTasks = array();
		
		//Fill-in marker tasks with current appeal assignments
		foreach($markers as $markerID)
		{
			if(array_key_exists($markerID, $markerToAppealedSubmissionsMap))
				$markerTasks[$markerID] = $markerToAppealedSubmissionsMap[$markerID];
			else
				$markerTasks[$markerID] = array();
		}
		
		//Load spot check map for avoiding spotchecking and answering appeals from the same submission
		$spotCheckMap = $assignment->getSpotCheckMap();
		$markerToSubmissionsMap = $assignment->getMarkerToSubmissionsMap();
		
		//Load target loads for all markers
		$markingLoadMap = array();
		$sumLoad = 0;
		foreach($markers as $markerID)
		{
			$markerLoad = $dataMgr->getMarkingLoad(new UserID($markerID));
			$markingLoadMap[$markerID] = $markerLoad;
			$sumLoad += $markerLoad;
		}
		$targetLoads = array();
		foreach($markers as $markerID)
			$targetLoads[$markerID] = precisionFloat($markingLoadMap[$markerID]/$sumLoad);

		//If appealmessage is already under a submission already in a marker's appeal assignment just exit this function
		if(array_reduce($markerToAppealedSubmissionsMap, function($res, $item) use ($submissionID){return array_key_exists($submissionID->id, $item) OR $res;}))
			return;

		//Create load defecit array to best select which marker is farthest from his target load and hence should be assigned this appeal
		$loadDefecits = array();
		$totalSubs = array_reduce($markerTasks, function($res, $item){return sizeof($item) + $res;});
		foreach($markers as $markerID)
		{
			if($targetLoads[$markerID] == 0) continue; //under no circumstances should marker with 0 be assigned an appeal even if there is no other non-conflicting marker
			$loadDefecits[$markerID] = $targetLoads[$markerID] - (1.0*sizeof($markerTasks[$markerID]))/$totalSubs;
		}
		//Pick the marker to assign the appeal
		while(1)
		{
			if(sizeof($loadDefecits) < 1)
				return;
			$res = array_keys($loadDefecits, max($loadDefecits));
			$markerID = $res[0];
			//Ensure that the marker to assign the appeal is not the marker of the submission
			if(array_key_exists($submissionID->id, $markerToSubmissionsMap[$markerID]))
			{
				unset($loadDefecits[$markerID]);
				continue;
			}
			//Ensure that the marker to assign the appeal is not the spotchecker of the submission
			if(isset($spotCheckMap[$submissionID->id]) ? ($spotCheckMap[$submissionID->id]->checkerID->id == $markerID) : false)
			{
				unset($loadDefecits[$markerID]);
				continue;
			}
			break;
		}
		$sh = $this->prepareQuery("assignAppealQuery", "INSERT INTO appeal_assignment (markerID, submissionID) VALUES (:markerID, :submissionID);");
		$sh->execute(array("submissionID"=>$submissionID->id, "markerID"=>$markerID));	
	}
	
    function getNumberOfTimesReviewedByUserMap(PeerReviewAssignment $assignment, UserID $reviewerID)
    {
        //First, we need the counts of actuall reviews
        $sh = $this->prepareQuery("getNumberOfTimesReviewedByUserMapQuery", "SELECT submissions.authorID as authorID, count(distinct(matches.matchID)) as c FROM peer_review_assignment_review_answers answers JOIN peer_review_assignment_matches matches ON answers.matchID = matches.matchID JOIN peer_review_assignment_submissions submissions ON matches.submissionID = submissions.submissionID JOIN peer_review_assignment assignments ON submissions.assignmentID = assignments.assignmentID WHERE matches.reviewerID = ? AND assignments.reviewStopDate < ".$this->from_unixtime("?")." GROUP BY matches.matchID;");

        $sh->execute(array($reviewerID, grace($assignment->reviewStopDate)));

        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->authorID] = $res->c;
        }

        //Now, we need the counts of spot checks
        $sh = $this->prepareQuery("getNumberOfTimesSpotCheckedByUserMapQuery", "SELECT submissions.authorID as authorID, count(submissions.submissionID) as c FROM peer_review_assignment_spot_checks  checks JOIN peer_review_assignment_submissions submissions ON checks.submissionID = submissions.submissionID JOIN peer_review_assignment assignments ON submissions.assignmentID = assignments.assignmentID WHERE checks.checkerID = ? AND assignments.reviewStopDate < ".$this->from_unixtime("?")." GROUP BY submissions.authorID;");

        $sh->execute(array($reviewerID, grace($assignment->reviewStopDate)));

        while($res = $sh->fetch())
        {
            if(array_key_exists($res->authorID, $map))
                $map[$res->authorID] += $res->c;
            else
                $map[$res->authorID] = $res->c;
        }
        return $map;
    }

	function getCalibrationSubmissionIDs(PeerReviewAssignment $assignment)
	{
		$sh = $this->prepareQuery("getCalibrationSubmissions", "SELECT subs.submissionID FROM peer_review_assignment_matches matches, peer_review_assignment_submissions subs WHERE subs.assignmentID = ? AND matches.submissionID = subs.submissionID AND matches.calibrationState = 'key';");
		$sh->execute(array($assignment->assignmentID));
		$calibrationSubmissionIDs = array();		
		while($res = $sh->fetch())
        {
			$calibrationSubmissionIDs[$res->submissionID] = $res->submissionID;
        }
        return $calibrationSubmissionIDs;
	}

	function isInSameCourse(Assignment $assignment, Assignment $otherAssignment)
	{
		$sh = $this->prepareQuery("isInSameCourse", "SELECT x.courseID FROM assignments x, assignments y WHERE x.courseID = y.courseID AND x.assignmentID = ? AND y.assignmentID = ?");
		$sh->execute(array($assignment->assignmentID, $otherAssignment->assignmentID));
		$res = $sh->fetch();
		return $res != NULL;
	}
	
	function numCalibrationReviewsDone(PeerReviewAssignment $assignment, UserID $userID)
	{
		$sh = $this->prepareQuery("numCalibrationReviewsDone", "SELECT COUNT(DISTINCT matches.matchID) FROM peer_review_assignment_submissions subs, peer_review_assignment_matches matches, peer_review_assignment_review_answers answers WHERE subs.assignmentID = ? AND matches.reviewerID = ? AND matches.calibrationState = 'attempt' AND subs.submissionID = matches.submissionID AND matches.matchID = answers.matchID;"); 
		$sh->execute(array($assignment->assignmentID, $userID));
		$res = $sh->fetch(PDO::FETCH_NUM);
		return $res[0];
	}

	function getStudentToCovertReviewsMap(PeerReviewAssignment $assignment)
	{
		$sh = $this->prepareQuery("getStudentToCovertReviewsMap", "SELECT reviewerID, matchID FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON matches.submissionID = subs.submissionID WHERE calibrationState = 'covert' AND subs.assignmentID = ? ORDER BY reviewerID, matchID;"); 
		$sh->execute(array($assignment->assignmentID));
		$covertCalibrations = array();
		while($res = $sh->fetch())
		{
			if(!array_key_exists($res->reviewerID, $covertCalibrations))
				$covertCalibrations[$res->reviewerID] = array();
			$covertCalibrations[$res->reviewerID][] = $res->matchID;		
		}
		return $covertCalibrations;
	}
	
	function getStudentToCovertScoresMap(PeerReviewAssignment $assignment)
	{
		$sh = $this->prepareQuery("getStudentToCovertScoresMap", "SELECT reviewerID, reviewPoints FROM peer_review_assignment_matches matches JOIN peer_review_assignment_submissions subs ON matches.submissionID = subs.submissionID LEFT OUTER JOIN peer_review_assignment_review_marks marks ON marks.matchID = matches.matchID WHERE calibrationState = 'covert' AND subs.assignmentID = ? ORDER BY reviewerID, matches.matchID;"); 
		$sh->execute(array($assignment->assignmentID));
		$covertScores = array();
		while($res = $sh->fetch())
		{
			if(!array_key_exists($res->reviewerID, $covertScores))
				$covertScores[$res->reviewerID] = array();
			$covertScores[$res->reviewerID][] = (!is_null($res->reviewPoints)) ? $res->reviewPoints : -1; //could be NULL if covert review not done
		}
		return $covertScores;
	}
	
	function supervisedSubmissions(PeerReviewAssignment $assignment)
	{
		global $dataMgr;
		$sh = $this->prepareQuery("supervisedSubmissions", "SELECT subs.submissionID, COUNT(DISTINCT answers.matchID) as numReviews FROM peer_review_assignment_submissions subs LEFT OUTER JOIN peer_review_assignment_denied denied ON subs.authorID = denied.userID AND subs.assignmentID = denied.assignmentID JOIN peer_review_assignment_matches matches ON matches.submissionID = subs.submissionID LEFT OUTER JOIN peer_review_assignment_review_answers answers ON matches.matchID = answers.matchID WHERE denied.userID IS NULL AND subs.assignmentID = :assignmentID AND subs.authorID IN (SELECT userID FROM users WHERE userType = 'student') AND subs.authorID NOT IN (SELECT userID FROM peer_review_assignment_independent WHERE assignmentID = :assignmentID) GROUP BY subs.submissionID;");
		$sh->execute(array("assignmentID"=>$assignment->assignmentID));
		$result = array();
		while($res = $sh->fetch())
        {
			$result[$res->submissionID] = $res->numReviews;
		}
		return $result;
	}

	function independentSubmissions(PeerReviewAssignment $assignment)
	{
		global $dataMgr;
		$sh = $this->prepareQuery("independentSubmissions", "SELECT subs.submissionID, COUNT(DISTINCT answers.matchID) as numReviews FROM peer_review_assignment_submissions subs LEFT OUTER JOIN peer_review_assignment_denied denied ON subs.authorID = denied.userID AND subs.assignmentID = denied.assignmentID JOIN peer_review_assignment_matches matches ON matches.submissionID = subs.submissionID LEFT OUTER JOIN peer_review_assignment_review_answers answers ON matches.matchID = answers.matchID WHERE denied.userID IS NULL AND subs.assignmentID = :assignmentID AND subs.authorID IN (SELECT userID FROM users WHERE userType = 'student') AND subs.authorID IN (SELECT userID FROM peer_review_assignment_independent WHERE assignmentID = :assignmentID) GROUP BY subs.submissionID;");
		$sh->execute(array("assignmentID"=>$assignment->assignmentID));
		$result = array();
		while($res = $sh->fetch())
        {
			$result[$res->submissionID] = $res->numReviews;
		}
		return $result;
	}
	
    //Because PHP doesn't do multiple inheritance, we have to define this method all over the place
	private function prepareQuery($name, $query)
    {
        if(!isset($this->$name)) {
            $this->$name = $this->db->prepare($query);
        }
        return $this->$name;
    }
	
};
