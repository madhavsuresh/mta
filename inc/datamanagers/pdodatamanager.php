<?php

require_once("inc/common.php");
require_once("inc/datamanager.php");

class PDODataManager extends DataManager
{
    private $db;
    function prepareQuery($name, $query)
    {
        if(!isset($this->$name)) {
            $this->$name = $this->db->prepare($query);
        }
        return $this->$name;
    }

    private $isUserQuery;
    private $userIDQuery;
    private $isInstructorQuery;
    private $isMarkerQuery;
    private $getConfigPropertyQuery;
    function __construct()
    {
        global $MTA_DATAMANAGER_PDO_CONFIG;
        if(!isset($MTA_DATAMANAGER_PDO_CONFIG["dsn"])) { die("PDO Data manager needs a DSN"); }
        if(!isset($MTA_DATAMANAGER_PDO_CONFIG["username"])) { die("PDODataManager needs a database user name"); }
        if(!isset($MTA_DATAMANAGER_PDO_CONFIG["password"])) { die("PDODataManager needs a database user password"); }
        //Load up a connection to the database
        $this->db = new PDO($MTA_DATAMANAGER_PDO_CONFIG["dsn"],
                            $MTA_DATAMANAGER_PDO_CONFIG["username"],
                            $MTA_DATAMANAGER_PDO_CONFIG["password"],
                            array(PDO::ATTR_PERSISTENT => true));

        $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->db->exec("SET NAMES 'utf8';");

        $this->isUserQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && userID=? ;"); //&& userType IN ('instructor', 'student', 'marker');");
        $this->isStudentQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? && userType = 'student';");
        $this->isUserByNameQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && username=? && userType IN ('instructor', 'student', 'marker');");
        $this->userIDQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && username=? ;");
        $this->isInstructorQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? && (userType IN ('instructor', 'shadowinstructor'));");
        $this->isMarkerQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? && (userType IN ('marker', 'shadowmarker', 'instructor', 'shadowinstructor'));");
        $this->getAssignmentHeadersQuery = $this->db->prepare("SELECT assignmentID, name, assignmentType, displayPriority FROM assignments WHERE courseID = ? ORDER BY displayPriority DESC;");
        $this->getAssignmentHeaderQuery = $this->db->prepare("SELECT name, assignmentType, displayPriority FROM assignments WHERE assignmentID = ?;");
        $this->getUsernameQuery = $this->db->prepare("SELECT username FROM users WHERE userID=?;");
        $this->getUsersQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? ORDER BY lastName, firstName;");
        $this->getStudentsQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && userType = 'student' ORDER BY lastName, firstName;");
        $this->getUserDisplayMapQuery = $this->db->prepare("SELECT userID, firstName, lastName FROM users WHERE courseID=? ORDER BY lastName, firstName;");
        $this->getUserDisplayNameQuery = $this->db->prepare("SELECT firstName, lastName FROM users WHERE userID=?;");
        $this->getUserAliasMapQuery = $this->db->prepare("SELECT userID, alias FROM users WHERE courseID=?;");
        $this->getUserAliasQuery = $this->db->prepare("SELECT alias FROM users WHERE userID=?;");
        $this->setUserAliasQuery = $this->db->prepare("UPDATE users SET alias = ? WHERE userID=?;");
        $this->numUserTypeQuery = $this->db->prepare("SELECT COUNT(userID) FROM users WHERE courseID=? && userType=?;");
        $this->assignmentExistsQuery = $this->db->prepare("SELECT assignmentID FROM assignments WHERE assignmentID=?;");
        $this->assignmentFieldsQuery = $this->db->prepare("SELECT password, passwordMessage, visibleToStudents FROM assignments WHERE assignmentID=?;");
        $this->getEnteredPasswordQuery = $this->db->prepare("SELECT userID from assignment_password_entered WHERE assignmentID = ? && userID = ?;");
        $this->userEnteredPasswordQuery = $this->db->prepare("INSERT INTO assignment_password_entered (assignmentID, userID) VALUES (?, ?);");

