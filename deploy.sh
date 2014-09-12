#!/bin/sh
$CWD=`dirname $0`

# Fetch changes without overwriting local
git pull

# fix directory permissions
find $CWD -type d -exec chmod a+rx '{}' \;

# fix file permissions
chmod -R a+r $CWD
