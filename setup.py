#! /usr/bin/env python

import sys

import re
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

#Copy config.php and .htaccess
subprocess.call('cp -r ./config.php.template ./config.php', shell=True)
#subprocess.call('cp -r ./.htaccess.template ./.htaccess', shell=True)
subprocess.call('cp -r ./.user.ini.template ./.user.ini', shell=True)
replace(".user.ini", "session.save_path", getcwd()+'/sessions');

#Prompt for ROOT URL
root_url = 'ROOT URL: '
#and replace in config.php
replace("config.php", "\$SITEURL", '"'+raw_input(root_url)+'";')

#Make new sqlite database
scriptfilename = 'sqlite/sqliteimport.sql'
samplefile = 'sqlite/sample.sql'
dbfilename = 'NAME OF SQLITE DATABASE: '
dbfilename = raw_input(dbfilename)
replace("config.php", "\$SQLITEDB", '"'+dbfilename+'";')

import sqlite3 as sqlite
 
if __name__ == "__main__":
	#if (len(sys.argv) != 3):
	#	print "\n\tRequires two arguments:"
	#	print "\n\t\tRunSQLiteScript.py {scriptfilename} {databasefilename}\n\n"
	#	sys.exit()
	#scriptfilename = sys.argv[1]
	#dbfilename = sys.argv[2]
 
	try:
		print "\nOpening DB"
		connection = sqlite.connect("sqlite/"+dbfilename+".db")
		cursor = connection.cursor()
		print "Reading Script..."
		scriptFile = open(scriptfilename, 'r')
		script = scriptFile.read()
		scriptFile.close()
		print "Running Script..."
		cursor.executescript(script)
		connection.commit()
		print "Changes successfully committed\n"
	except Exception, e:
		print "Something went wrong:"
		print e
	finally:
		print "\nClosing DB"
		connection.close() 

if __name__ == "__main__":    
    try:
        print "\nOpening DB"
        connection = sqlite.connect("sqlite/"+dbfilename+".db")
        cursor = connection.cursor()
        print "Reading Script..."
        scriptFile2 = open(samplefile, 'r')
        script2 = scriptFile2.read()
        scriptFile2.close()
        print "Running Script..."
        cursor.executescript(script2)
        connection.commit()
        print "Changes successfully committed\n"
    except Exception, e:
        print "Something went wrong:"
        print e
    finally:
        print "\nClosing DB"
        connection.close()

import htpasswd
import getpass
from os import chmod
import stat

user = "Administrator User: "
user = raw_input(user)
password = getpass.getpass("Administrator Password: ")

ht = htpasswd.HtpasswdFile("admin/.htpasswd", create=True)
ht.update(user, password)
ht.save()
chmod('admin/.htpasswd', stat.S_IROTH+stat.S_IRWXU)

print 'All Done. Have Fun!'
