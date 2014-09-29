ALTER TABLE `peer_review_assignment_matches` CHANGE `calibrationState` `oldCalibrationState` TINYINT(1);
ALTER TABLE `peer_review_assignment_matches` ADD `calibrationState` enum('none','key','attempt','covert') NOT NULL DEFAULT 'none';
UPDATE `peer_review_assignment_matches` SET `calibrationState` = 'none' WHERE `oldCalibrationState` = 0;
UPDATE `peer_review_assignment_matches` SET `calibrationState` = 'key' WHERE `oldCalibrationState` = 1;
UPDATE `peer_review_assignment_matches` SET `calibrationState` = 'attempt' WHERE `oldCalibrationState` = 2;