        $this->addAssignmentToCourseQuery = $this->db->prepare( "INSERT INTO assignments (courseID, name, displayPriority, assignmentType) SELECT :courseID, :name, COUNT(courseID), :type FROM assignments WHERE courseID=:courseID;",
                                                                array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $this->removeAssignmentFromCourseQuery = $this->db->prepare("DELETE FROM assignments WHERE assignmentID = ?;");
        $this->updateAssignmentQuery = $this->db->prepare("UPDATE assignments SET name=?, password=?, passwordMessage=?, visibleToStudents=? WHERE assignmentID = ?;");
        //$this->getConfigPropertyQuery = $this->db->prepare("SELECT *;");
        //$this->assignmentSwapDisplayOrderQuery = $this->db->prepare("UPDATE assignments SET

 		$this->getAllAssignmentHeadersQuery = $this->db->prepare("SELECT assignmentID, name, courseID, assignmentType, displayPriority FROM assignments WHERE assignmentType = 'peerreview' ORDER BY displayPriority ASC;");
		//$this->getAllCalibrationPoolsQuery = $this->db->prepare("SELECT assignmentID, a.name, a.courseID, a.assignmentType, a.displayPriority FROM assignments a, peer_review_assignment_submissions ps, users u WHERE ps.assignmentID = a.assignmentID AND ps.authorID = u.a AND u.userType = 'anonymous' ORDER BY displayPriority ASC;");
		$this->getAllCalibrationPoolsQuery = $this->db->prepare("SELECT a.assignmentID, a.name, a.courseID, a.assignmentType, a.displayPriority FROM assignments a, peer_review_assignment_calibration_pools pcp WHERE a.assignmentID = pcp.poolAssignmentID ORDER BY displayPriority ASC;");
        
		$this->getCalibrationAssignmentHeadersQuery = $this->db->prepare("SELECT a.assignmentID, a.name, a.assignmentType, a.displayPriority FROM assignments a, peer_review_assignment_calibration_matches pr WHERE a.assignmentID = pr.assignmentID AND courseID = ? ORDER BY displayPriority DESC;");
		
		#before deprication of calibration matches
		//$this->getCalibrationReviewsQuery = $this->db->prepare("SELECT DISTINCT(pram.matchID), prara.reviewTimeStamp, prarm.reviewPoints FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm, peer_review_assignment_calibration_matches pracm WHERE pram.reviewerID = ? AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID AND pram.matchID = pracm.matchID ORDER BY prara.reviewTimeStamp DESC;");
		#after deprication of calibration matches
		$this->getCalibrationReviewsQuery = $this->db->prepare("SELECT DISTINCT(pram.matchID), prara.reviewTimeStamp, prarm.reviewPoints FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;"); 
		$this->getCalibrationReviewsAfterDateQuery = $this->db->prepare("SELECT DISTINCT(pram.matchID), prara.reviewTimeStamp, prarm.reviewPoints FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND prara.reviewTimestamp > FROM_UNIXTIME(?) AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;");
		
		$this->numCalibrationReviewsQuery = $this->db->prepare("SELECT COUNT(DISTINCT pram.matchID) FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;");
       	$this->numCalibrationReviewsAfterDateQuery = $this->db->prepare("SELECT COUNT(DISTINCT pram.matchID) FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND prara.reviewTimestamp > FROM_UNIXTIME(?) AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;");
       	
       	$this->latestAssignmentWithFlaggedIndependentsQuery = $this->db->prepare("SELECT peer_review_assignment.assignmentID FROM peer_review_assignment, peer_review_assignment_independent WHERE peer_review_assignment.assignmentID = peer_review_assignment_independent.assignmentID ORDER BY peer_review_assignment.calibrationStopDate DESC LIMIT 10");
        
		$this->getMarkingLoadQuery = $this->db->prepare("SELECT markingLoad FROM users WHERE userID=?");
		
		$this->getRecentPeerReviewAssignmentsQuery = $this->db->prepare("SELECT assignmentID FROM peer_review_assignment WHERE reviewStopDate > FROM_UNIXTIME(?) && reviewStopDate < FROM_UNIXTIME(?);");
		
        //Now we can set up all the assignment data managers
        parent::__construct();
    }

    function getDatabase()
    {
        return $this->db;
    }

    function setCourseFromID(CourseID $id)
    {
        //Get the course information
        $sh = $this->db->prepare("SELECT name, displayName, authType, registrationType FROM course WHERE courseID = ?;");
        $sh->execute(array($id));
        if(!$res = $sh->fetch())
        {
            throw new Exception("Invalid course id '$id'");
        }
        $this->courseID = new CourseID($id);
        $this->courseName = $res->name;
        $this->courseDisplayName = $res->displayName;
        $this->authMgrType = $res->authType;
        $this->registrationType = $res->registrationType;
    }


