<?php
    function createAssignment($json){
        global $dataMgr, $NOW;
        $assignment_params = json_decode($json);

        $db = $dataMgr->getDatabase();
        $assignmentType = $assignment_params->assignmentType;

        $new_assignment = $dataMgr->createAssignmentInstance(null, $assignmentType);

        $new_assignment->submissionQuestion = $assignment_params->submissionQuestion;
        $time = $dataMgr->from_unixtime($NOW);
        # for some reason I can't just put this value into the db. It will accept
        # the unix epoch offset. Need to loop through time and grab that value
        $unix_time = '';
        for ($i = 9; $i < 19; $i++){
            $unix_time .= $time[$i];
        }


        $late_unix_time = $unix_time;
        $late_unix_time[0] = "2"; # this puts the date ~32 years in the future so things won't be due



        $new_assignment->submissionStartDate = $assignment_params->submissionStartDate;
        $new_assignment->submissionStopDate =  $assignment_params->submissionStopDate;

        $new_assignment->reviewStartDate =  $assignment_params->reviewStartDate;
        $new_assignment->reviewStopDate =  $assignment_params->reviewStopDate;

        $new_assignment->markPostDate =  $assignment_params->markPostDate;

        $new_assignment->appealStopDate =  $assignment_params->appealStopDate;

        $new_assignment->maxSubmissionScore =  $assignment_params->maxSubmissionScore;
        $new_assignment->maxReviewScore =  $assignment_params->maxReviewScore;
        $new_assignment->defaultNumberOfReviews =  $assignment_params->defaultNumberOfReviews;
        $new_assignment->allowRequestOfReviews =  $assignment_params->alllowRequestOfReviews;
        $new_assignment->showMarksForReviewsReceived =  $assignment_params->showMarksForReviewsReceived;
        $new_assignment->showOtherReviewsByStudents =  $assignment_params->showOtherReviewsByStudents;
        $new_assignment->showOtherReviewsByInstructors =  $assignment_params->showOtherReviewsByInstructors;
        $new_assignment->showMarksForOtherReviews =  $assignment_params->showMarksForOtherReviews;
        $new_assignment->showMarksForReviewedSubmissions =  $assignment_params->showMarksForReviewedSubmissions;
        $new_assignment->showPoolStatus =  $assignment_params->showPoolStatus;

        $new_assignment->calibrationMinCount =  $assignment_params->calibrationMinCount;
        $new_assignment->calibrationMaxScore =  $assignment_params->calibrationMaxScore;
        $new_assignment->calibrationThresholdMSE =  $assignment_params->calibrationThresholdMSE;
        $new_assignment->calibrationThresholdScore =  $assignment_params->calibrationThresholdScore;
        $new_assignment->extraCalibrations =  $assignment_params->extraCalibrations;
        $new_assignment->calibrationStartDate =  $assignment_params->calibrationstartDate; #set these equal so calibration doesn't happen ??
        $new_assignment->calibrationStopDate =  $assignment_params->calibrationStopDate;

                #$new_assignment->assignmentID = 2;

        $new_assignment->submissionType =  $assignment_params->submissionType;
        $new_assignment->name =  $assignment_params->assignment_name;

        $submissionSettingsType = $new_assignment->submissionType . "SubmissionSettings";

        $new_assignment->autoAssignEssayTopic =  $assignment_params->autoAssignEssayTopic;

        $new_assignment->submissionSettings = new $submissionSettingsType();

        $new_assignment->submissionSettings->essayWordLimit = $assignment_params->essayWordLimit;
        $dataMgr->saveAssignment($new_assignment, $assignmentType);
        $assignmentID = $db->lastInsertId();
        return array($assignmentID, $new_assignment);
    }


?>
