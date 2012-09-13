<?php

#The base location of the site
$SITEURL="http://localhost/~chris/mta/";

$SITEMASTER="cwthornt@cs.ubc.ca";

#What theme you're going to be using
$MTA_THEME="oceania";

#Specify the data manager
$MTA_DATAMANAGER="pdo";
    #Data manager settings
    $MTA_DATAMANAGER_PDO_CONFIG["dsn"] = "mysql:host=localhost;dbname=mta";
    $MTA_DATAMANAGER_PDO_CONFIG["username"] = "mta";
    $MTA_DATAMANAGER_PDO_CONFIG["password"] = "mta";

//What type of assignments have been installed?
$MTA_ASSIGNMENTS = array(
    "peerreview",
    "grouppicker",
);


?>
