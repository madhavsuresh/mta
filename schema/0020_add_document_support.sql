--
-- Table structure for table `peer_review_assignment_document`
-- Added 15/2/2017 NUIT A&RT
-- Jacob Collins
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_document` (
	`submissionID`	INTEGER,
	`document`	BLOB NOT NULL,
	PRIMARY KEY(`submissionID`),
	FOREIGN KEY(`submissionID`) REFERENCES `peer_review_assignment_submissions`(`submissionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `peer_review_assignment_document_settings`
-- Added 15/2/2017 NUIT A&RT
-- Jacob Collins
--

CREATE TABLE IF NOT EXISTS `peer_review_assignment_document_settings` (
	`assignmentID`	INTEGER,
	`documentExtension`	varchar(10) NOT NULL DEFAULT '',
	PRIMARY KEY(`assignmentID`),
	FOREIGN KEY(`assignmentID`) REFERENCES `peer_review_assignment`(`assignmentID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