    function setCourseFromName($name)
    {
        $sh = $this->db->prepare("SELECT courseID, displayName, authType, registrationType FROM course WHERE name = ?;");
        $sh->execute(array($name));
        if(!$res = $sh->fetch())
        {
            throw new Exception("Invalid course '$name'");
        }
        $this->courseID = new CourseID($res->courseID);
        $this->courseName = $name;
        $this->courseDisplayName = $res->displayName;
        $this->authMgrType = $res->authType;
        $this->registrationType = $res->registrationType;
    }

    function addUser($username, $firstName, $lastName, $studentID, $type='student', $markingLoad=0)
    {
        $sh = $this->db->prepare("INSERT INTO users (courseID, username, firstName, lastName, studentID, userType, markingLoad) VALUES (?, ?, ?, ?, ?, ?, ?);");
        $sh->execute(array($this->courseID, $username, $firstName, $lastName, $studentID, $type, $markingLoad));
        return new UserID($this->db->lastInsertID());
    }

    function getUserInfo(UserID $id)
    {
        $sh = $this->db->prepare("SELECT courseID, username, firstName, lastName, studentID, userType FROM users where userID = ?;");
        $sh->execute(array($id));
        $ret = $sh->fetch();
        if(!is_null($ret)){
            $ret->userID = $id;
        }
        return $ret;
    }

    function updateUser(UserID $id, $username, $firstName, $lastName, $studentID, $type, $markingLoad=0)
    {
    	/*if($markingLoad != 0)
		{*/
			$sh = $this->db->prepare("UPDATE users SET username = ?, firstName = ?, lastName = ?, studentID = ?, userType = ?, markingLoad = ? WHERE userID = ?;");
        	$sh->execute(array($username, $firstName, $lastName, $studentID, $type, $markingLoad, $id));
		/*}
		else
		{
			$sh = $this->db->prepare("UPDATE users SET username = ?, firstName = ?, lastName = ?, studentID = ?, userType = ? WHERE userID = ?;");
        	$sh->execute(array($username, $firstName, $lastName, $studentID, $type, $id));
		}*/
    }

    function getUserID($username)
    {
        $this->userIDQuery->execute(array($this->courseID, $username));
        $res = $this->userIDQuery->fetch();
        if(!$res)
            throw new Exception("Could not get a user id for '$username'");
        return new UserID($res->userID);
    }

    /** Checks to see if the given user is actually a user
     */
    function isUser(UserID $userid)
    {
        $this->isUserQuery->execute(array($this->courseID, $userid));
        return $this->isUserQuery->fetch() != NULL;
    }

    /** Checks to see if the given user is actually a user
     */
    function isStudent(UserID $userid)
    {
        $this->isStudentQuery->execute(array($userid));
        return $this->isStudentQuery->fetch() != NULL;
    }

    function getUserFirstAndLastNames(UserID $userid)
    {
        $sh = $this->db->prepare("SELECT firstName, lastName FROM users WHERE userID = ?;");
        $sh->execute(array($userid));
        if(!$res = $sh->fetch())
        {
            throw new Exception("Could not get a user info for user '$userid'");
        }
        return $res;
    }

    function isUserByName($username)
    {
        $this->isUserByNameQuery->execute(array($this->courseID, $username));
        return $this->isUserByNameQuery->fetch() != NULL;
    }

    /** Checks to see if the given user is an instructor
     */
    function isMarker(UserID $userid)
    {
        $this->isMarkerQuery->execute(array($userid));
        return $this->isMarkerQuery->fetch() != NULL;
    }

    /** Checks to see if the given user is an instructor
     */
    function isInstructor(UserID $userid)
    {
        $this->isInstructorQuery->execute(array($userid));
        return $this->isInstructorQuery->fetch() != NULL;
    }

    function getUserAlias(UserID $userID)
    {
        $this->getUserAliasQuery->execute(array($userID));
        if(!$res = $this->getUserAliasQuery->fetch())
        {
            throw new Exception("No user with id '$userID'");
        }
        else
        {
            if(is_null($res->alias)){
                return "Anonymous";
            }
            return $res->alias;
        }
    }

    function setUserAlias(UserID $userID, $alias)
    {
        $this->setUserAliasQuery->execute(array($alias, $userID));
    }


