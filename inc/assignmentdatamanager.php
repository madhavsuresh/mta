<?php

abstract class AssignmentDataManager
{
    public $assignmentType;
    protected $dataMgr;

    function __construct($type, DataManager $dataMgr)
    {
        $this->assignmentType = $type;
        $this->dataMgr = $dataMgr;
    }

    /**
     * Virtual function responsible for loading all the data of an assignment
     */
    abstract function loadAssignment(AssignmentID $id);

    /**
     * Virtual function responsible for saving all the data of an assignment
     */
    abstract function saveAssignment(Assignment $assignment, $newAssignment);

};

