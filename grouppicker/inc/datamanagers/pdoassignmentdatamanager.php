<?php

require_once(dirname(__FILE__)."/../../../inc/common.php");
require_once("grouppicker/inc/grouppickerassignment.php");

class PDOGroupPickerAssignmentDataManager extends AssignmentDataManager
{
    private $db;

    function __construct($type, PDODataManager $dataMgr)
    {
        parent::__construct($type, $dataMgr);

        $this->db = $dataMgr->getDatabase();
    }

    function loadAssignment(AssignmentID $assignmentID)
    {

        $sh = $this->prepareQuery("loadAssignmentHeaderQuery", "SELECT name, UNIX_TIMESTAMP(startDate) as startDate, UNIX_TIMESTAMP(stopDate) as stopDate FROM group_picker_assignment JOIN assignments ON assignments.assignmentID = group_picker_assignment.assignmentID WHERE assignments.assignmentID=?;");
        $sh->execute(array($assignmentID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get group picker assignment with id $assignmentID");

        $assignment = new GroupPickerAssignment($assignmentID, $res->name, $this);
        $assignment->startDate = $res->startDate;
        $assignment->stopDate = $res->stopDate;

        $sh = $this->prepareQuery("loadAssignmentGroupsQuery", "SELECT groupText FROM group_picker_assignment_groups WHERE assignmentID=? ORDER BY groupIndex");
        $sh->execute(array($assignmentID));
        $assignment->groups = array();
        while($res = $sh->fetch())
        {
            $assignment->groups[] = $res->groupText;
        }

        return $assignment;
    }

    function saveAssignment(Assignment $assignment, $newAssignment)
    {
        #First, we need to update the assignment table
        if($newAssignment)
        {
            $sh = $this->db->prepare("INSERT INTO group_picker_assignment (startDate, stopDate, assignmentID) VALUES (FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?);");
        }
        else
        {
            $sh = $this->db->prepare("UPDATE group_picker_assignment SET startDate=FROM_UNIXTIME(?), stopDate=FROM_UNIXTIME(?) WHERE assignmentid=?;");
        }
        $sh->execute(array(
            $assignment->startDate,
            $assignment->stopDate,
            $assignment->assignmentID
        ));

        //Clear out all the old choices
        $sh = $this->db->prepare("DELETE FROM group_picker_assignment_groups WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        //Now all of the choice indices have to go in there
        $sh = $this->db->prepare("INSERT INTO group_picker_assignment_groups (assignmentID, groupIndex, groupText) VALUES (?, ?, ?);");
        $i = 0;
        foreach($assignment->groups as $group)
        {
            $sh->execute(array($assignment->assignmentID, $i, $group));
            $i++;
        }
    }

    function getGroupIndex(GroupPickerAssignment $assignment, UserID $userID)
    {
        $sh = $this->prepareQuery("getGroupIndexQuery", "SELECT groupIndex FROM group_picker_assignment_selections WHERE assignmentID=? && userID=?;");
        $sh->execute(array($assignment->assignmentID, $userID));
        $res = $sh->fetch();
        if($res)
        {
            //Just return it
            return $res->groupIndex;
        }
        else
        {
            //We need to go and insert it
            $sh = $this->prepareQuery("getGroupIndexInsertQuery", "INSERT INTO group_picker_assignment_selections (assignmentID, userID, groupIndex) VALUES (?, ?, 0);");
            $sh->execute(array($assignment->assignmentID, $userID));
            //Next, we need to get the ID of our choice
            $id = $this->db->lastInsertID();

            //Now, we can compute our assignment
            $sh = $this->prepareQuery("getGroupIndexSelectQuery", "SELECT count(selectionID) as c FROM group_picker_assignment_selections WHERE assignmentID=? && selectionID < ?;");
            $sh->execute(array($assignment->assignmentID, $id));
            $count = $sh->fetch()->c;
            $groupIndex = ($count) % sizeof($assignment->groups);

            //Finally, update the bugger
            $sh = $this->prepareQuery("getGroupIndexUpdateQuery", "UPDATE group_picker_assignment_selections SET groupIndex=? WHERE selectionID=?;");
            $sh->execute(array($groupIndex, $id));

            return $groupIndex;
        }
    }

    function hasGroupIndex(GroupPickerAssignment $assignment, UserID $userID)
    {
        $sh = $this->prepareQuery("hasGroupAssignmentQuery", "SELECT selectionID FROM group_picker_assignment_selections WHERE assignmentID=? && userID=?;");
        $sh->execute(array($assignment->assignmentID, $userID));
        return $sh->fetch() != NULL;
    }


    function getGroups(GroupPickerAssignment $assignment)
    {
        $sh = $this->db->prepare("SELECT groups.userID, groupIndex FROM group_picker_assignment_selections groups JOIN users ON users.userID = groups.userID WHERE assignmentID=? ORDER BY lastName, firstName;");
        $sh->execute(array($assignment->assignmentID));

        $groups = array();
        while($res = $sh->fetch())
        {
            $groups[$res->userID] = $res->groupIndex;
        }
        return $groups;
    }

    function saveGroups(GroupPickerAssignment $assignment, $groupMap)
    {
        //Get the old group map
        global $dataMgr;

        $oldGroupMap = $this->getGroups($assignment);

        $deleteQuery = $this->db->prepare("DELETE FROM group_picker_assignment_selections WHERE assignmentID = ? && userID = ?;");
        $updateQuery = $this->db->prepare("UPDATE group_picker_assignment_selections SET groupIndex = ? WHERE assignmentID = ? && userID = ?;");
        $insertQuery = $this->db->prepare("INSERT INTO group_picker_assignment_selections (assignmentID, userID, groupIndex) VALUES (?, ?, ?);");
        foreach($dataMgr->getUserDisplayMap() as $userID => $_)
        {
            $inNew = isset($groupMap[$userID]);
            $inOld = isset($oldGroupMap[$userID]);

            if($inNew)
            {
                if($inOld) {
                    $updateQuery->execute(array($groupMap[$userID], $assignment->assignmentID, $userID));
                }else{
                    $insertQuery->execute(array($assignment->assignmentID, $userID, $groupMap[$userID]));
                }
            }else if($inOld)
            {
                //We need to nuke it
                $deleteQuery->execute(array($assignment->assignmentID, $userID));
            }
        }
    }

    function numAssigned($assignment)
    {
        $sh = $this->prepareQuery("numAssignedQuery", "SELECT COUNT(selectionID) as c FROM group_picker_assignment_selections WHERE assignmentID=?;");
        $sh->execute(array($assignment->assignmentID));
        return $sh->fetch()->c;
    }

    //Because PHP doesn't do multiple inheritance, we have to define this method all over the place
    private function prepareQuery($name, $query)
    {
        if(!isset($this->$name)) {
            $this->$name = $this->db->prepare($query);
        }
        return $this->$name;
    }
}


