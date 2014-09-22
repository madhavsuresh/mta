ALTER TABLE `users` ADD `markingLoad` float NOT NULL;
UPDATE users SET markingLoad = "1" WHERE users.userType = "marker";