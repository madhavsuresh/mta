ALTER TABLE `peer_review_assignment` ADD `calibrationStartDate` datetime NOT NULL;
UPDATE `peer_review_assignment` SET `calibrationStartDate` = `submissionStartDate`;
ALTER TABLE `peer_review_assignment` ADD `calibrationStopDate` datetime NOT NULL;
UPDATE `peer_review_assignment` SET `calibrationStopDate` = `markPostDate`;