    /** Gets a user's name
     */
    function getUserDisplayName(UserID $userID)
    {
        $this->getUserDisplayNameQuery->execute(array($userID));
        if(!$res = $this->getUserDisplayNameQuery->fetch())
        {
            throw new Exception("No user with id '$userID'");
        }
        else
        {
            return $res->firstName." ".$res->lastName;
        }
    }
    function getUsername(UserID $userID)
    {
        $this->getUsernameQuery->execute(array($userID));
        if(!$res = $this->getUsernameQuery->fetch())
        {
            throw new Exception("No user with id '$userID'");
        }
        else
        {
            return $res->username;
        }
    }

    function getConfigProperty($property)
    {
        throw new Exception("Not Implemented");
        //$sh = $this->db->prepare("SELECT value FROM course_config WHERE course = '$COURSE' and property = ?;");
        $this->getConfigPropertyQuery->execute(array($property));
        if($count == 0)
            return NULL;
        else
            return $sh->fetch()->value;
    }

    function getUserDisplayMap()
    {
        $this->getUserDisplayMapQuery->execute(array($this->courseID));

        $users = array();
        while($res = $this->getUserDisplayMapQuery->fetch())
        {
            $users[$res->userID] = $res->firstName." ".$res->lastName;
        }
        return $users;
    }
    
    function getUserAliasMap()
    {
        $this->getUserAliasMapQuery->execute(array($this->courseID));

        $users = array();
        while($res = $this->getUserAliasMapQuery->fetch())
        {
            $users[$res->userID] = $res->alias;
        }
        return $users;
    }

    function getUsers()
    {
        $this->getUsersQuery->execute(array($this->courseID));
        return array_map(function($x) { return new UserID($x->userID); }, $this->getUsersQuery->fetchAll());
    }

    function getStudents()
    {
        $this->getStudentsQuery->execute(array($this->courseID));
        return array_map(function($x) { return new UserID($x->userID); }, $this->getStudentsQuery->fetchAll());
    }

    function getInstructors()
    {
        $sh = $this->prepareQuery("getInstructorsQuery", "SELECT userID FROM users WHERE userType='instructor' && courseID=?;");
        $sh->execute(array($this->courseID));
        $instructors = array();
        while($res = $sh->fetch())
            $instructors[] = $res->userID;
        return $instructors;
    }

    function getMarkers()
    {
        $sh = $this->prepareQuery("getMarkersQuery", "SELECT userID FROM users WHERE (userType='instructor' || userType='marker') && courseID=?;");
        $sh->execute(array($this->courseID));
        $instructors = array();
        while($res = $sh->fetch())
            $instructors[] = $res->userID;
        return $instructors;
    }

    function getStudentIDMap()
    {
        $sh = $this->db->prepare("SELECT userID, studentID FROM users WHERE courseID=?;");
        $sh->execute(array($this->courseID));
        $map = array();
        while($res = $sh->fetch())
        {
            $map[$res->userID] = $res->studentID;
        }
        return $map;
    }

    function getAssignmentHeaders()
    {
        $this->getAssignmentHeadersQuery->execute(array($this->courseID));
        $headers = array();
        while($res = $this->getAssignmentHeadersQuery->fetch())
        {
            $headers[] = new AssignmentHeader(new AssignmentID($res->assignmentID), $res->name, $res->assignmentType, $res->displayPriority);
        }
        return $headers;
    }

    function getAssignmentHeader(AssignmentID $id)
    {
        $this->getAssignmentHeaderQuery->execute(array($id));
        if(!$res = $this->getAssignmentHeaderQuery->fetch())
        {
            throw new Exception("No Assignment with id '$id' found");
        }
        return new AssignmentHeader($id, $res->name, $res->assignmentType, $res->displayPriority);
    }

    function numStudents()
    {
        $this->numUserTypeQuery->execute(array($this->courseID, 'student'));
        $res = $this->numUserTypeQuery->fetch(PDO::FETCH_NUM);
        return $res[0];
    }

    function numInstructors()
    {
        $this->numUserTypeQuery->execute(array($this->courseID, 'instructors'));
        $res = $this->numUserTypeQuery->fetch(PDO::FETCH_NUM);
        return $res[0];
    }

    function assignmentExists(AssignmentID $id)
    {
        $this->assignmentExistsQuery->execute(array($id));
        return $this->assignmentExistsQuery->fetch() != NULL;
    }

