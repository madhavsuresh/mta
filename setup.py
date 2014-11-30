#! /usr/bin/env python

import re
import shutil
from tempfile import mkstemp
from shutil import move
from os import remove, close

def replace(file_path, pattern, subst):
    #Create temp file
    fh, abs_path = mkstemp()
    new_file = open(abs_path,'wb')
    old_file = open(file_path)
    for line in old_file:
	x = re.findall('^\$SITEURL="(\S*)";', line)	
	if len(x) > 0:
		new_file.write(line.replace(x[0], subst))
	else:
		new_file.write(line)

    #close temp file
    new_file.close()
    close(fh)
    old_file.close()
    #Remove original file
    remove(file_path)
    #Move new file
    move(abs_path, file_path)

replace("config.php", "$SITEURL", "GOO")

print 'Hello, world!'
