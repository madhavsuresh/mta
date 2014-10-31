CREATE TABLE IF NOT EXISTS `appeal_assignment` (
  `appealMessageID` int(11) NOT NULL,
  `submissionID` int(11) NOT NULL,
  `markerID` int(11) NOT NULL,
  PRIMARY KEY (`appealMessageID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `appeal_assignment`
  ADD CONSTRAINT `appeal_assignment_ibfk_1` FOREIGN KEY (`appealMessageID`) REFERENCES `peer_review_assignment_appeal_messages` (`appealMessageID`) ON DELETE CASCADE;

ALTER TABLE `appeal_assignment`
  ADD CONSTRAINT `appeal_assignment_ibfk_2` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

ALTER TABLE `appeal_assignment`
  ADD CONSTRAINT `appeal_assignment_ibfk_3` FOREIGN KEY (`markerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;