    function moveAssignmentUp(AssignmentID $id)
    {
        $this->db->beginTransaction();
        $header = $this->getAssignmentHeader($id);
        $sh = $this->db->prepare("SELECT assignmentID FROM assignments WHERE courseID = ? && displayPriority = ?;");
        $sh->execute(array($this->courseID, $header->displayPriority+1));
        if(!$res = $sh->fetch())
            return;
        $sh = $this->db->prepare("UPDATE assignments SET displayPriority = ? - displayPriority WHERE assignmentID IN (?, ?);");
        $sh->execute(array(2*$header->displayPriority+1, $id, $res->assignmentID));
        $this->db->commit();
    }

    function hasEnteredPassword(AssignmentID $assignmentID, UserID $userID)
    {
        $this->getEnteredPasswordQuery->execute(array($assignmentID, $userID));
        return $this->getEnteredPasswordQuery->fetch() != null;
    }

    function userEnteredPassword(AssignmentID $assignmentID, UserID $userID)
    {
        $this->userEnteredPasswordQuery->execute(array($assignmentID, $userID));
    }

    function moveAssignmentDown(AssignmentID $id)
    {
        $this->db->beginTransaction();
        $header = $this->getAssignmentHeader($id);
        $sh = $this->db->prepare("SELECT assignmentID FROM assignments WHERE courseID = ? && displayPriority = ?;");
        $sh->execute(array($this->courseID, $header->displayPriority-1));
        if(!$res = $sh->fetch())
            return;
        $sh = $this->db->prepare("UPDATE assignments SET displayPriority = ? - displayPriority WHERE assignmentID IN (?, ?);");
        $sh->execute(array(2*$header->displayPriority-1,$id, $res->assignmentID));
        $this->db->commit();
    }

    function getCourses()
    {
        $sh = $this->db->prepare("SELECT name, displayName, courseID, browsable FROM course;");
        $sh->execute(array());
        return $sh->fetchall();
    }

    protected function removeAssignmentFromCourse(AssignmentID $id)
    {
        $this->removeAssignmentFromCourseQuery->execute(array($id));
    }

    protected function addAssignmentToCourse($name, $type)
    {
        //$this->db->beginTransaction();
        $this->addAssignmentToCourseQuery->execute(array("courseID"=>$this->courseID, "name"=>$name, "type"=>$type));
        $id = $this->db->lastInsertID();
        return new AssignmentID($id);
    }
    protected function updateAssignment(Assignment $assignment)
    {
        $this->updateAssignmentQuery->execute(array($assignment->name, $assignment->password, $assignment->passwordMessage, $assignment->visibleToStudents, $assignment->assignmentID));
    }

    protected function populateGeneralAssignmentFields(Assignment $assignment)
    {
        $this->assignmentFieldsQuery->execute(array($assignment->assignmentID));
        $res = $this->assignmentFieldsQuery->fetch();

        $assignment->password = $res->password;
        $assignment->passwordMessage = $res->passwordMessage;
        $assignment->visibleToStudents = $res->visibleToStudents;
    }

    function getCourseInfo(CourseID $id)
    {
        $sh = $this->db->prepare("SELECT courseID, name, displayName, courseID, authType, registrationType, browsable FROM course where courseID = ?;");
        $sh->execute(array($id));
        return $sh->fetch();
    }

    function setCourseInfo(CourseID $id, $name, $displayName, $authType, $regType, $browsable)
    {
        $sh = $this->db->prepare("UPDATE course SET name = ?, displayName = ?, authType = ?, registrationType = ?, browsable = ? WHERE courseID = ?;");
        $sh->execute(array($name, $displayName, $authType, $regType, $browsable, $id));
    }

    function createCourse($name, $displayName, $authType, $regType, $browsable)
    {
        $sh = $this->db->prepare("INSERT INTO course (name, displayName, authType, registrationType, browsable) VALUES (?, ?, ?, ?, ?);");
        $sh->execute(array($name, $displayName, $authType, $regType, $browsable));
    }
	
	function getAllAssignmentHeaders()
    {
        $this->getAllAssignmentHeadersQuery->execute();
        $headers = array();
        while($res = $this->getAllAssignmentHeadersQuery->fetch())
        {
            $headers[] = new GlobalAssignmentHeader(new AssignmentID($res->assignmentID), $res->name, new CourseID($res->courseID) , $res->assignmentType, $res->displayPriority);
        }
        return $headers;
    }
	
