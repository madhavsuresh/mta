<?php
require_once("inc/common.php");
require_once("inc/datamanager.php");
require_once("peerreview/inc/datamanagers/pdoassignmentdatamanager.php");
require_once("config.php");
function populate_submissions($assignment_id, $text){
    echo "i got called";
    return 0;
}

echo "hello world";
$db = $dataMgr->getDatabase();
#$db = new PDO($MTA_DATAMANGER_PDO_CONFIG["dsn"], $MTA_DATAMANAGER_PDO_CONFIG["username"],
                              #                  $MTA_DATAMANAGER_PDO_CONFIG["password"],
                               #                 array(PDO::ATTR_PERSISTENT => true));
$counter = 0;
$sh = $db->prepare("SELECT * FROM users; ");
$sh->execute();
$size = $sh->fetchall();
$size = count($size);
print_r($size);
$sh = $db->prepare("SELECT userID FROM users WHERE userType = 'student';");
$sh->execute();
$students = $sh->fetchall();
print_r($students);

foreach ($students as $student){
    $sh= $db->prepare("Insert INTO peer_review_assignment_submissions (assignmentID, authorID, noPublicUse, submissionTimestamp) Values(?,?,?, ".$dataMgr->from_unixtime("?").");");
    $sh->execute(array(1,$student->userID, 0, $NOW ));
    echo $student->userID;

}




$sh = $db->prepare("INSERT INTO peer_review_assignment_submissions (assignmentID, authorID, noPublicUse, submissionTimestamp) VALUES(?,?,?,?)");
# echo $db;





?>



