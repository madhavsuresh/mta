CREATE TABLE IF NOT EXISTS `job_notifications` (
  `notificationID` int(11) NOT NULL AUTO_INCREMENT,
  `courseID` int(11) NOT NULL,  
  `assignmentID` int(11) NOT NULL,
  `job` enum('general','autogradeandassign','copyindependents','computeindependentsfromscores','computeindependentsfromcalibrations', 'disqualifyindependents', 'assignreviews') NOT NULL DEFAULT 'general',
  `dateRan` datetime NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `summary` long NOT NULL,
  PRIMARY KEY (`notificationID`),
  KEY `courseID` (`courseID`, `assignmentID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;