	function getAllCalibrationPoolHeaders()
    {
        $this->getAllCalibrationPoolsQuery->execute();
        $headers = array();
        while($res = $this->getAllCalibrationPoolsQuery->fetch())
        {
            $headers[] = new GlobalAssignmentHeader(new AssignmentID($res->assignmentID), $res->name, new CourseID($res->courseID) , $res->assignmentType, $res->displayPriority);
        }
        return $headers;
    }
	
	//NO LONGER USED
	function getCalibrationAssignments()
    {
        $calibrationAssignments = array();
        foreach($this->getCalibrationAssignmentHeaders() as $header)
        {
            $calibrationAssignments[] = $this->getAssignment($header->assignmentID, $header->assignmentType);
        }
        return $calibrationAssignments;
    }
	
	//NO LONGER USED
	function getCalibrationAssignmentHeaders()
    {
        $this->getCalibrationAssignmentHeadersQuery->execute(array($this->courseID));
        $headers = array();
        while($res = $this->getCalibrationAssignmentHeadersQuery->fetch())
        {
            $headers[] = new AssignmentHeader(new AssignmentID($res->assignmentID), $res->name, $res->assignmentType, $res->displayPriority);
        }
        return $headers;
    }
	
	function getCalibrationScores(UserID $reviewerID)
	{
		$demotionDate = $this->getDemotionEntry($reviewerID)->demotionDate;
		$calibrationScores = array();
		if($demotionDate)
		{
			$this->getCalibrationReviewsAfterDateQuery->execute(array($reviewerID, $demotionDate));
			while($res = $this->getCalibrationReviewsAfterDateQuery->fetch())
			{
		    	$calibrationScores[$res->reviewTimeStamp]= $res->reviewPoints;
			}
		}
		else
		{
			$this->getCalibrationReviewsQuery->execute(array($reviewerID));
			while($res = $this->getCalibrationReviewsQuery->fetch())
			{
		    	$calibrationScores[$res->reviewTimeStamp]= $res->reviewPoints;
			}
		}
		return $calibrationScores;
	}
	
	function numCalibrationReviews(UserID $reviewerID)
	{
		$demotionDate = $this->getDemotionEntry($reviewerID)->demotionDate;
		if($demotionDate)
		{
			$this->numCalibrationReviewsAfterDateQuery->execute(array($reviewerID, $demotionDate));
			$res = $this->numCalibrationReviewsAfterDateQuery->fetch(PDO::FETCH_NUM);
		}
		else
		{
			$this->numCalibrationReviewsQuery->execute(array($reviewerID));
			$res = $this->numCalibrationReviewsQuery->fetch(PDO::FETCH_NUM);
		}
        return $res[0];
	}
	
	function latestAssignmentWithFlaggedIndependents()
	{
		$this->latestAssignmentWithFlaggedIndependentsQuery->execute();
		$res = $this->latestAssignmentWithFlaggedIndependentsQuery->fetch();
		if($res)
			return $res->assignmentID;
		else
			return NULL;
	}
	
	function getMarkingLoad(UserID $markerID)
	{
		$this->getMarkingLoadQuery->execute(array($markerID));
		$res = $this->getMarkingLoadQuery->fetch();
		return $res->markingLoad;
	}
	
	function demote(UserID $userID, $demotionThreshold)
	{
		global $NOW;
		$sh = $this->prepareQuery("demoteQuery", "INSERT INTO peer_review_assignment_demotion_log (userID, demotionDate, demotionThreshold) VALUES (:userID, FROM_UNIXTIME(:demotionDate), :demotionThreshold) ON DUPLICATE KEY UPDATE demotionDate=FROM_UNIXTIME(:demotionDate), demotionThreshold=:demotionThreshold;");
		$sh->execute(array("userID"=>$userID, "demotionDate"=>$NOW, "demotionThreshold"=>$demotionThreshold));
	}
	
	function getDemotionEntry(UserID $userID)
	{
		$sh = $this->prepareQuery("getDemotionEntryQuery", "SELECT UNIX_TIMESTAMP(demotionDate) as demotionDate, demotionThreshold FROM peer_review_assignment_demotion_log WHERE userID = ?;");
		$sh->execute(array($userID->id));
		$res = $sh->fetch();
		if($res)
		{
			$entry = new stdClass;
			$entry->demotionDate = $res->demotionDate;
			$entry->demotionThreshold = $res->demotionThreshold;
			return $entry;
		}
		else 
			return NULL;
	}
	
