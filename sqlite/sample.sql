PRAGMA synchronous = OFF;
PRAGMA journal_mode = MEMORY;
BEGIN TRANSACTION;
INSERT INTO "course" ("name", "displayName", "authType", "registrationType", "browsable") VALUES ("TEST100" ,"Example Course", "pdo", "open", "1");
INSERT INTO "users" ("userType", "courseID", "firstName", "lastName", "username", "studentID", "markingLoad") VALUES ("instructor", (SELECT courseID from course WHERE name = 'TEST100'), 'Valerie', 'Frizzle', 'Frizzle', '1', '1');
INSERT INTO "user_passwords" ("username", "passwordHash") VALUES ("Frizzle", '536e00d2f14fb818e9a905dd493cfa886604f2b4');

INSERT INTO "users" ("userType", "courseID", "firstName", "lastName", "username", "studentID") VALUES ("student", (SELECT courseID from course WHERE name = 'TEST100'), 'Phoebe', 'Terese', 'Phoebe', '11111111');
INSERT INTO "user_passwords" ("username", "passwordHash") VALUES ("Phoebe", '536e00d2f14fb818e9a905dd493cfa886604f2b4');
INSERT INTO "users" ("userType", "courseID", "firstName", "lastName", "username", "studentID") VALUES ("student", (SELECT courseID from course WHERE name = 'TEST100'), 'Ralphie', 'Tenelli', 'Ralphie', '22222222');
INSERT INTO "user_passwords" ("username", "passwordHash") VALUES ("Ralphie", '536e00d2f14fb818e9a905dd493cfa886604f2b4');
INSERT INTO "users" ("userType", "courseID", "firstName", "lastName", "username", "studentID") VALUES ("student", (SELECT courseID from course WHERE name = 'TEST100'), 'Carlos', 'Ramon', 'Carlos', '33333333');
INSERT INTO "user_passwords" ("username", "passwordHash") VALUES ("Carlos", '536e00d2f14fb818e9a905dd493cfa886604f2b4');
INSERT INTO "users" ("userType", "courseID", "firstName", "lastName", "username", "studentID") VALUES ("student", (SELECT courseID from course WHERE name = 'TEST100'), 'Dorothy Ann', 'Unknown', 'Dorothy Ann', '44444444');
INSERT INTO "user_passwords" ("username", "passwordHash") VALUES ("Dorothy Ann", '536e00d2f14fb818e9a905dd493cfa886604f2b4');

END TRANSACTION;