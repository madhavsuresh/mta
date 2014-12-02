#! /usr/bin/env python

import sys

import re
import shutil
from tempfile import mkstemp
from shutil import move
from os import remove, close

import subprocess

#Copy config.php and .htaccess
subprocess.call('cp -r ./config.php.template ./config.php', shell=True)
#subprocess.call('cp -r ./.htaccess.template ./.htaccess', shell=True)

def replace(file_path, pattern, subst):
    #Create temp file
    fh, abs_path = mkstemp()
    new_file = open(abs_path,'wb')
    old_file = open(file_path)
    for line in old_file:
	x = re.findall('^'+pattern+'="(\S*)";', line)	
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

#Prompt for ROOT URL
root_url = 'ROOT URL: '
#and replace in config.php
replace("config.php", "\$SITEURL", raw_input(root_url))

#Make new sqlite database
scriptfilename = 'sqlite/sqliteimport.sql'
dbfilename = 'NAME OF DATABASE: '
dbfilename = raw_input(dbfilename)
replace("config.php", "\$SQLITEDB", dbfilename)

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

import htpasswd
import getpass

user = "Administrator User: "
user = raw_input(user)
password = getpass.getpass("Administrator Password: ")

ht = htpasswd.HtpasswdFile("admin/.htpasswd", create=True)
ht.update(user, password)
ht.save()

print 'Hello, world!'
