PRAGMA synchronous = OFF;
PRAGMA journal_mode = MEMORY;
BEGIN TRANSACTION;
CREATE TABLE "appeal_assignment" (
  "submissionID" INTEGER PRIMARY KEY,
  "markerID" INTEGER NOT NULL,
  CONSTRAINT "appeal_assignment_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE,
  CONSTRAINT "appeal_assignment_ibfk_2" FOREIGN KEY ("markerID") REFERENCES "users" ("userID") ON DELETE CASCADE
);
CREATE TABLE "assignment_password_entered" (
  "userID" INTEGER NOT NULL,
  "assignmentID" INTEGER NOT NULL,
  PRIMARY KEY ("userID", "assignmentID"),
  CONSTRAINT "assignment_password_entered_ibfk_1" FOREIGN KEY ("userID") REFERENCES "users" ("userID") ON DELETE CASCADE,
  CONSTRAINT "assignment_password_entered_ibfk_2" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "assignments" (
  "assignmentID" INTEGER PRIMARY KEY,
  "name" varchar(128) NOT NULL,
  "courseID" INTEGER NOT NULL,
  "displayPriority" INTEGER NOT NULL,
  "assignmentType" varchar(64) NOT NULL,
  "passwordMessage" text,
  "password" varchar(255) DEFAULT NULL,
  "visibleToStudents" tinyint(1) NOT NULL DEFAULT '1',
  CONSTRAINT "assignments_ibfk_1" FOREIGN KEY ("courseID") REFERENCES "course" ("courseID") ON DELETE CASCADE
);
CREATE TABLE "course" (
  "courseID" INTEGER PRIMARY KEY,
  "name" varchar(64) NOT NULL,
  "displayName" varchar(128) NOT NULL,
  "authType" varchar(128) NOT NULL,
  "registrationType" varchar(128) NOT NULL,
  "browsable" tinyint(1) NOT NULL DEFAULT '1',
  "archived" tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE ("name")
);
CREATE TABLE "course_configuration" (
  "courseID" INTEGER PRIMARY KEY,
  "windowSize" INTEGER NOT NULL,
  "numReviews" INTEGER NOT NULL,
  "scoreNoise" float NOT NULL,
  "maxAttempts" INTEGER NOT NULL,
  "numCovertCalibrations" INTEGER NOT NULL,
  "exhaustedCondition" text  NOT NULL,
  "minReviews" INTEGER NOT NULL,
  "spotCheckProb" float NOT NULL,
  "highMarkThreshold" float NOT NULL,
  "highMarkBias" float NOT NULL,
  "calibrationThreshold" float NOT NULL,
  "calibrationBias" float NOT NULL,
  "scoreWindowSize" INTEGER NOT NULL,
  "scoreThreshold" float NOT NULL,
  "disqualifyWindowSize" INTEGER NOT NULL,
  "disqualifyThreshold" float NOT NULL,
  CONSTRAINT "course_configuration_ibfk_1" FOREIGN KEY ("courseID") REFERENCES "course" ("courseID") ON DELETE CASCADE
);
CREATE TABLE "group_picker_assignment" (
  "assignmentID" INTEGER PRIMARY KEY,
  "startDate" datetime NOT NULL,
  "stopDate" datetime NOT NULL,
  CONSTRAINT "group_picker_assignment_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "group_picker_assignment_groups" (
  "assignmentID" INTEGER NOT NULL,
  "groupIndex" INTEGER NOT NULL,
  "groupText" text NOT NULL,
  PRIMARY KEY ("assignmentID", "groupIndex"),
  CONSTRAINT "group_picker_assignment_groups_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "group_picker_assignment_selections" (
  "selectionID" INTEGER PRIMARY KEY,
  "assignmentID" INTEGER NOT NULL,
  "userID" INTEGER NOT NULL,
  "groupIndex" INTEGER NOT NULL,
  UNIQUE("assignmentID", "userID"),
  CONSTRAINT "group_picker_assignment_selections_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE,
  CONSTRAINT "group_picker_assignment_selections_ibfk_2" FOREIGN KEY ("userID") REFERENCES "users" ("userID") ON DELETE CASCADE
);
CREATE TABLE "job_notifications" (
  "notificationID" INTEGER PRIMARY KEY,
  "courseID" INTEGER NOT NULL,
  "assignmentID" INTEGER NOT NULL,
  "job" text  NOT NULL DEFAULT 'general',
  "dateRan" datetime NOT NULL,
  "success" tinyint(1) NOT NULL DEFAULT '0',
  "seen" tinyint(1) NOT NULL DEFAULT '0',
  "summary" text NOT NULL,
  "details" longtext NOT NULL
);
CREATE TABLE "peer_review_assignment" (
  "assignmentID" INTEGER PRIMARY KEY,
  "submissionQuestion" longtext NOT NULL,
  "submissionType" varchar(64) NOT NULL,
  "submissionStartDate" datetime NOT NULL,
  "submissionStopDate" datetime NOT NULL,
  "reviewStartDate" datetime NOT NULL,
  "reviewStopDate" datetime NOT NULL,
  "markPostDate" datetime NOT NULL,
  "maxSubmissionScore" float NOT NULL,
  "maxReviewScore" float NOT NULL,
  "defaultNumberOfReviews" INTEGER NOT NULL,
  "allowRequestOfReviews" tinyint(1) NOT NULL,
  "showMarksForReviewsReceived" tinyint(1) NOT NULL,
  "showOtherReviewsByStudents" tinyint(1) NOT NULL,
  "showOtherReviewsByInstructors" tinyint(1) NOT NULL,
  "showMarksForOtherReviews" tinyint(1) NOT NULL,
  "showMarksForReviewedSubmissions" tinyint(1) NOT NULL,
  "appealStopDate" datetime NOT NULL,
  "showPoolStatus" tinyint(1) NOT NULL,
  "calibrationMinCount" INTEGER NOT NULL,
  "calibrationMaxScore" INTEGER NOT NULL,
  "calibrationThresholdMSE" float NOT NULL,
  "calibrationThresholdScore" float NOT NULL,
  "autoAssignEssayTopic" tinyint(1) NOT NULL DEFAULT '0',
  "extraCalibrations" INTEGER DEFAULT NULL,
  "essayWordLimit" INTEGER NOT NULL DEFAULT '0',
  "calibrationStartDate" datetime NOT NULL,
  "calibrationStopDate" datetime NOT NULL,
  CONSTRAINT "peer_review_assignment_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_appeal_messages" (
  "appealMessageID" INTEGER PRIMARY KEY,
  "appealType" text NOT NULL,
  "matchID" INTEGER NOT NULL,
  "authorID" INTEGER NOT NULL,
  "viewedByStudent" tinyint(1) NOT NULL,
  "text" text NOT NULL,
  CONSTRAINT "peer_review_assignment_appeal_messages_ibfk_1" FOREIGN KEY ("matchID") REFERENCES "peer_review_assignment_matches" ("matchID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_appeal_messages_ibfk_2" FOREIGN KEY ("authorID") REFERENCES "users" ("userID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_appeal_messages_ibfk_3" FOREIGN KEY ("appealType") REFERENCES "appealType" ("value") ON DELETE SET NULL
);
CREATE TABLE "appealType"(
  "value" text PRIMARY KEY
);
INSERT INTO "appealType" ("value") VALUES ("review"), ("reviewMark");
CREATE TABLE "peer_review_assignment_article_response_settings" (
  "assignmentID" INTEGER NOT NULL,
  "articleIndex" INTEGER NOT NULL,
  "name" varchar(255) NOT NULL,
  "link" text NOT NULL,
  PRIMARY KEY ("assignmentID", "articleIndex"),
  CONSTRAINT "peer_review_assignment_article_response_settings_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_article_responses" (
  "submissionID" INTEGER PRIMARY KEY,
  "articleIndex" INTEGER NOT NULL,
  "outline" longtext NOT NULL,
  "response" longtext NOT NULL,
  CONSTRAINT "peer_review_assignment_article_responses_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_calibration_matches" (
  "matchID" INTEGER PRIMARY KEY,
  "assignmentID" INTEGER NOT NULL,
  "required" tinyint(1) NOT NULL,
  CONSTRAINT "peer_review_assignment_calibration_matches_ibfk_1" FOREIGN KEY ("matchID") REFERENCES "peer_review_assignment_matches" ("matchID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_calibration_matches_ibfk_2" FOREIGN KEY ("assignmentID") REFERENCES "peer_review_assignment" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_calibration_pools" (
  "assignmentID" INTEGER NOT NULL,
  "poolAssignmentID" INTEGER NOT NULL,
  PRIMARY KEY ("assignmentID", "poolAssignmentID"),
  CONSTRAINT "peer_review_assignment_calibration_pools_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "peer_review_assignment" ("assignmentID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_calibration_pools_ibfk_2" FOREIGN KEY ("poolAssignmentID") REFERENCES "peer_review_assignment" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_code" (
  "submissionID" INTEGER PRIMARY KEY,
  "code" longtext NOT NULL,
  CONSTRAINT "peer_review_assignment_code_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_code_settings" (
  "assignmentID" INTEGER PRIMARY KEY,
  "codeLanguage" varchar(255) NOT NULL,
  "codeExtension" varchar(10) NOT NULL DEFAULT '',
  "uploadOnly" tinyint(1) NOT NULL,
  CONSTRAINT "peer_review_assignment_code_settings_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "peer_review_assignment" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_demotion_log" (
  "userID" INTEGER PRIMARY KEY ,
  "demotionDate" datetime NOT NULL,
  "demotionThreshold" float NOT NULL,
  CONSTRAINT "peer_review_assignment_demotion_log_ibfk_1" FOREIGN KEY ("userID") REFERENCES "users" ("userID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_denied" (
  "userID" INTEGER NOT NULL,
  "assignmentID" INTEGER NOT NULL,
  PRIMARY KEY ("userID", "assignmentID"),
  CONSTRAINT "peer_review_assignment_denied_ibfk_1" FOREIGN KEY ("userID") REFERENCES "users" ("userID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_denied_ibfk_2" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_essay_settings" (
  "assignmentID" INTEGER NOT NULL,
  "topicIndex" INTEGER NOT NULL,
  "topic" varchar(255) NOT NULL,
  PRIMARY KEY ("assignmentID", "topicIndex"),
  CONSTRAINT "peer_review_assignment_essay_settings_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_essays" (
  "submissionID" INTEGER PRIMARY KEY,
  "text" longtext NOT NULL,
  "topicIndex" INTEGER DEFAULT NULL,
  CONSTRAINT "peer_review_assignment_essays_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_images" (
  "submissionID" INTEGER PRIMARY KEY,
  "imgWidth" INTEGER NOT NULL,
  "imgHeight" INTEGER NOT NULL,
  "imgData" longblob NOT NULL,
  "text" text NOT NULL
);
CREATE TABLE "peer_review_assignment_independent" (
  "userID" INTEGER NOT NULL,
  "assignmentID" INTEGER NOT NULL,
  PRIMARY KEY ("userID", "assignmentID"),
  CONSTRAINT "peer_review_assignment_independent_ibfk_1" FOREIGN KEY ("userID") REFERENCES "users" ("userID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_independent_ibfk_2" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_instructor_review_touch_times" (
  "submissionID" INTEGER NOT NULL,
  "instructorID" INTEGER NOT NULL,
  "timestamp" datetime NOT NULL,
  PRIMARY KEY ("submissionID", "instructorID"),
  CONSTRAINT "peer_review_assignment_instructor_review_touch_times_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT "peer_review_assignment_instructor_review_touch_times_ibfk_2" FOREIGN KEY ("instructorID") REFERENCES "users" ("userID") ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE TABLE "peer_review_assignment_matches" (
  "matchID" INTEGER PRIMARY KEY,
  "submissionID" INTEGER NOT NULL,
  "reviewerID" INTEGER NOT NULL,
  "instructorForced" tinyint(1) NOT NULL,
  "calibrationState" text  NOT NULL DEFAULT 'none',
  UNIQUE ("submissionID","reviewerID"),
  CONSTRAINT "peer_review_assignment_matches_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_matches_ibfk_2" FOREIGN KEY ("reviewerID") REFERENCES "users" ("userID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_matches_ibfk_3" FOREIGN KEY ("calibrationState") REFERENCES "calibrationState" ("value") ON DELETE SET NULL
);
CREATE TABLE "calibrationState"(
  "value" text PRIMARY KEY
);
INSERT INTO "calibrationState" ("value") VALUES ("none"), ("key"), ("attempt"), ("covert");
CREATE TABLE "peer_review_assignment_questions" (
  "questionID" INTEGER PRIMARY KEY ,
  "assignmentID" INTEGER NOT NULL,
  "questionName" varchar(128) NOT NULL,
  "questionText" text NOT NULL,
  "questionType" varchar(64) NOT NULL,
  "hidden" tinyint(1) NOT NULL,
  "displayPriority" INTEGER NOT NULL,
  CONSTRAINT "peer_review_assignment_questions_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_radio_options" (
  "questionID" INTEGER NOT NULL,
  "index" INTEGER NOT NULL,
  "label" varchar(1024) NOT NULL,
  "score" double NOT NULL,
  PRIMARY KEY ("questionID", "index"),
  CONSTRAINT "peer_review_assignment_radio_options_ibfk_1" FOREIGN KEY ("questionID") REFERENCES "peer_review_assignment_questions" ("questionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_review_answers" (
  "matchID" INTEGER NOT NULL,
  "questionID" INTEGER NOT NULL,
  "answerInt" INTEGER DEFAULT NULL,
  "answerText" text,
  "reviewTimestamp" datetime NOT NULL,
  PRIMARY KEY ("matchID", "questionID"),
  CONSTRAINT "peer_review_assignment_review_answers_ibfk_1" FOREIGN KEY ("matchID") REFERENCES "peer_review_assignment_matches" ("matchID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_review_answers_ibfk_2" FOREIGN KEY ("questionID") REFERENCES "peer_review_assignment_questions" ("questionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_review_answers_drafts" (
  "matchID" INTEGER NOT NULL,
  "questionID" INTEGER NOT NULL,
  "answerInt" INTEGER DEFAULT NULL,
  "answerText" text,
  PRIMARY KEY ("matchID", "questionID"),
  CONSTRAINT "peer_review_assignment_review_answers_drafts_ibfk_1" FOREIGN KEY ("matchID") REFERENCES "peer_review_assignment_matches" ("matchID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_review_answers_drafts_ibfk_3" FOREIGN KEY ("questionID") REFERENCES "peer_review_assignment_questions" ("questionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_review_marks" (
  "matchID" INTEGER PRIMARY KEY,
  "score" double NOT NULL,
  "comments" text,
  "automatic" tinyint(1) NOT NULL DEFAULT '0',
  "reviewPoints" float NOT NULL,
  "reviewMarkTimestamp" datetime NOT NULL,
  CONSTRAINT "peer_review_assignment_review_marks_ibfk_2" FOREIGN KEY ("matchID") REFERENCES "peer_review_assignment_matches" ("matchID") ON DELETE CASCADE
);
CREATE TABLE "status"(
  "value" text PRIMARY KEY
);
INSERT INTO "status" VALUES ('pending');
INSERT INTO "status" VALUES ('nochange');
INSERT INTO "status" VALUES ('change');
INSERT INTO "status" VALUES ('');
CREATE TABLE "peer_review_assignment_spot_checks" (
  "submissionID" INTEGER PRIMARY KEY,
  "checkerID" INTEGER NOT NULL,
  "status" text NOT NULL DEFAULT 'pending',
  CONSTRAINT "peer_review_assignment_spot_checks_ibfk_1" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_spot_checks_ibfk_2" FOREIGN KEY ("checkerID") REFERENCES "users" ("userID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_spot_checks_ibfk_3" FOREIGN KEY ("status") REFERENCES "status" ("value") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_submission_marks" (
  "submissionID" INTEGER PRIMARY KEY,
  "score" double NOT NULL,
  "comments" text,
  "automatic" tinyint(1) NOT NULL DEFAULT '0',
  "submissionMarkTimestamp" datetime NOT NULL,
  CONSTRAINT "peer_review_assignment_submission_marks_ibfk_3" FOREIGN KEY ("submissionID") REFERENCES "peer_review_assignment_submissions" ("submissionID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_submissions" (
  "submissionID" INTEGER PRIMARY KEY,
  "assignmentID" INTEGER NOT NULL,
  "authorID" INTEGER NOT NULL,
  "noPublicUse" tinyint(1) NOT NULL,
  "submissionTimestamp" datetime NOT NULL,
  UNIQUE ("assignmentID","authorID"),
  CONSTRAINT "peer_review_assignment_submissions_ibfk_1" FOREIGN KEY ("assignmentID") REFERENCES "assignments" ("assignmentID") ON DELETE CASCADE,
  CONSTRAINT "peer_review_assignment_submissions_ibfk_2" FOREIGN KEY ("authorID") REFERENCES "users" ("userID") ON DELETE CASCADE
);
CREATE TABLE "peer_review_assignment_text_options" (
  "questionID" INTEGER PRIMARY KEY,
  "minLength" INTEGER NOT NULL,
  CONSTRAINT "peer_review_assignment_text_options_ibfk_1" FOREIGN KEY ("questionID") REFERENCES "peer_review_assignment_questions" ("questionID") ON DELETE CASCADE
);
CREATE TABLE "user_passwords" (
  "username" varchar(64) PRIMARY KEY,
  "passwordHash" varchar(128) NOT NULL
);
CREATE TABLE "users" (
  "userID" INTEGER PRIMARY KEY,
  "userType" text NOT NULL,
  "courseID" INTEGER NOT NULL,
  "firstName" varchar(128) NOT NULL,
  "lastName" varchar(128) NOT NULL,
  "username" varchar(64) NOT NULL,
  "studentID" INTEGER NOT NULL,
  "alias" varchar(64) DEFAULT NULL,
  "markingLoad" float NOT NULL DEFAULT '0',
  "dropped" tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE ("courseID","username"),
  CONSTRAINT "users_ibfk_1" FOREIGN KEY ("courseID") REFERENCES "course" ("courseID") ON DELETE CASCADE,
  CONSTRAINT "users_ibfk_2" FOREIGN KEY ("userType") REFERENCES "userType" ("value") ON DELETE SET NULL
);
CREATE TABLE "userType"(
  "value" text PRIMARY KEY
);
INSERT INTO "userType" ("value") VALUES ("student"), ("anonymous"), ("instructor"), ("shadowinstructor"), ("marker"), ("shadowmarker");
CREATE INDEX "peer_review_assignment_denied_userID" ON "peer_review_assignment_denied" ("userID","assignmentID");
CREATE INDEX "peer_review_assignment_denied_assignmentID" ON "peer_review_assignment_denied" ("assignmentID");
CREATE INDEX "group_picker_assignment_assignmentID" ON "group_picker_assignment" ("assignmentID");
CREATE INDEX "user_passwords_username" ON "user_passwords" ("username");
CREATE INDEX "user_passwords_userID" ON "user_passwords" ("username");
CREATE INDEX "peer_review_assignment_assignmentID" ON "peer_review_assignment" ("assignmentID");
CREATE INDEX "users_userID" ON "users" ("userID");
CREATE INDEX "users_courseID" ON "users" ("courseID","username");
CREATE INDEX "users_lastName" ON "users" ("lastName");
CREATE INDEX "users_studentID" ON "users" ("studentID");
CREATE INDEX "users_username" ON "users" ("username");
CREATE INDEX "users_userType_username" ON "users" ("userType","username");
CREATE INDEX "users_userType" ON "users" ("userType");
CREATE INDEX "users_userID_userType" ON "users" ("userID","userType");
CREATE INDEX "peer_review_assignment_questions_questionID" ON "peer_review_assignment_questions" ("questionID");
CREATE INDEX "peer_review_assignment_questions_assignmentID" ON "peer_review_assignment_questions" ("assignmentID");
CREATE INDEX "peer_review_assignment_questions_assignmentID_displayPriority" ON "peer_review_assignment_questions" ("assignmentID","displayPriority");
CREATE INDEX "peer_review_assignment_essay_settings_assignmentID_topicIndex" ON "peer_review_assignment_essay_settings" ("assignmentID","topicIndex");
CREATE INDEX "peer_review_assignment_code_submissionID" ON "peer_review_assignment_code" ("submissionID");
CREATE INDEX "peer_review_assignment_calibration_pools_assignmentID_poolAssignmentID" ON "peer_review_assignment_calibration_pools" ("assignmentID","poolAssignmentID");
CREATE INDEX "peer_review_assignment_calibration_pools_poolAssignmentID" ON "peer_review_assignment_calibration_pools" ("poolAssignmentID");
CREATE INDEX "peer_review_assignment_calibration_pools_assignmentID" ON "peer_review_assignment_calibration_pools" ("assignmentID");
CREATE INDEX "course_configuration_courseID" ON "course_configuration" ("courseID");
CREATE INDEX "assignments_assignmentID" ON "assignments" ("assignmentID");
CREATE INDEX "assignments_courseID" ON "assignments" ("courseID");
CREATE INDEX "assignments_assignment_name" ON "assignments" ("name");
CREATE INDEX "assignments_courseID_displayPriority" ON "assignments" ("courseID","displayPriority");
CREATE INDEX "peer_review_assignment_spot_checks_submissionID" ON "peer_review_assignment_spot_checks" ("submissionID");
CREATE INDEX "peer_review_assignment_spot_checks_submissionID_checkerID_status" ON "peer_review_assignment_spot_checks" ("submissionID","checkerID","status");
CREATE INDEX "peer_review_assignment_spot_checks_submissionID_checkerID" ON "peer_review_assignment_spot_checks" ("submissionID","checkerID");
CREATE INDEX "peer_review_assignment_spot_checks_checkerID" ON "peer_review_assignment_spot_checks" ("checkerID");
CREATE INDEX "peer_review_assignment_review_answers_matchID_questionID" ON "peer_review_assignment_review_answers" ("matchID","questionID");
CREATE INDEX "peer_review_assignment_review_answers_questionID" ON "peer_review_assignment_review_answers" ("questionID");
CREATE INDEX "peer_review_assignment_radio_options_questionID_index" ON "peer_review_assignment_radio_options" ("questionID","index");
CREATE INDEX "peer_review_assignment_radio_options_questionID" ON "peer_review_assignment_radio_options" ("questionID");
CREATE INDEX "peer_review_assignment_matches_matchID" ON "peer_review_assignment_matches" ("matchID");
CREATE INDEX "peer_review_assignment_matches_submissionID_reviewerID" ON "peer_review_assignment_matches" ("submissionID","reviewerID");
CREATE INDEX "peer_review_assignment_matches_submissionID" ON "peer_review_assignment_matches" ("submissionID");
CREATE INDEX "peer_review_assignment_matches_reviewerID" ON "peer_review_assignment_matches" ("reviewerID");
CREATE INDEX "peer_review_assignment_essays_submissionID" ON "peer_review_assignment_essays" ("submissionID");
CREATE INDEX "peer_review_assignment_code_settings_assignmentID" ON "peer_review_assignment_code_settings" ("assignmentID");
CREATE INDEX "peer_review_assignment_submission_marks_submissionID" ON "peer_review_assignment_submission_marks" ("submissionID");
CREATE INDEX "peer_review_assignment_images_submissionID" ON "peer_review_assignment_images" ("submissionID");
CREATE INDEX "peer_review_assignment_article_response_settings_assignmentID_" ON "peer_review_assignment_article_response_settings" ("assignmentID","articleIndex");
CREATE INDEX "peer_review_assignment_article_response_settings_assignmentID" ON "peer_review_assignment_article_response_settings" ("assignmentID");
CREATE INDEX "appeal_assignment_submissionID" ON "appeal_assignment" ("submissionID");
CREATE INDEX "appeal_assignment_markerID" ON "appeal_assignment" ("markerID");
CREATE INDEX "peer_review_assignment_text_options_questionID" ON "peer_review_assignment_text_options" ("questionID");
CREATE INDEX "peer_review_assignment_demotion_log_userID" ON "peer_review_assignment_demotion_log" ("userID");
CREATE INDEX "assignment_password_entered_userID" ON "assignment_password_entered" ("userID","assignmentID");
CREATE INDEX "assignment_password_entered_assignmentID" ON "assignment_password_entered" ("assignmentID");
CREATE INDEX "peer_review_assignment_review_answers_drafts_matchID_questionID" ON "peer_review_assignment_review_answers_drafts" ("matchID","questionID");
CREATE INDEX "peer_review_assignment_review_answers_drafts_questionID" ON "peer_review_assignment_review_answers_drafts" ("questionID");
CREATE INDEX "peer_review_assignment_independent_userID_assignmentID" ON "peer_review_assignment_independent" ("userID","assignmentID");
CREATE INDEX "peer_review_assignment_independent_assignmentID" ON "peer_review_assignment_independent" ("assignmentID");
CREATE INDEX "peer_review_assignment_calibration_matches_matchID" ON "peer_review_assignment_calibration_matches" ("matchID");
CREATE INDEX "peer_review_assignment_calibration_matches_assignmentID" ON "peer_review_assignment_calibration_matches" ("assignmentID");
CREATE INDEX "job_notifications_notificationID" ON "job_notifications" ("notificationID");
CREATE INDEX "job_notifications_courseID_assignmentID" ON "job_notifications" ("courseID","assignmentID");
CREATE INDEX "group_picker_assignment_selections_selectionID" ON "group_picker_assignment_selections" ("selectionID");
CREATE INDEX "group_picker_assignment_selections_assignmentID_userID" ON "group_picker_assignment_selections" ("assignmentID","userID");
CREATE INDEX "group_picker_assignment_selections_userID" ON "group_picker_assignment_selections" ("userID");
CREATE INDEX "group_picker_assignment_selections_selectionID_2" ON "group_picker_assignment_selections" ("selectionID","assignmentID","groupIndex");
CREATE INDEX "peer_review_assignment_instructor_review_touch_times_submissionID" ON "peer_review_assignment_instructor_review_touch_times" ("submissionID","instructorID");
CREATE INDEX "peer_review_assignment_instructor_review_touch_times_instructorID" ON "peer_review_assignment_instructor_review_touch_times" ("instructorID");
CREATE INDEX "peer_review_assignment_submissions_submissionID" ON "peer_review_assignment_submissions" ("submissionID");
CREATE INDEX "peer_review_assignment_submissions_assignmentID" ON "peer_review_assignment_submissions" ("assignmentID","authorID");
CREATE INDEX "peer_review_assignment_submissions_authorID" ON "peer_review_assignment_submissions" ("authorID");
CREATE INDEX "peer_review_assignment_appeal_messages_appealMessageID" ON "peer_review_assignment_appeal_messages" ("appealMessageID");
CREATE INDEX "peer_review_assignment_appeal_messages_matchID" ON "peer_review_assignment_appeal_messages" ("matchID");
CREATE INDEX "peer_review_assignment_appeal_messages_matchID_viewedByStudent" ON "peer_review_assignment_appeal_messages" ("matchID","viewedByStudent");
CREATE INDEX "peer_review_assignment_appeal_messages_authorID" ON "peer_review_assignment_appeal_messages" ("authorID");
CREATE INDEX "peer_review_assignment_appeal_messages_appealMessageID_2" ON "peer_review_assignment_appeal_messages" ("appealMessageID","appealType","matchID");
CREATE INDEX "course_courseID" ON "course" ("courseID");
CREATE INDEX "course_course_name" ON "course" ("name");
CREATE INDEX "group_picker_assignment_groups_assignmentID" ON "group_picker_assignment_groups" ("assignmentID","groupIndex");
CREATE INDEX "peer_review_assignment_review_marks_matchID" ON "peer_review_assignment_review_marks" ("matchID");
CREATE INDEX "peer_review_assignment_article_responses_submissionID" ON "peer_review_assignment_article_responses" ("submissionID");
END TRANSACTION;
