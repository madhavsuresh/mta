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
	
	function add_seconds($time, $seconds)
	{
		$driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($driver == 'sqlite')
			return "datetime($time, '+$seconds seconds')";
		elseif($driver == 'mysql') 
			return "($time + INTERVAL $seconds SECOND)";
	}
	
    private $isUserQuery;
    private $userIDQuery;
    private $isInstructorQuery;
    private $isMarkerQuery;
    private $getConfigPropertyQuery;
    function __construct()
    {
        global $MTA_DATAMANAGER_PDO_CONFIG; global $SQLITEDB;
        if(!isset($MTA_DATAMANAGER_PDO_CONFIG["dsn"])) { die("PDO Data manager needs a DSN"); }
        if(!isset($MTA_DATAMANAGER_PDO_CONFIG["username"])) { die("PDODataManager needs a database user name"); }
        if(!isset($MTA_DATAMANAGER_PDO_CONFIG["password"])) { die("PDODataManager needs a database user password"); }
        //Load up a connection to the database
        //$this->db = new PDO("sqlite:".MTA_ROOTPATH."sqlite/$SQLITEDB.db");
        $this->db = new PDO($MTA_DATAMANAGER_PDO_CONFIG["dsn"],
                    $MTA_DATAMANAGER_PDO_CONFIG["username"],
                    $MTA_DATAMANAGER_PDO_CONFIG["password"],
                    array(PDO::ATTR_PERSISTENT => true));
        
        $this->db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        if($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
       		$this->db->exec("SET NAMES 'utf8';");
		if($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite')
		{
			$this->db->exec("PRAGMA synchronous = OFF;");
        	$this->db->exec("PRAGMA foreign_keys = ON;");
		}
        $this->isUserQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? AND userID=? ;"); //AND userType IN ('instructor', 'student', 'marker');");
        $this->isStudentQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? AND userType = 'student';");
        $this->isUserByNameQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? AND username=? AND userType IN ('instructor', 'student', 'marker');");
        $this->userIDQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? AND username=? ;");
        $this->isInstructorQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? AND (userType IN ('instructor', 'shadowinstructor'));");
        $this->isMarkerQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? AND (userType IN ('marker', 'shadowmarker', 'instructor', 'shadowinstructor'));");
        $this->getAssignmentHeadersQuery = $this->db->prepare("SELECT assignmentID, name, assignmentType, displayPriority FROM assignments WHERE courseID = ? ORDER BY displayPriority DESC;");
        $this->getAssignmentHeaderQuery = $this->db->prepare("SELECT name, assignmentType, displayPriority FROM assignments WHERE assignmentID = ?;");
        $this->getUsernameQuery = $this->db->prepare("SELECT username FROM users WHERE userID=?;");
        $this->getUsersQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? ORDER BY lastName, firstName;");
        $this->getStudentsQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? AND userType = 'student' ORDER BY lastName, firstName;");
        $this->getUserDisplayMapQuery = $this->db->prepare("SELECT userID, firstName, lastName FROM users WHERE courseID=? ORDER BY lastName, firstName;");
        $this->getUserDisplayNameQuery = $this->db->prepare("SELECT firstName, lastName FROM users WHERE userID=?;");
        $this->getUserAliasMapQuery = $this->db->prepare("SELECT userID, alias FROM users WHERE courseID=?;");
        $this->getUserAliasQuery = $this->db->prepare("SELECT alias FROM users WHERE userID=?;");
        $this->setUserAliasQuery = $this->db->prepare("UPDATE users SET alias = ? WHERE userID=?;");
        $this->numUserTypeQuery = $this->db->prepare("SELECT COUNT(userID) FROM users WHERE courseID=? AND userType=?;");
        $this->assignmentExistsQuery = $this->db->prepare("SELECT assignmentID FROM assignments WHERE assignmentID=?;");
        $this->assignmentFieldsQuery = $this->db->prepare("SELECT password, passwordMessage, visibleToStudents FROM assignments WHERE assignmentID=?;");
        $this->getEnteredPasswordQuery = $this->db->prepare("SELECT userID from assignment_password_entered WHERE assignmentID = ? AND userID = ?;");
        $this->userEnteredPasswordQuery = $this->db->prepare("INSERT INTO assignment_password_entered (assignmentID, userID) VALUES (?, ?);");

        $this->addAssignmentToCourseQuery = $this->db->prepare( "INSERT INTO assignments (courseID, name, displayPriority, assignmentType) SELECT :courseID, :name, COUNT(courseID), :type FROM assignments WHERE courseID=:courseID;",
                                                                array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $this->removeAssignmentFromCourseQuery = $this->db->prepare("DELETE FROM assignments WHERE assignmentID = ?;");
        $this->updateAssignmentQuery = $this->db->prepare("UPDATE assignments SET name=?, password=?, passwordMessage=?, visibleToStudents=? WHERE assignmentID = ?;");
        //$this->getConfigPropertyQuery = $this->db->prepare("SELECT *;");
        //$this->assignmentSwapDisplayOrderQuery = $this->db->prepare("UPDATE assignments SET

 		$this->getInstructedAssignmentHeadersQuery = $this->db->prepare("SELECT assignmentID, name, courseID, assignmentType, displayPriority FROM assignments WHERE assignmentType = 'peerreview' AND courseID IN (SELECT courseID FROM users WHERE username = ?) ORDER BY displayPriority ASC;");
		//$this->getAllCalibrationPoolsQuery = $this->db->prepare("SELECT assignmentID, a.name, a.courseID, a.assignmentType, a.displayPriority FROM assignments a, peer_review_assignment_submissions ps, users u WHERE ps.assignmentID = a.assignmentID AND ps.authorID = u.a AND u.userType = 'anonymous' ORDER BY displayPriority ASC;");
		$this->getAllCalibrationPoolsQuery = $this->db->prepare("SELECT a.assignmentID, a.name, a.courseID, a.assignmentType, a.displayPriority FROM assignments a, peer_review_assignment_calibration_pools pcp WHERE a.assignmentID = pcp.poolAssignmentID ORDER BY displayPriority ASC;");
        
		$this->getCalibrationAssignmentHeadersQuery = $this->db->prepare("SELECT a.assignmentID, a.name, a.assignmentType, a.displayPriority FROM assignments a, peer_review_assignment_calibration_matches pr WHERE a.assignmentID = pr.assignmentID AND courseID = ? ORDER BY displayPriority DESC;");
		
		#before deprication of calibration matches
		//$this->getCalibrationReviewsQuery = $this->db->prepare("SELECT DISTINCT(pram.matchID), prara.reviewTimeStamp, prarm.reviewPoints FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm, peer_review_assignment_calibration_matches pracm WHERE pram.reviewerID = ? AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID AND pram.matchID = pracm.matchID ORDER BY prara.reviewTimeStamp DESC;");
		#after deprication of calibration matches
		$this->getCalibrationReviewsQuery = $this->db->prepare("SELECT DISTINCT(pram.matchID), ".$this->unix_timestamp("reviewTimeStamp")." as reviewTimeStamp, prarm.reviewPoints FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;"); 
		$this->getCalibrationReviewsAfterDateQuery = $this->db->prepare("SELECT DISTINCT(pram.matchID), ".$this->unix_timestamp("reviewTimeStamp")." as reviewTimeStamp, prarm.reviewPoints FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND prara.reviewTimestamp > ".$this->from_unixtime("?")." AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;");
		
		$this->numCalibrationReviewsQuery = $this->db->prepare("SELECT COUNT(DISTINCT pram.matchID) FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;");
       	$this->numCalibrationReviewsAfterDateQuery = $this->db->prepare("SELECT COUNT(DISTINCT pram.matchID) FROM peer_review_assignment_matches pram, peer_review_assignment_review_answers prara, peer_review_assignment_review_marks prarm WHERE pram.calibrationState = 'attempt' AND pram.reviewerID = ? AND prara.reviewTimestamp > ".$this->from_unixtime("?")." AND pram.matchID = prara.matchID AND pram.matchID = prarm.matchID ORDER BY prara.reviewTimeStamp DESC;");
       	
       	$this->latestAssignmentWithFlaggedIndependentsQuery = $this->db->prepare("SELECT peer_review_assignment.assignmentID FROM peer_review_assignment, peer_review_assignment_independent WHERE peer_review_assignment.assignmentID = peer_review_assignment_independent.assignmentID ORDER BY peer_review_assignment.calibrationStopDate DESC LIMIT 10");
        
		$this->getMarkingLoadQuery = $this->db->prepare("SELECT markingLoad FROM users WHERE userID=?");
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
			$sh = $this->db->prepare("UPDATE users SET username = ?, firstName = ?, lastName = ?, studentID = ?, userType = ?, markingLoad = ?, dropped = 0 WHERE userID = ?;");
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
    
	function getActiveUserDisplayMap()
	{
		$sh = $this->prepareQuery("getActiveUserDisplayMapQuery", "SELECT userID, firstName, lastName FROM users WHERE dropped = 0 AND courseID=? ORDER BY lastName, firstName;");
		$sh->execute(array($this->courseID));
        $activeUsers = array();
        while($res = $sh->fetch())
        {
            $activeUsers[$res->userID] = $res->firstName." ".$res->lastName;
        }
        return $activeUsers;
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

	function getActiveStudents()
    {
        $sh = $this->prepareQuery("getActiveUsersQuery", "SELECT userID FROM users WHERE userType='student' AND dropped=0 AND courseID=?;");
		$sh->execute(array($this->courseID));
        return array_map(function($x) { return new UserID($x->userID); }, $sh->fetchAll());
    }

	function getDroppedStudents()
    {
        $sh = $this->prepareQuery("getActiveUsersQuery", "SELECT userID FROM users WHERE userType='student' AND dropped=1 AND courseID=?;");
		$sh->execute(array($this->courseID));
        return array_map(function($x) { return $x->userID; }, $sh->fetchAll());
    }

    function getInstructors()
    {
        $sh = $this->prepareQuery("getInstructorsQuery", "SELECT userID FROM users WHERE userType='instructor' AND courseID=?;");
        $sh->execute(array($this->courseID));
        $instructors = array();
        while($res = $sh->fetch())
            $instructors[] = $res->userID;
        return $instructors;
    }

    function getMarkers()
    {
        $sh = $this->prepareQuery("getMarkersQuery", "SELECT userID FROM users WHERE (userType='instructor' OR userType='marker') AND courseID=?;");
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
        $sh = $this->db->prepare("SELECT assignmentID FROM assignments WHERE courseID = ? AND displayPriority = ?;");
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
        $sh = $this->db->prepare("SELECT assignmentID FROM assignments WHERE courseID = ? AND displayPriority = ?;");
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
	
	function getInstructedAssignmentHeaders(UserID $instructorID)
    {
    	$username = $this->getUserName($instructorID);
        $this->getInstructedAssignmentHeadersQuery->execute(array($username));
        $headers = array();
        while($res = $this->getInstructedAssignmentHeadersQuery->fetch())
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
	
	//copied from peerreview/inc/calibrationutils.php to accomodate cron job computindependentsfromscalibrations.php
	function getWeightedAverage(UserID $userid, Assignment $assignment=NULL)
	{	
		$scores = $this->getCalibrationScores($userid);
		
		require_once("peerreview/inc/calibrationutils.php");
		
		if($scores)
			$average = computeWeightedAverage($scores);
		else 
			$average = "--";
	
		if($assignment!=NULL)
			$average = convertTo10pointScale($average, $assignment);
	
		return $average;
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
	
	function setMarkingLoad(UserID $markerID, $load)
	{
		//$sh = $this->prepareQuery("assertUserQuery", "SELECT userID FROM users WHERE userID = ?");
		$sh = $this->prepareQuery("setMarkingLoadQuery", "UPDATE users SET markingLoad = ? WHERE userID = ?");
		$sh->execute(array($load, $markerID->id));
	}
	
	function demote(UserID $userID, $demotionThreshold)
	{
		global $NOW;
		$array = array("userID"=>$userID, "demotionDate"=>$NOW, "demotionThreshold"=>$demotionThreshold);
		switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->prepareQuery("demoteQuery", "INSERT INTO peer_review_assignment_demotion_log (userID, demotionDate, demotionThreshold) VALUES (:userID, FROM_UNIXTIME(:demotionDate), :demotionThreshold) ON DUPLICATE KEY UPDATE demotionDate=FROM_UNIXTIME(:demotionDate), demotionThreshold=:demotionThreshold;");
				$sh->execute($array);
       			break;
			case 'sqlite':
       			$sh = $this->prepareQuery("demoteQuery", "INSERT OR IGNORE INTO peer_review_assignment_demotion_log (userID, demotionDate, demotionThreshold) VALUES (:userID, datetime(:demotionDate,'unixepoch'), :demotionThreshold);");
				$sh->execute($array);
				$sh = $this->prepareQuery("demoteQuery2", "UPDATE peer_review_assignment_demotion_log SET demotionDate=datetime(:demotionDate,'unixepoch'), demotionThreshold=:demotionThreshold WHERE userID = :userID;");
				$sh->execute($array);
       			break;
		}
	}
	
	function getDemotionEntry(UserID $userID)
	{
		$sh = $this->prepareQuery("getDemotionEntryQuery", "SELECT ".$this->unix_timestamp("demotionDate")." as demotionDate, demotionThreshold FROM peer_review_assignment_demotion_log WHERE userID = ?;");
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
	
	function saveCourseConfiguration(CourseConfiguration $configuration) 
	{
		$array = array(
		"courseID"=>$this->courseID, 
		"windowSize"=>$configuration->windowSize, 
		"numReviews"=>$configuration->numReviews, 
		"scoreNoise"=>$configuration->scoreNoise, 
		"maxAttempts"=>$configuration->maxAttempts, 
		"numCovertCalibrations"=>$configuration->numCovertCalibrations, 
		"exhaustedCondition"=>$configuration->exhaustedCondition,
		"minReviews"=>$configuration->minReviews, 
		"spotCheckProb"=>$configuration->spotCheckProb, 
		"highMarkThreshold"=>$configuration->highMarkThreshold, 
		"highMarkBias"=>$configuration->highMarkBias, 
		"calibrationThreshold"=>$configuration->calibrationThreshold, 
		"calibrationBias"=>$configuration->calibrationBias,
		"scoreWindowSize"=>$configuration->scoreWindowSize,
		"scoreThreshold"=>$configuration->scoreThreshold,
		"disqualifyWindowSize"=>$configuration->disqualifyWindowSize,
		"disqualifyThreshold"=>$configuration->disqualifyThreshold
		);
		switch($this->db->getAttribute(PDO::ATTR_DRIVER_NAME)){
        	case 'mysql':
       			$sh = $this->prepareQuery("saveCourseConfigurationQuery", "INSERT INTO course_configuration (courseID, windowSize, numReviews, scoreNoise, maxAttempts, numCovertCalibrations, exhaustedCondition, minReviews, spotCheckProb, highMarkThreshold, highMarkBias, calibrationThreshold, calibrationBias, scoreWindowSize, scoreThreshold, disqualifyWindowSize, disqualifyThreshold) 
																			VALUES (:courseID, :windowSize, :numReviews, :scoreNoise, :maxAttempts, :numCovertCalibrations, :exhaustedCondition, :minReviews, :spotCheckProb, :highMarkThreshold, :highMarkBias, :calibrationThreshold, :calibrationBias, :scoreWindowSize, :scoreThreshold, :disqualifyWindowSize, :disqualifyThreshold) 
																		   ON DUPLICATE KEY UPDATE windowSize=:windowSize , numReviews=:numReviews , scoreNoise=:scoreNoise , maxAttempts=:maxAttempts , numCovertCalibrations=:numCovertCalibrations , exhaustedCondition=:exhaustedCondition, minReviews=:minReviews, spotCheckProb=:spotCheckProb, highMarkThreshold=:highMarkThreshold, highMarkBias=:highMarkBias, calibrationThreshold=:calibrationThreshold, calibrationBias=:calibrationBias, scoreWindowSize=:scoreWindowSize, scoreThreshold=:scoreThreshold, disqualifyWindowSize=:disqualifyWindowSize, disqualifyThreshold=:disqualifyThreshold;");
				$sh->execute($array);
				break;
			case 'sqlite':
       			$sh = $this->prepareQuery("saveCourseConfigurationQuery", "INSERT OR IGNORE INTO course_configuration (courseID, windowSize, numReviews, scoreNoise, maxAttempts, numCovertCalibrations, exhaustedCondition, minReviews, spotCheckProb, highMarkThreshold, highMarkBias, calibrationThreshold, calibrationBias, scoreWindowSize, scoreThreshold, disqualifyWindowSize, disqualifyThreshold) VALUES (:courseID, :windowSize, :numReviews, :scoreNoise, :maxAttempts, :numCovertCalibrations, :exhaustedCondition, :minReviews, :spotCheckProb, :highMarkThreshold, :highMarkBias, :calibrationThreshold, :calibrationBias, :scoreWindowSize, :scoreThreshold, :disqualifyWindowSize, :disqualifyThreshold);");
				$result = $sh->execute($array); 																		
				$sh = $this->prepareQuery("saveCourseConfigurationQuery2", "UPDATE course_configuration SET windowSize=:windowSize, numReviews=:numReviews , scoreNoise=:scoreNoise , maxAttempts=:maxAttempts , numCovertCalibrations=:numCovertCalibrations , exhaustedCondition=:exhaustedCondition, minReviews=:minReviews, spotCheckProb=:spotCheckProb, highMarkThreshold=:highMarkThreshold, highMarkBias=:highMarkBias, calibrationThreshold=:calibrationThreshold, calibrationBias=:calibrationBias, scoreWindowSize=:scoreWindowSize, scoreThreshold=:scoreThreshold, disqualifyWindowSize=:disqualifyWindowSize, disqualifyThreshold=:disqualifyThreshold WHERE courseID=:courseID;");       																		
       			$sh->execute($array); 
       			break;
		}
	}
	
	function getCourseConfiguration(AssignmentID $assignmentID=NULL) 
	{
		if($assignmentID)
		{
			$sh = $this->prepareQuery("getCourseConfigurationQuery", "SELECT courseID, windowSize, numReviews, scoreNoise, maxAttempts, numCovertCalibrations, exhaustedCondition, minReviews, spotCheckProb, highMarkThreshold, highMarkBias, calibrationThreshold, calibrationBias, scoreWindowSize, scoreThreshold, disqualifyWindowSize, disqualifyThreshold from course_configuration WHERE courseID = (SELECT courseID FROM assignments WHERE assignmentID = ?);");
			$sh->execute(array($assignmentID));
		}
		else
		{
			$sh = $this->prepareQuery("getCourseConfigurationQuery", "SELECT courseID, windowSize, numReviews, scoreNoise, maxAttempts, numCovertCalibrations, exhaustedCondition, minReviews, spotCheckProb, highMarkThreshold, highMarkBias, calibrationThreshold, calibrationBias, scoreWindowSize, scoreThreshold, disqualifyWindowSize, disqualifyThreshold from course_configuration WHERE courseID = ?;");
			$sh->execute(array($this->courseID));
		}
		$configuration = new CourseConfiguration();
		$res = $sh->fetch();
		if(!$res)
			throw new Exception('The course configuration has not been set for this course');

		$configuration->windowSize = $res->windowSize;
		$configuration->numReviews = $res->numReviews;
		$configuration->scoreNoise = $res->scoreNoise;
		$configuration->maxAttempts = $res->maxAttempts;
		$configuration->numCovertCalibrations = $res->numCovertCalibrations;
		$configuration->exhaustedCondition = $res->exhaustedCondition;
		
		$configuration->minReviews = $res->minReviews;
		$configuration->spotCheckProb = $res->spotCheckProb;
		$configuration->highMarkThreshold = $res->highMarkThreshold;
		$configuration->highMarkBias = $res->highMarkBias;
		$configuration->calibrationThreshold = $res->calibrationThreshold;
		$configuration->calibrationBias = $res->calibrationBias;
		
		$configuration->scoreWindowSize = $res->scoreWindowSize;
		$configuration->scoreThreshold = $res->scoreThreshold;
		
		$configuration->disqualifyWindowSize = $res->disqualifyWindowSize;
		$configuration->disqualifyThreshold = $res->disqualifyThreshold;
		
		return $configuration;
	}

	function getCoursesInstructedByUser(UserID $instructorID)
	{
		$username = $this->getUserName($instructorID);
		$sh = $this->prepareQuery("getCoursesInstructedByUser", "SELECT name, displayName, courseID, browsable FROM course where courseID IN (SELECT courseID FROM users WHERE username = ?);");
		$sh->execute(array($username));
		return $sh->fetchall();
	}

	/*
	 * Global Data Manager stuff for cronjobs
	 * 						    			*/
	
	function getReviewStoppedAssignments()
	{
		global $NOW; global $GRACETIME;
		$sh = $this->prepareQuery("getReviewStoppedAssignmentsQuery", "SELECT assignmentID FROM peer_review_assignment WHERE ".$this->add_seconds(reviewStopDate, $GRACETIME)." > ".$this->from_unixtime("?")." AND ".$this->add_seconds(reviewStopDate, $GRACETIME)." < ".$this->from_unixtime("?").";");
        $sh->execute(array($NOW - (20*60), $NOW));
        $assignments = array();
        while($res = $sh->fetch())
        {
            $assignments[] = new AssignmentID($res->assignmentID);
        }
        return $assignments;
	}
	
	function getSubmissionStoppedAssignments()
	{
		global $NOW; global $GRACETIME;
		$sh = $this->prepareQuery("getSubmissionStoppedAssignmentsQuery", "SELECT assignmentID FROM peer_review_assignment WHERE ".$this->add_seconds(submissionStopDate, $GRACETIME)." > ".$this->from_unixtime("?")." AND ".$this->add_seconds(submissionStopDate, $GRACETIME)." < ".$this->from_unixtime("?").";");
		//$sh = $this->prepareQuery("getSubmissionStoppedAssignmentsQuery", "SELECT assignmentID FROM peer_review_assignment WHERE submissionStopDate > ".$this->from_unixtime("?")." AND submissionStopDate < ".$this->from_unixtime("?").";");
        $sh->execute(array($NOW - (20*60), $NOW));
        $assignments = array();
        while($res = $sh->fetch())
        {
            $assignments[] = new AssignmentID($res->assignmentID);
        }
        return $assignments;
	}
	
	function getStudentsByAssignment(AssignmentID $assignmentID)
    {
        $sh = $this->prepareQuery("getStudentsByAssignmentQuery", "SELECT userID FROM users JOIN assignments ON assignments.courseID = users.courseID WHERE userType = 'student' AND assignmentID = ? ORDER BY lastName, firstName;");
        $sh->execute(array($assignmentID));
        $students = array();
        while($res = $sh->fetch())
            $students[] = new UserID($res->userID);
        return $students;
    }
    
    function getActiveStudentsByAssignment(AssignmentID $assignmentID)
    {
        $sh = $this->prepareQuery("getStudentsByAssignmentQuery", "SELECT userID FROM users JOIN assignments ON assignments.courseID = users.courseID WHERE userType = 'student' AND assignmentID = ? AND dropped = 0 ORDER BY lastName, firstName;");
        $sh->execute(array($assignmentID));
        $students = array();
        while($res = $sh->fetch())
            $students[] = new UserID($res->userID);
        return $students;
    }
	
	function getMarkersByAssignment(AssignmentID $assignmentID)
    {
        $sh = $this->prepareQuery("getMarkersByAssignmentQuery", "SELECT userID FROM users JOIN assignments ON assignments.courseID = users.courseID WHERE (userType='instructor' OR userType='marker') AND assignmentID=?;");
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
	
	function getAssignmentsBefore(AssignmentID $assignmentID, $maxAssignments = 4)
    {
        //Get all the assignments
        $sh = $this->prepareQuery("getAssignmentsBeforeQuery", "SELECT assignmentID, name, assignmentType, displayPriority FROM assignments WHERE courseID = (SELECT courseID FROM assignments WHERE assignmentID = ?) ORDER BY displayPriority DESC;");
		$sh->execute(array($assignmentID));
		$assignments = array();
		$foundCurrent = false;  
	   	while($res = $sh->fetch())
        {
        	if($foundCurrent AND $res->assignmentType == "peerreview") {
        		$blah = $this->getAssignment(new AssignmentID($res->assignmentID), "peerreview");
                $assignments[] = $blah;
            } else if ($res->assignmentID == $assignmentID->id) {
                $foundCurrent = true;
            }
        }

        //Sort the assignments based on their date
        //usort($assignments, function($a, $b) { return $a->reviewStopDate < $b->reviewStopDate; } );

        if($maxAssignments < 0)
            return $assignments;

        return array_splice($assignments, 0, $maxAssignments);
    }

	function getAssignmentHeadersByAssignment(AssignmentID $assignmentID)
    {
    	$sh = $this->prepareQuery("getAssignmentHeadersByAssignmentQuery", "SELECT assignmentID, name, assignmentType, displayPriority FROM assignments WHERE courseID = (SELECT courseID FROM assignments WHERE assignmentID = ?) ORDER BY displayPriority DESC;");
        $sh->execute(array($assignmentID->id));
        $headers = array();
        while($res = $sh->fetch())
        {
            $headers[] = new AssignmentHeader(new AssignmentID($res->assignmentID), $res->name, $res->assignmentType, $res->displayPriority);
        }
        return $headers;
    }
	
	function isJobDone(AssignmentID $assignmentID, $job) 
	{
		$sh = $this->prepareQuery("independentsCopiedQuery", "SELECT notificationID FROM job_notifications WHERE success = 1 AND assignmentID = ? AND job = ?;");
		$sh->execute(array($assignmentID, $job));
		$res = $sh->fetch();
		return $res != NULL;
	}
	
	function createNotification(AssignmentID $assignmentID, $job, $success, $summary, $details)
	{
		global $NOW;
		$array = array("assignmentID"=>$assignmentID,
					  "job"=>$job, 
					  "dateRan"=>$NOW,
				      "success"=>$success,
					  "summary"=>$summary,
					  "details"=>$details);
		$sh = $this->prepareQuery("createNotificationQuery", "INSERT INTO job_notifications (courseID, assignmentID, job, dateRan, success, summary, details) VALUES ((SELECT courseID FROM assignments WHERE assignmentID = :assignmentID), :assignmentID, :job, ".$this->from_unixtime(":dateRan").", :success, :summary, :details);");
		$sh->execute($array);
	}
	
	function getNewNotifications()
	{
		$sh = $this->prepareQuery("getNewNotificationsQuery", "SELECT notificationID, assignmentID, job, ".$this->unix_timestamp("dateRan")." as dateRan, success, seen, summary FROM job_notifications WHERE courseID = ? AND seen = 0 ORDER BY dateRan DESC;");
		$sh->execute(array($this->courseID));
		$notifications = array();
		while($res = $sh->fetch())
        {
        	$notification = new stdClass();
			$notification->notificationID = $res->notificationID;
        	$notification->assignmentID = new AssignmentID($res->assignmentID);
			$notification->job = $res->job;
			$notification->dateRan = $res->dateRan;
			$notification->success = $res->success;
			$notification->seen = $res->seen;
			$notification->summary = $res->summary;
            $notifications[] = $notification;
        }
        return $notifications;
	}
	
	function getAllNotifications()
	{
		$sh = $this->prepareQuery("getAllNotificationsQuery", "SELECT notificationID, assignmentID, job, ".$this->unix_timestamp("dateRan")." as dateRan, success, seen, summary FROM job_notifications WHERE courseID = ? ORDER BY dateRan DESC;");
		$sh->execute(array($this->courseID));
		$notifications = array();
		while($res = $sh->fetch())
        {
        	$notification = new stdClass();
			$notification->notificationID = $res->notificationID;
        	$notification->assignmentID = new AssignmentID($res->assignmentID);
			$notification->job = $res->job;
			$notification->dateRan = $res->dateRan;
			$notification->success = $res->success;
			$notification->seen = $res->seen;
			$notification->summary = $res->summary;
            $notifications[] = $notification;
        }
        return $notifications;
	}
	
	function getNotification(/*NotificationID*/ $notificationID)
	{
		$sh = $this->prepareQuery("getNotificationQuery", "SELECT assignmentID, job, ".$this->unix_timestamp("dateRan")." as dateRan, success, seen, summary, details FROM job_notifications WHERE notificationID = ?;");
		$sh->execute(array($notificationID));
		if(!$res = $sh->fetch())
        {
            throw new Exception("Invalid notification id '$notificationID'");
        }
    	$notification = new stdClass();
    	$notification->assignmentID = new AssignmentID($res->assignmentID);
		$notification->job = $res->job;
		$notification->dateRan = $res->dateRan;
		$notification->success = $res->success;
		$notification->seen = $res->seen;
		$notification->summary = $res->summary;
		$notification->details = $res->details;
        $notifications[] = $notification;
        return $notification;
	}
	
	function dismissNotification(/*NotificationID*/ $notificationID)
	{
		//$sh = $this->prepareQuery("assertNotificationQuery", "SELECT * FROM job_notifications WHERE notification = ?;");
		
		$sh = $this->prepareQuery("dismissNotificationQuery", "UPDATE job_notifications SET seen = 1 WHERE notificationID = ?;");
		$sh->execute(array($notificationID));
	}
	
	function renewNotification(/*NotificationID*/ $notificationID)
	{
		//$sh = $this->prepareQuery("assertNotificationQuery", "SELECT * FROM job_notifications WHERE notification = ?;");
		
		$sh = $this->prepareQuery("dismissNotificationQuery", "UPDATE job_notifications SET seen = 0 WHERE notificationID = ?;");
		$sh->execute(array($notificationID));
	}
	
	function getCalibrationReviewer(SubmissionID $submissionID)
	{
		$sh = $this->prepareQuery("isCalibratedSubmissionQuery", "SELECT matches.reviewerID FROM peer_review_assignment_submissions submissions JOIN peer_review_assignment_matches matches ON submissions.submissionID = matches.submissionID WHERE submissions.submissionID = ? AND matches.calibrationState = 'key';");
		$sh->execute(array($submissionID));
		return $sh->fetch()->reviewerID;
	}
	
	//Just for re-assigning old unanswered appeals from previous appeal assignment  
	function assignOldUnansweredAppeals()
	{
		$sh = $this->prepareQuery("assignOldUnansweredAppealsQuery", "SELECT submissions.submissionID, submissions.assignmentID
		FROM peer_review_assignment_appeal_messages messages 
		LEFT OUTER JOIN peer_review_assignment_appeal_messages messages2 ON messages.appealMessageID < messages2.appealMessageID AND messages.matchID = messages2.matchID AND messages.appealType = messages2.appealType 
		JOIN peer_review_assignment_matches matches ON matches.matchID = messages.matchID JOIN peer_review_assignment_submissions submissions ON submissions.submissionID = matches.submissionID 
		JOIN users ON messages.authorID = users.userID 
		JOIN assignments ON assignments.assignmentID = submissions.assignmentID
		WHERE messages2.appealMessageID IS NULL AND users.userType = 'student' AND submissions.submissionID NOT IN (SELECT submissionID FROM appeal_assignment) AND assignments.courseID = ?
		ORDER BY submissions.assignmentID;");
		$sh->execute(array($this->courseID));
		$unansweredappeals = array();
		while($res = $sh->fetch())
		{
			if(!array_key_exists($res->assignmentID, $unansweredappeals))
				$unansweredappeals[$res->assignmentID] = array(); 
			$unansweredappeals[$res->assignmentID][$res->submissionID] = new SubmissionID($res->submissionID);
		}
		return $unansweredappeals;
	}
	
	//Just for re-assigning old unanswered appeals from previous appeal assignment 
	function assignAppeal(SubmissionID $submissionID, UserID $markerID)
	{
		$sh = $this->prepareQuery("assignAppealQuery", "INSERT INTO appeal_assignment (markerID, submissionID) VALUES (?, ?);");
		$sh->execute(array($markerID, $submissionID));
	}
	
	function dropUser(UserID $studentID) 
	{
		$sh = $this->prepareQuery("dropUserQuery", "UPDATE users SET dropped = 1 WHERE userID = ?;");
		$sh->execute(array($studentID));
	}
}
