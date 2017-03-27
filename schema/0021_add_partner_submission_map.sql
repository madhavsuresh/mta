
--
-- Table structure for table `peer_review_partner_submission_map`
-- Added 23/3/2017
-- Madhav Suresh
--


CREATE TABLE IF NOT EXISTS `peer_review_partner_submission_map` (
	`submissionID` INTEGER,
	`submissionOwnerID` INTEGER,
	`submissionPartnerID` INTEGER,
	PRIMARY KEY(`submissionID`),
	FOREIGN KEY(`submissionID`) REFERENCES `peer_review_assignment_submissions`(`submissionID`) ON DELETE CASCADE,
	FOREIGN KEY(`submissionOwnerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
	FOREIGN KEY(`submissionPartnerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE
	);

