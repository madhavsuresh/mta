ALTER TABLE `job_notifications`
  ADD CONSTRAINT `job_notifications_ibfk_1` FOREIGN KEY (`assignmentID`) REFERENCES `assignments` (`assignmentID`) ON DELETE CASCADE;

ALTER TABLE `peer_review_assignment_images`
  ADD CONSTRAINT `peer_review_assignment_images_ibfk_1` FOREIGN KEY (`submissionID`) REFERENCES `peer_review_assignment_submissions` (`submissionID`) ON DELETE CASCADE;

