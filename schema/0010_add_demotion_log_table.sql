CREATE TABLE IF NOT EXISTS `peer_review_assignment_demotion_log` (
  `userID` int(11) NOT NULL,
  `demotionDate` datetime NOT NULL,
  `demotionThreshold` float NOT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;