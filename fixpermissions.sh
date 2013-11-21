#!/usr/bin/env bash

find ./ -name "*.png" -not -path "./.git*" -exec chmod a+r {} \;
find ./ -name "*.jpg" -not -path "./.git*" -exec chmod a+r {} \;
find ./ -name "*.css"  -not -path "./.git*" -exec chmod a+r {} \;
find ./ -name ".user.ini" -not -path "./.git*" -exec chmod a+r {} \;
find ./ -name ".htaccess" -not -path "./.git*" -exec chmod a+r {} \;
find ./ -name ".htpasswd" -not -path "./.git*" -exec chmod a+r {} \;
find ./ -name "*.php" -not -path "./.git*" -exec chmod a+rx {} \;
find ./ -name "*.js" -not -path "./.git*" -exec chmod a+rx {} \;
find ./ -name "*.gif" -not -path "./.git*" -exec chmod a+rx {} \;
find ./ -type d -not -path "./.git*" -exec chmod a+rx {} \;

chmod 777 sessions
chmod a+r favicon.ico