	function getRecentPeerReviewAssignments()
	{
		global $NOW;
		$this->getRecentPeerReviewAssignmentsQuery->execute(array($NOW - (20*60), $NOW));
        $assignments = array();
        while($res = $this->getRecentPeerReviewAssignmentsQuery->fetch())
        {
            $assignments[] = new AssignmentID($res->assignmentID);
        }
        return $assignments;
	}
	
	function getMarkersByAssignment(AssignmentID $assignmentID)
    {
        $sh = $this->prepareQuery("getMarkersByAssignmentQuery", "SELECT userID FROM users JOIN assignments ON assignments.courseID = users.courseID WHERE (userType='instructor' || userType='marker') && assignmentID=?;");
        $sh->execute(array($assignmentID));
        $instructors = array();
        while($res = $sh->fetch())
            $instructors[] = $res->userID;
        return $instructors;
    }
	
	function getUserDisplayMapByAssignment(AssignmentID $assignmentID)
    {
    	$sh = $this->prepareQuery("getUserDisplayMapByAssignmentQuery", "SELECT userID, firstName, lastName FROM users JOIN assignments ON assignments.courseID = users.courseID WHERE assignmentID=? ORDER BY lastName, firstName;");
        $sh->execute(array($assignmentID));

        $users = array();
        while($res = $sh->fetch())
        {
            $users[$res->userID] = $res->firstName." ".$res->lastName;
        }
        return $users;
    }
	
	//NO LONGER USED
	//Complicated query basically asking if all non-calibrated submissions have been autograded or assigned to a marker
	/*function isAutogradedAndAssigned(AssignmentID $assignmentID) 
	{
		$sh = $this->prepareQuery("isAutogradedAndAssignedQuery", "SELECT subs.submissionID FROM peer_review_assignment_submissions subs WHERE (NOT EXISTS (SELECT * FROM peer_review_assignment_matches matches WHERE subs.submissionID = matches.submissionID && matches.reviewerID IN (SELECT userID FROM users WHERE (userType='instructor' || userType='marker'))) && subs.submissionID NOT IN (SELECT submissionID FROM peer_review_assignment_submission_marks)) && subs.submissionID NOT IN (SELECT submissionID FROM peer_review_assignment_matches WHERE calibrationState = 'key') && subs.assignmentID = ?;");
        $sh->execute(array($assignmentID));
		$res = $sh->fetch();
		return $res == NULL;
	}*/
	
	function isAutogradedAndAssigned(AssignmentID $assignmentID) 
	{
		$sh = $this->prepareQuery("isAutogradedAndAssignedQuery", "SELECT notificationID FROM job_notifications WHERE job = 'autogradeandassign' && success = 1 && assignmentID = ?;");
		$sh->execute(array($assignmentID));
		$res = $sh->fetch();
		return $res != NULL;
	}
	
	function createNotification(AssignmentID $assignmentID, $job, $success, $summary)
	{
		global $NOW;
		$sh = $this->prepareQuery("createNotificationQuery", "INSERT INTO job_notifications (courseID, assignmentID, job, dateRan, success, summary) VALUES ((SELECT courseID FROM assignments WHERE assignmentID = :assignmentID), :assignmentID, :job, FROM_UNIXTIME(:dateRan), :success, :summary);");
		$sh->execute(array("assignmentID"=>$assignmentID, "job"=>$job, "dateRan"=>$NOW, "success"=>$success, "summary"=>$summary));	
	}
	
	function getNotifications()
	{
		$sh = $this->prepareQuery("getNotificationsQuery", "SELECT assignmentID, job, UNIX_TIMESTAMP(dateRan) as dateRan, success, read, summary FROM job_notifications WHERE courseID = ? && read = 0 ORDER BY dateRan DESC;");
		$sh->execute(array($this->courseID));
		$notifications = array();
		while($res = $sh->fetch())
        {
        	$notification = new stdClass();
        	$notification->assignmentID = $res->assignmentID;
			$notification->job = $res->job;
			$notification->dateRan = $res->dateRan;
			$notification->success = $res->success;
			$notification->read = $res->read;
			$notification->summary = $res->summary;
            $notifications[] = $notification;
        }
        return $notifications;
	}
}
