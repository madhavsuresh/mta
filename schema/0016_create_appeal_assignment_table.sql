CREATE TABLE IF NOT EXISTS `appeal_assignment` (
  `submissionID` int(11) NOT NULL,
  `markerID` int(11) NOT NULL,
  PRIMARY KEY (`submissionID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `appeal_assignment`
  ADD CONSTRAINT `appeal_assignment_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

ALTER TABLE `appeal_assignment`
  ADD CONSTRAINT `appeal_assignment_ibfk_2` FOREIGN KEY (`markerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;