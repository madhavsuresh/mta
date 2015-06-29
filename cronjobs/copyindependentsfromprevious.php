<?php
require_once("peerreview/inc/common.php");

class CopyIndependentsFromPreviousCronJob
{
	function executeAndGetResult(AssignmentID $assignmentID, PDODataManager $globalDataMgr)
    {
    	try{
	    	//First check if the job has already been done
	    	if($globalDataMgr->isJobDone($assignmentID, 'copyindependentsfromprevious'))
				return;
			
			//Get all the assignments
			$assignmentHeaders = $globalDataMgr->getAssignmentHeadersByAssignment($assignmentID);
			
			$currentAssignment = $globalDataMgr->getAssignment($assignmentID);
			$assignments = $globalDataMgr->getAssignmentsBefore($assignmentID, 1);
			
			if(sizeof($assignments) != 1){				
			    //throw new Exception("Could not find exactly one previous assignment!");
			    $globalDataMgr->createNotification($assignmentID, 'copyindependentsfromprevious', 1, "Not done. Could not find exactly one previous assignment!", "");
				return;
			}
			
			$userNameMap = $globalDataMgr->getUserDisplayMapByAssignment($assignmentID);
			$students = $globalDataMgr->getActiveStudentsByAssignment($assignmentID);
			$currentIndependents = $currentAssignment->getIndependentUsers();
			$previousIndependents = $assignments[0]->getIndependentUsers();
			$independents = array();
			
			$mode = "copy";//hard-coded in 
			
			$html = "";
			$html .= "<table width='100%'>\n";
			$html .= "<tr><td><h2>Student</h2></td><td><h2>Status</h2></td></tr>\n";
			$currentRowType = 0;
			$addedIndependents = 0;
			$removedIndependents = 0;
			foreach($students as $student)
			{
			    $html .= "<tr class='rowType$currentRowType'><td>".$userNameMap[$student->id]."</td><td>";
			
			    $html .= "</td><td>\n";
				
			    $inCurrent = array_key_exists($student->id, $currentIndependents);
			    $inPrevious = array_key_exists($student->id, $previousIndependents);
			    if($mode == "copy"){
			        if($inCurrent != $inPrevious){
			            if($inPrevious){
			                $html .= "Added to independents";
			                $addedIndependents++;
			                $independents[$student->id] = $student;
			            }else{
			                $html .= "Removed from independents";
							$removedIndependents++;
			            }
			        }else if($inPrevious){
			            $independents[$student->id] = $student;
			        }
			    }else if ($mode == "copyIndependents") {
			        if($inPrevious){
			            $independents[$student->id] = $student;
			            if(!$inCurrent){
			                $html .= "Added to independents";
							$addedIndependents++;
			            }
			        }else if($inCurrent){
			            $independents[$student->id] = $student;
			        }
			    } else { //$mode == "copySupervised"
			        if($inPrevious){
			            if($inCurrent){
			                $independents[$student->id] = $student;
			            }
			        }else{
			            if($inCurrent){
			                $html .= "Removed from independents";
							$removedIndependents++;
			            }
			        }
			    }
			
			    $html .= "</td></tr>\n";
			    $currentRowType = ($currentRowType+1)%2;
			}
			$html .= "</table>\n";
			
			$currentAssignment->saveIndependentUsers($independents);
			
			$summary = "Ran in $mode mode: $addedIndependents independents added and $removedIndependents independents removed";
	
			$globalDataMgr->createNotification($assignmentID, 'copyindependentsfromprevious', 1, $summary, $html);
		}catch(Exception $exception){
			$globalDataMgr->createNotification($assignmentID, 'copyindependentsfromprevious', 0, cleanString($exception->getMessage()), "");
		}
	}
}
?>