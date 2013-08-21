#!/usr/bin/env bash

find ./ -name "*.png" -not -path "./.git*" -exec chmod +r {} \;
find ./ -name "*.css"  -not -path "./.git*"-exec chmod +r {} \;
find ./ -name ".user.ini" -not -path "./.git*" -exec chmod +r {} \;
find ./ -name ".htaccess" -not -path "./.git*" -exec chmod +r {} \;
find ./ -name ".htpasswd" -not -path "./.git*" -exec chmod +r {} \;
find ./ -name "*.php" -not -path "./.git*" -exec chmod a+rx {} \;
find ./ -name "*.js" -not -path "./.git*" -exec chmod a+rx {} \;
find ./ -type d -not -path "./.git*" -exec chmod a+rx {} \;

chmod 777 sessions
chmod a+r favicon.ico
