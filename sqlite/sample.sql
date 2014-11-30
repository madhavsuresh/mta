PRAGMA synchronous = OFF;
PRAGMA journal_mode = MEMORY;
BEGIN TRANSACTION;
INSERT INTO "course" ("name", "displayName", "authType", "registrationType", "browsable") VALUES ("TEST100" ,"Example Course", "pdo", "open", "1");
INSERT INTO "user" ("userType", "courseID", "firstName", "lastName", "username", "studentID", "alias") VALUES ("instructor", (SELECT courseID from course WHERE );

END TRANSACTION;
