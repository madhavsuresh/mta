<?php
require_once("default_values.php");
require_once("../inc/common.php");
require_once("fill_and_decode_json.php");
    function createAssignment($assignment_params){
       global $dataMgr; 
        $assignmentType = $assignment_params['assignmentType'];
        $submission_type = $assignment_params['submissionType'];
        $submissionSettingsType = $submission_type ."SubmissionSettings";
        $new_assignment = $dataMgr->createAssignmentInstance(null, $assignmentType);
        
        foreach ($assignment_params as $key => $value){
            if ($key == "submissionSettings"){
                $new_assignment->submissionSettings = new $submissionSettingsType(); 
                foreach($value as $setting => $sub_value){
                   $new_assignment->submissionSettings->$setting = $sub_value; 
                }         
            }
            
            

            else{    
            $new_assignment->$key = $value;
            
            }
        }
        
        
        $dataMgr->saveAssignment($new_assignment, $assignmentType);
        return $new_assignment;
    }

?>
