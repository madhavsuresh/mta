CREATE TABLE IF NOT EXISTS `course_configuration` (
  `courseID` int(11) NOT NULL,
  `windowSize` int(11) NOT NULL,
  `numReviews` int(11) NOT NULL,
  `scoreNoise` float NOT NULL,
  `maxAttempts` int(11) NOT NULL, 
  `numCovertCalibrations` int(11) NOT NULL,
  `exhaustedCondition` enum('extrapeerreview','error') NOT NULL,
  `minReviews` int(11) NOT NULL, 
  `spotCheckProb` float NOT NULL, 
  `spotCheckThreshold` float NOT NULL, 
  `highMarkBias` float NOT NULL, 
  `calibThreshold` float NOT NULL, 
  `calibBias` float NOT NULL,
  PRIMARY KEY (`courseID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `course_configuration`
  ADD CONSTRAINT `course_configuration_ibfk_1` FOREIGN KEY (`courseID`) REFERENCES `course` (`courseID`) ON DELETE CASCADE;