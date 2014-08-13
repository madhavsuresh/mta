ALTER TABLE `peer_review_assignment_submissions` ADD `submissionTimestamp` DATETIME NOT NULL ;
ALTER TABLE `peer_review_assignment_submission_marks` ADD `submissionMarkTimestamp` DATETIME NOT NULL ;
ALTER TABLE `peer_review_assignment_review_answers` ADD `reviewTimestamp` DATETIME NOT NULL ;
ALTER TABLE `peer_review_assignment_review_marks` ADD `reviewMarkTimestamp` DATETIME NOT NULL ;