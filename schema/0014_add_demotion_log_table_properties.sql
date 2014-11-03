--
-- Constraints for table `peer_review_assignment_demotion_log`
--
ALTER TABLE `peer_review_assignment_demotion_log`
  ADD CONSTRAINT `peer_review_assignment_demotion_log_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

ALTER TABLE `peer_review_assignment_demotion_log` ADD INDEX `userID` (`userID`)