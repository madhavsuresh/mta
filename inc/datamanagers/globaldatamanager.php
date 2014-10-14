<?php

require_once("inc/common.php");

class GlobalPDODataManager extends PDODataManager
{
    function prepareQuery($name, $query)
    {
        if(!isset($this->$name)) {
            $this->$name = $this->db->prepare($query);
        }
        return $this->$name;
    }

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
		 
        //Now we can set up all the assignment data managers
        global $MTA_ASSIGNMENTS, $MTA_DATAMANAGER;
        #Go through all the assignments we have, and load up the datamanager if we can
        foreach($MTA_ASSIGNMENTS as $assignmentType)
        {
            //We need to get the data manager for this
            require_once(MTA_ROOTPATH.$assignmentType."/inc/".$assignmentType."assignment.php");
            require_once(MTA_ROOTPATH.$assignmentType."/inc/datamanagers/".$MTA_DATAMANAGER."assignmentdatamanager.php");
            $assignmentDataMgrType = $MTA_DATAMANAGER.$assignmentType."AssignmentDataManager";
            $this->assignmentDataManagers[$assignmentType] = new $assignmentDataMgrType($assignmentType, $this);
            //We also need to get the name for this type of assignment
            $assignmentClass = $assignmentType."Assignment";

            $tempAssignment = new $assignmentClass(NULL, NULL, $this->assignmentDataManagers[$assignmentType]);
            $this->assignmentNameTypeMap[$assignmentType] = $tempAssignment->getAssignmentTypeDisplayName();
		}
		
        $this->getAssignmentHeaderQuery = $this->db->prepare("SELECT name, assignmentType, displayPriority FROM assignments WHERE assignmentID = ?;");
		$this->getRecentPeerReviewAssignmentsQuery = $this->db->prepare("SELECT assignmentID FROM peer_review_assignment WHERE reviewStopDate > FROM_UNIXTIME(?) && reviewStopDate < FROM_UNIXTIME(?);");
		$this->assignmentFieldsQuery = $this->db->prepare("SELECT password, passwordMessage, visibleToStudents FROM assignments WHERE assignmentID=?;");
    }

	function getDatabase()
    {
        return $this->db;
    }

    /*function getAssignmentTypeToNameMap()
    {
        return $this->assignmentNameTypeMap;
    }

    // Creates a new assignment of the given type with the appropriate data manager
     // Optional argument "type", allows you to speed it up a bit
     //

    function getAssignmentDataManager($type)
    {
        return $this->assignmentDataManagers[$type];
    }

    function createAssignmentInstance($name, $type)
    {
        if(!array_key_exists($type, $this->assignmentDataManagers))
            die("Unknown assignment type '$type'");
        require_once(MTA_ROOTPATH .$type."/inc/".$type."assignment.php");
        $assignmentType = $type . "Assignment";
        return new $assignmentType(null, $name, $this->assignmentDataManagers[$type]);
    }

    // Virtual function that loads all the headers for assignments (name, type)
    abstract function numStudents();
    abstract function numInstructors();
    abstract function getAssignmentHeaders();
    abstract function getAssignmentHeader(AssignmentID $id);
    abstract function moveAssignmentUp(AssignmentID $id);
    abstract function moveAssignmentDown(AssignmentID $id);
    protected abstract function populateGeneralAssignmentFields(Assignment $assignment);
    protected abstract function removeAssignmentFromCourse(AssignmentID $id);
    protected abstract function addAssignmentToCourse($name, $type);
    protected abstract function updateAssignment(Assignment $assignment);
    abstract function assignmentExists(AssignmentID $id);
    abstract function getCourses();
    abstract function getCourseInfo(CourseID $id);
    abstract function setCourseInfo(CourseID $id, $name, $displayName, $authType, $regType, $browsable);
    abstract function createCourse($name, $displayName, $authType, $regType, $browsable);

    function getAssignments()
    {
        $assignments = array();
        foreach($this->getAssignmentHeaders() as $header)
        {
            $assignments[] = $this->getAssignment($header->assignmentID, $header->assignmentType);
        }
        return $assignments;
    }

    function deleteAssignment(AssignmentID $id)
    {
        $assignment = $this->getAssignment($id);
        $this->assignmentDataManagers[$assignment->assignmentType]->deleteAssignment($assignment);
        $this->removeAssignmentFromCourse($id);
    }

    function saveAssignment($assignment, $type)
    {
        if(!array_key_exists($type, $this->assignmentDataManagers))
            throw new Exception("Unknown assignment type '$type'");

        $added = false;

        if($assignment->assignmentID === NULL)
        {
            $assignment->assignmentID = $this->addAssignmentToCourse($assignment->name, $type);
            $added = true;
        }
        $this->updateAssignment($assignment);

        //We have to remove the assignment if anything else fails
        try
        {
            $this->assignmentDataManagers[$type]->saveAssignment($assignment, $added);
        }catch(Exception $e) {
            if($added)
                $this->removeAssignmentFromCourse($assignment->assignmentID);
            throw $e;
        }
    }

	function getAssignmentHeader(AssignmentID $id)
    {
        $this->getAssignmentHeaderQuery->execute(array($id));
        if(!$res = $this->getAssignmentHeaderQuery->fetch())
        {
            throw new Exception("No Assignment with id '$id' found");
        }
        return new AssignmentHeader($id, $res->name, $res->assignmentType, $res->displayPriority);
    }*/
    
    function getRecentPeerReviewAssignments()
	{
		global $NOW;
		$this->getRecentPeerReviewAssignmentsQuery->execute(array($NOW - (10*60), $NOW));
        $assignments = array();
        while($res = $this->getRecentPeerReviewAssignmentsQuery->fetch())
        {
            $assignments[] = new AssignmentID($res->assignmentID);
        }
        return $assignments;
	}
};

