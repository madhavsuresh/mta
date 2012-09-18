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

        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

        $this->isUserQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? && userType IN ('instructor', 'student');");
        $this->isStudentQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? && userType = 'student';");
        $this->isUserByNameQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && username=? && userType IN ('instructor', 'student');");
        $this->userIDQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && username=? ;");
        $this->isInstructorQuery = $this->db->prepare("SELECT userID FROM users WHERE userID=? && (userType IN ('instructor', 'shadowinstructor'));");
        $this->getAssignmentHeadersQuery = $this->db->prepare("SELECT assignmentID, name, assignmentType, displayPriority FROM assignments WHERE courseID = ? ORDER BY displayPriority DESC;");
        $this->getAssignmentHeaderQuery = $this->db->prepare("SELECT name, assignmentType, displayPriority FROM assignments WHERE assignmentID = ?;");
        $this->getUsernameQuery = $this->db->prepare("SELECT username FROM users WHERE userID=?;");
        $this->getUsersQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? ORDER BY lastName;");
        $this->getStudentsQuery = $this->db->prepare("SELECT userID FROM users WHERE courseID=? && userType = 'student' ORDER BY lastName;");
        $this->getUserDisplayMapQuery = $this->db->prepare("SELECT userID, firstName, lastName FROM users WHERE courseID=? ORDER BY lastName;");
        $this->getUserDisplayNameQuery = $this->db->prepare("SELECT firstName, lastName FROM users WHERE userID=?;");
        $this->numUserTypeQuery = $this->db->prepare("SELECT COUNT(userID) FROM users WHERE courseID=? && userType=?;");
        $this->assignmentExistsQuery = $this->db->prepare("SELECT assignmentID FROM assignments WHERE assignmentID=?;");


        $this->addAssignmentToCourseQuery = $this->db->prepare( "INSERT INTO assignments (courseID, name, displayPriority, assignmentType) SELECT :courseID, :name, COUNT(courseID), :type FROM assignments WHERE courseID=:courseID;",
                                                                array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $this->removeAssignmentFromCourseQuery = $this->db->prepare("DELETE FROM assignments WHERE assignmentID = ?;");
        $this->updateAssignmentQuery = $this->db->prepare("UPDATE assignments SET name=? WHERE assignmentID = ?;");
        //$this->getConfigPropertyQuery = $this->db->prepare("SELECT *;");
        //$this->assignmentSwapDisplayOrderQuery = $this->db->prepare("UPDATE assignments SET


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
        $sh = $this->db->prepare("SELECT name, displayName, authType, registrationType FROM course WHERE courseId = ?;");
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

    function addUser($username, $firstName, $lastName, $studentID, $type='student')
    {
        $sh = $this->db->prepare("INSERT INTO users (courseID, username, firstName, lastName, studentID, userType) VALUES (?, ?, ?, ?, ?, ?);");
        $sh->execute(array($this->courseID, $username, $firstName, $lastName, $studentID, $type));
        return new UserID($this->db->lastInsertID());
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
        $this->isUserQuery->execute(array($userid));
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
    function isInstructor(UserID $userid)
    {
        $this->isInstructorQuery->execute(array($userid));
        return $this->isInstructorQuery->fetch() != NULL;
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
        $sh = $this->prepareQuery("getInstructorsQuery", "SELECT userID FROM users WHERE userType='instructor' && course=?;");
        $sh->execute($this->courseID);
        $instructors = array();
        while($res = $sh->fetch())
            $instructors[] = $res->userID;
        return $instructors;
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
        $this->updateAssignmentQuery->execute(array($assignment->name, $assignment->assignmentID));
    }
}
?>
