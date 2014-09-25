CREATE TABLE IF NOT EXISTS `demotion_log` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `demotionDate` datetime NOT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;