CREATE TABLE IF NOT EXISTS `job_notifications` (
  `notificationID` int(11) NOT NULL AUTO_INCREMENT,
  `courseID` int(11) NOT NULL,  
  `assignmentID` int(11) NOT NULL,
  `job` enum('general','autogradeandassign','copyindependentsfromprevious','computeindependentsfromscores','computeindependentsfromcalibrations', 'disqualifyindependentsfromscores', 'assignreviews') NOT NULL DEFAULT 'general',
  `dateRan` datetime NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  `summary` text NOT NULL,
  `details` longtext NOT NULL,
  PRIMARY KEY (`notificationID`),
  KEY `courseID` (`courseID`, `assignmentID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;