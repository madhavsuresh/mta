-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 12, 2013 at 02:50 PM
-- Server version: 5.5.24
-- PHP Version: 5.3.10-1ubuntu3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mta`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE IF NOT EXISTS `assignments` (
  `assignmentID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `courseID` int(11) NOT NULL,
  `displayPriority` int(11) NOT NULL,
  `assignmentType` varchar(64) NOT NULL,
  `passwordMessage` text,
  `password` varchar(255) DEFAULT NULL,
  `visibleToStudents` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`assignmentID`),
  KEY `courseID` (`courseID`),
  KEY `assignment_name` (`name`),
  KEY `courseID_2` (`courseID`,`displayPriority`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_password_entered`
--

CREATE TABLE IF NOT EXISTS `assignment_password_entered` (
  `userID` int(11) NOT NULL,
  `assignmentID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`assignmentID`),
  KEY `assignmentID` (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE IF NOT EXISTS `course` (
  `courseID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `displayName` varchar(128) NOT NULL,
  `authType` varchar(128) NOT NULL,
  `registrationType` varchar(128) NOT NULL,
  `browsable` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`courseID`),
  UNIQUE KEY `course_name_2` (`name`),
  KEY `course_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_picker_assignment`
--

CREATE TABLE IF NOT EXISTS `group_picker_assignment` (
  `assignmentID` int(11) NOT NULL,
  `startDate` datetime NOT NULL,
  `stopDate` datetime NOT NULL,
  PRIMARY KEY (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_picker_assignment_groups`
--

CREATE TABLE IF NOT EXISTS `group_picker_assignment_groups` (
  `assignmentID` int(11) NOT NULL,
  `groupIndex` int(11) NOT NULL,
  `groupText` text NOT NULL,
  PRIMARY KEY (`assignmentID`,`groupIndex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `group_picker_assignment_selections`
--

CREATE TABLE IF NOT EXISTS `group_picker_assignment_selections` (
  `selectionID` int(11) NOT NULL AUTO_INCREMENT,
  `assignmentID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `groupIndex` int(11) NOT NULL,
  PRIMARY KEY (`selectionID`),
  UNIQUE KEY `assignmentID` (`assignmentID`,`userID`),
  KEY `userID` (`userID`),
  KEY `selectionID` (`selectionID`,`assignmentID`,`groupIndex`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment` (
  `assignmentID` int(11) NOT NULL,
  `submissionQuestion` longtext NOT NULL,
  `submissionType` varchar(64) NOT NULL,
  `submissionStartDate` datetime NOT NULL,
  `submissionStopDate` datetime NOT NULL,
  `reviewStartDate` datetime NOT NULL,
  `reviewStopDate` datetime NOT NULL,
  `markPostDate` datetime NOT NULL,
  `maxSubmissionScore` float NOT NULL,
  `maxReviewScore` float NOT NULL,
  `defaultNumberOfReviews` int(11) NOT NULL,
  `allowRequestOfReviews` tinyint(1) NOT NULL,
  `showMarksForReviewsReceived` tinyint(1) NOT NULL,
  `showOtherReviewsByStudents` tinyint(1) NOT NULL,
  `showOtherReviewsByInstructors` tinyint(1) NOT NULL,
  `showMarksForOtherReviews` tinyint(1) NOT NULL,
  `showMarksForReviewedSubmissions` tinyint(1) NOT NULL,
  `appealStopDate` datetime NOT NULL,
  `reviewScoreMaxDeviationForGood` float NOT NULL,
  `reviewScoreMaxCountsForGood` int(11) NOT NULL,
  `reviewScoreMaxDeviationForPass` float NOT NULL,
  `reviewScoreMaxCountsForPass` int(11) NOT NULL,
  `showPoolStatus` tinyint(1) NOT NULL,
  PRIMARY KEY (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_appeal_messages`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_appeal_messages` (
  `appealMessageID` int(11) NOT NULL AUTO_INCREMENT,
  `appealType` enum('review','reviewMark') NOT NULL,
  `matchID` int(11) NOT NULL,
  `authorID` int(11) NOT NULL,
  `viewedByStudent` tinyint(1) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`appealMessageID`),
  KEY `matchID` (`matchID`),
  KEY `matchID_2` (`matchID`,`viewedByStudent`),
  KEY `matchID_3` (`matchID`),
  KEY `matchID_4` (`matchID`),
  KEY `authorID` (`authorID`),
  KEY `appealMessageID` (`appealMessageID`,`appealType`,`matchID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_article_responses`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_article_responses` (
  `submissionID` int(11) NOT NULL,
  `articleIndex` int(11) NOT NULL,
  `outline` longtext NOT NULL,
  `response` longtext NOT NULL,
  PRIMARY KEY (`submissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_article_response_settings`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_article_response_settings` (
  `assignmentID` int(11) NOT NULL,
  `articleIndex` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `link` text NOT NULL,
  PRIMARY KEY (`assignmentID`,`articleIndex`),
  KEY `assignmentID` (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_calibration_matches`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_calibration_matches` (
  `matchID` int(11) NOT NULL,
  `assignmentID` int(11) NOT NULL,
  `required` tinyint(1) NOT NULL,
  PRIMARY KEY (`matchID`),
  KEY `assignmentID` (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_calibration_pools`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_calibration_pools` (
  `assignmentID` int(11) NOT NULL,
  `poolAssignmentID` int(11) NOT NULL,
  PRIMARY KEY (`assignmentID`,`poolAssignmentID`),
  KEY `poolAssignmentID` (`poolAssignmentID`),
  KEY `assignmentID` (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_code`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_code` (
  `submissionID` int(11) NOT NULL,
  `code` longtext NOT NULL,
  PRIMARY KEY (`submissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_code_settings`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_code_settings` (
  `assignmentID` int(11) NOT NULL,
  `codeLanguage` varchar(255) NOT NULL,
  `codeExtension` varchar(10) NOT NULL DEFAULT '',
  `uploadOnly` tinyint(1) NOT NULL,
  PRIMARY KEY (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_denied`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_denied` (
  `userID` int(11) NOT NULL,
  `assignmentID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`assignmentID`),
  KEY `assignmentID` (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_essays`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_essays` (
  `submissionID` int(11) NOT NULL,
  `text` longtext NOT NULL,
  `topicIndex` int(11) DEFAULT NULL,
  PRIMARY KEY (`submissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_essay_settings`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_essay_settings` (
  `assignmentID` int(11) NOT NULL,
  `topicIndex` int(11) NOT NULL,
  `topic` varchar(255) NOT NULL,
  PRIMARY KEY (`assignmentID`,`topicIndex`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_images`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_images` (
  `submissionID` int(11) NOT NULL,
  `imgWidth` int(11) NOT NULL,
  `imgHeight` int(11) NOT NULL,
  `imgData` longblob NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`submissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_independent`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_independent` (
  `userID` int(11) NOT NULL,
  `assignmentID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`assignmentID`),
  KEY `assignmentID` (`assignmentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_instructor_review_touch_times`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_instructor_review_touch_times` (
  `submissionID` int(11) NOT NULL,
  `instructorID` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`submissionID`,`instructorID`),
  KEY `instructorID` (`instructorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_matches`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_matches` (
  `matchID` int(11) NOT NULL AUTO_INCREMENT,
  `submissionID` int(11) NOT NULL,
  `reviewerID` int(11) NOT NULL,
  `instructorForced` tinyint(1) NOT NULL,
  PRIMARY KEY (`matchID`),
  UNIQUE KEY `submissionID` (`submissionID`,`reviewerID`),
  KEY `submissioID_2` (`submissionID`),
  KEY `reviewerID` (`reviewerID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_questions`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_questions` (
  `questionID` int(11) NOT NULL AUTO_INCREMENT,
  `assignmentID` int(11) NOT NULL,
  `questionName` varchar(128) NOT NULL,
  `questionText` text NOT NULL,
  `questionType` varchar(64) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `displayPriority` int(11) NOT NULL,
  PRIMARY KEY (`questionID`),
  KEY `assignmentID` (`assignmentID`),
  KEY `assignmentID_2` (`assignmentID`,`displayPriority`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_radio_options`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_radio_options` (
  `questionID` int(11) NOT NULL,
  `index` int(11) NOT NULL,
  `label` varchar(1024) NOT NULL,
  `score` double NOT NULL,
  PRIMARY KEY (`questionID`,`index`),
  KEY `questionID` (`questionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_review_answers`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_review_answers` (
  `matchID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `answerInt` int(11) DEFAULT NULL,
  `answerText` text,
  PRIMARY KEY (`matchID`,`questionID`),
  KEY `questionID` (`questionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_review_answers_drafts`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_review_answers_drafts` (
  `matchID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `answerInt` int(11) DEFAULT NULL,
  `answerText` text,
  PRIMARY KEY (`matchID`,`questionID`),
  KEY `questionID` (`questionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_review_marks`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_review_marks` (
  `matchID` int(11) NOT NULL,
  `score` double NOT NULL,
  `comments` text,
  `automatic` tinyint(1) NOT NULL DEFAULT '0',
  `reviewPoints` float NOT NULL,
  PRIMARY KEY (`matchID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_spot_checks`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_spot_checks` (
  `submissionID` int(11) NOT NULL,
  `checkerID` int(11) NOT NULL,
  `status` enum('pending','nochange','change','') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`submissionID`),
  KEY `submissionID` (`submissionID`,`checkerID`,`status`),
  KEY `submissionID_2` (`submissionID`,`checkerID`),
  KEY `checkerID` (`checkerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_submissions`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_submissions` (
  `submissionID` int(11) NOT NULL AUTO_INCREMENT,
  `assignmentID` int(11) NOT NULL,
  `authorID` int(11) NOT NULL,
  `noPublicUse` tinyint(1) NOT NULL,
  PRIMARY KEY (`submissionID`),
  UNIQUE KEY `assignmentID_2` (`assignmentID`,`authorID`),
  KEY `assignmentID` (`assignmentID`,`authorID`),
  KEY `authorID` (`authorID`),
  KEY `assignmentID_3` (`assignmentID`,`authorID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_submission_marks`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_submission_marks` (
  `submissionID` int(11) NOT NULL,
  `score` double NOT NULL,
  `comments` text,
  `automatic` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`submissionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_text_options`
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_text_options` (
  `questionID` int(11) NOT NULL,
  `minLength` int(11) NOT NULL,
  PRIMARY KEY (`questionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `userType` enum('student','anonymous','instructor','shadowinstructor','marker','shadowmarker') NOT NULL,
  `courseID` int(11) NOT NULL,
  `firstName` varchar(128) NOT NULL,
  `lastName` varchar(128) NOT NULL,
  `username` varchar(64) NOT NULL,
  `studentID` int(11) NOT NULL,
  `alias` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `courseID` (`courseID`,`username`),
  KEY `lastName` (`lastName`),
  KEY `studentID` (`studentID`),
  KEY `username` (`username`),
  KEY `userType` (`userType`,`username`),
  KEY `userType_2` (`userType`),
  KEY `userID` (`userID`,`userType`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_passwords`
--

CREATE TABLE IF NOT EXISTS `user_passwords` (
  `username` varchar(64) NOT NULL,
  `passwordHash` varchar(128) NOT NULL,
  PRIMARY KEY (`username`),
  KEY `userID` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_password_entered`
--
ALTER TABLE `assignment_password_entered`
  ADD CONSTRAINT `assignment_password_entered_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignment_password_entered_ibfk_2` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `group_picker_assignment`
--
ALTER TABLE `group_picker_assignment`
  ADD CONSTRAINT `group_picker_assignment_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `group_picker_assignment_groups`
--
ALTER TABLE `group_picker_assignment_groups`
  ADD CONSTRAINT `group_picker_assignment_groups_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `group_picker_assignment_selections`
--
ALTER TABLE `group_picker_assignment_selections`
  ADD CONSTRAINT `group_picker_assignment_selections_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_picker_assignment_selections_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment`
--
ALTER TABLE `peer_review_assignment`
  ADD CONSTRAINT `peer_review_assignment_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_appeal_messages`
--
ALTER TABLE `peer_review_assignment_appeal_messages`
  ADD CONSTRAINT `peer_review_assignment_appeal_messages_ibfk_1` FOREIGN KEY (`matchID`) REFERENCES `peer_review_assignment_matches` (`matchID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_appeal_messages_ibfk_2` FOREIGN KEY (`authorID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_article_responses`
--
ALTER TABLE `peer_review_assignment_article_responses`
  ADD CONSTRAINT `peer_review_assignment_article_responses_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_article_response_settings`
--
ALTER TABLE `peer_review_assignment_article_response_settings`
  ADD CONSTRAINT `peer_review_assignment_article_response_settings_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_calibration_matches`
--
ALTER TABLE `peer_review_assignment_calibration_matches`
  ADD CONSTRAINT `peer_review_assignment_calibration_matches_ibfk_1` FOREIGN KEY (`matchID`) REFERENCES `peer_review_assignment_matches` (`matchID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_calibration_matches_ibfk_2` FOREIGN KEY (`assignmentID`) REFERENCES `peer_review_assignment` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_calibration_pools`
--
ALTER TABLE `peer_review_assignment_calibration_pools`
  ADD CONSTRAINT `peer_review_assignment_calibration_pools_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `peer_review_assignment` (`assignmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_calibration_pools_ibfk_2` FOREIGN KEY (`poolAssignmentID`) REFERENCES `peer_review_assignment` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_code`
--
ALTER TABLE `peer_review_assignment_code`
  ADD CONSTRAINT `peer_review_assignment_code_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_code_settings`
--
ALTER TABLE `peer_review_assignment_code_settings`
  ADD CONSTRAINT `peer_review_assignment_code_settings_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `peer_review_assignment` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_denied`
--
ALTER TABLE `peer_review_assignment_denied`
  ADD CONSTRAINT `peer_review_assignment_denied_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_denied_ibfk_2` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_essays`
--
ALTER TABLE `peer_review_assignment_essays`
  ADD CONSTRAINT `peer_review_assignment_essays_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_essay_settings`
--
ALTER TABLE `peer_review_assignment_essay_settings`
  ADD CONSTRAINT `peer_review_assignment_essay_settings_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_independent`
--
ALTER TABLE `peer_review_assignment_independent`
  ADD CONSTRAINT `peer_review_assignment_independent_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_independent_ibfk_2` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_instructor_review_touch_times`
--
ALTER TABLE `peer_review_assignment_instructor_review_touch_times`
  ADD CONSTRAINT `peer_review_assignment_instructor_review_touch_times_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_instructor_review_touch_times_ibfk_2` FOREIGN KEY (`instructorID`) REFERENCES `users` (`userID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peer_review_assignment_matches`
--
ALTER TABLE `peer_review_assignment_matches`
  ADD CONSTRAINT `peer_review_assignment_matches_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_matches_ibfk_2` FOREIGN KEY (`reviewerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_questions`
--
ALTER TABLE `peer_review_assignment_questions`
  ADD CONSTRAINT `peer_review_assignment_questions_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_radio_options`
--
ALTER TABLE `peer_review_assignment_radio_options`
  ADD CONSTRAINT `peer_review_assignment_radio_options_ibfk_1` FOREIGN KEY (`questionID`) REFERENCES `peer_review_assignment_questions` (`questionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_review_answers`
--
ALTER TABLE `peer_review_assignment_review_answers`
  ADD CONSTRAINT `peer_review_assignment_review_answers_ibfk_1` FOREIGN KEY (`matchID`) REFERENCES `peer_review_assignment_matches` (`matchID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_review_answers_ibfk_2` FOREIGN KEY (`questionID`) REFERENCES `peer_review_assignment_questions` (`questionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_review_answers_drafts`
--
ALTER TABLE `peer_review_assignment_review_answers_drafts`
  ADD CONSTRAINT `peer_review_assignment_review_answers_drafts_ibfk_1` FOREIGN KEY (`matchID`) REFERENCES `peer_review_assignment_matches` (`matchID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_review_answers_drafts_ibfk_3` FOREIGN KEY (`questionID`) REFERENCES `peer_review_assignment_questions` (`questionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_review_marks`
--
ALTER TABLE `peer_review_assignment_review_marks`
  ADD CONSTRAINT `peer_review_assignment_review_marks_ibfk_2` FOREIGN KEY (`matchID`) REFERENCES `peer_review_assignment_matches` (`matchID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_spot_checks`
--
ALTER TABLE `peer_review_assignment_spot_checks`
  ADD CONSTRAINT `peer_review_assignment_spot_checks_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_spot_checks_ibfk_2` FOREIGN KEY (`checkerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_submissions`
--
ALTER TABLE `peer_review_assignment_submissions`
  ADD CONSTRAINT `peer_review_assignment_submissions_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `peer_review_assignment_submissions_ibfk_2` FOREIGN KEY (`authorID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_submission_marks`
--
ALTER TABLE `peer_review_assignment_submission_marks`
  ADD CONSTRAINT `peer_review_assignment_submission_marks_ibfk_3` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

--
-- Constraints for table `peer_review_assignment_text_options`
--
ALTER TABLE `peer_review_assignment_text_options`
  ADD CONSTRAINT `peer_review_assignment_text_options_ibfk_1` FOREIGN KEY (`questionID`) REFERENCES `peer_review_assignment_questions` (`questionID`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
