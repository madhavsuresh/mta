from os import getcwd
import re
import shutil
from tempfile import mkstemp
from shutil import move
from os import remove, close, getcwd
import subprocess

def replace(file_path, pattern, subst):
    #Create temp file
    fh, abs_path = mkstemp()
    new_file = open(abs_path,'wb')
    old_file = open(file_path)
    for line in old_file:
	x = re.findall('^'+pattern+'\s*=\s*(\S*)', line)	
	if len(x) > 0:
		new_file.write(line.replace(x[0], subst))
		print '1 - '+line.replace(x[0], subst)
	else:
		new_file.write(line)
		print '0 - '+line

    #close temp file
    new_file.close()
    close(fh)
    old_file.close()
    #Remove original file
    remove(file_path)
    #Move new file
    move(abs_path, file_path)

subprocess.call('cp -r ./.user.ini.template ./.user.ini', shell=True)
replace(".user.ini", "session.save_path", getcwd());
