#!/bin/sh
CWD=`dirname $0`

# Fetch changes without overwriting local
git pull --rebase

echo Fixing directory permissions...
find $CWD -type d -exec chmod a+rx '{}' \;

echo Fixing file permissions...
chmod -R a+r $CWD

echo OK
