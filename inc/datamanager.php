<?php

abstract class DataManager
{
    private $assignmentDataManagers = array();
    public $courseID = NULL;
    public $courseName = NULL;
    public $courseDisplayName = NULL;
    protected $authMgrType;
    protected $registrationType;
    private $assignmentNameTypeMap = array();

    function __construct()
    {
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
    }

    function requireCourse()
    {
        if(!$this->courseID)
            throw new Exception("No Course was specified");
    }

    function createAuthManager()
    {
        if($this->courseID)
        {
            //Figure out what kind of auth manager we are using
            require_once(MTA_ROOTPATH."inc/authmanagers/".$this->authMgrType."authmanager.php");
            $authMgrType = $this->authMgrType."AuthManager";
            return new $authMgrType($this->registrationType, $this);
        }
        return new DummyAuthManager(null, $this);
    }

    function getAssignmentTypeToNameMap()
    {
        return $this->assignmentNameTypeMap;
    }

    /** Creates a new assignment of the given type with the appropriate data manager
     * Optional argument "type", allows you to speed it up a bit
     */
    function getAssignment(AssignmentID $assignmentID)
    {
        if(func_num_args() == 1)
        {
            $type = $this->getAssignmentHeader($assignmentID)->assignmentType;
        }
        else if (func_num_args() == 2)
        {
            $type = func_get_arg(1);
            //TODO Make sure that we actually have this assignment in the course
        }
        if(!array_key_exists($type, $this->assignmentDataManagers))
            throw new Exception("Crazy assignment type '$type' is unknown");

        #Get the data manager for this type of assignment
        require_once(MTA_ROOTPATH .$type."/inc/".$type."assignment.php");
        $assignmentType = $type . "Assignment";
        $assignment = $this->assignmentDataManagers[$type]->loadAssignment($assignmentID);
        $this->populateGeneralAssignmentFields($assignment);

        return $assignment;
    }

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

    /** Virtual function that loads all the headers for assignments (name, type) */
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

};

