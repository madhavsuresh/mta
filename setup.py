#! /usr/bin/env python

import sys

import re
from tempfile import mkstemp
from shutil import move
from os import remove, close, getcwd, chmod, system
import subprocess
import htpasswd
import getpass

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

def replace2(file_path, pattern, subst):
    #Create temp file
    fh, abs_path = mkstemp()
    new_file = open(abs_path,'wb')
    old_file = open(file_path)
    for line in old_file:
        x = re.findall('^'+pattern+'\s*(\S*)', line)
	if re.search('#### To use LDAP authentication', line):
	    break
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
subprocess.call('cp -r ./.user.ini.template ./.user.ini', shell=True)
replace(".user.ini", "session.save_path", getcwd()+'/sessions');
subprocess.call('cp -r ./.user.ini peerreview/.user.ini', shell=True)
subprocess.call('cp -r ./.user.ini grouppicker/.user.ini', shell=True)
system("wget -O- https://www.cs.ubc.ca/~mglgms/mta/test.html > wget.out 2> wget.err")

subprocess.call('cp -r ./.htaccess.template ./.htaccess', shell=True)

#Prompt for ROOT URL
root_url = raw_input('ROOT URL: ')
#and replace in config.php
replace("config.php", "\$SITEURL", '"'+root_url+'";')
stuff = re.search('\S*\.[a-zA-Z]+(\/\S*)', root_url)
if stuff:
	replace2(".htaccess", 'RewriteBase', stuff.group(1))

#Make new sqlite database
scriptfilename = 'sqlite/sqliteimport.sql'
samplefile = 'sqlite/sample.sql'
dbfilename = raw_input('NAME OF SQLITE DATABASE: ')
replace("config.php", "\$SQLITEDB", '"'+dbfilename+'";')

import sqlite3 as sqlite
 
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
	print "New schema created..."
	print "Reading sample data script..."
        scriptFile2 = open(samplefile, 'r')
        script2 = scriptFile2.read()
        scriptFile2.close()
	print "Running Script..."
	cursor.executescript(script2)
	connection.commit()
	print "Changes successfully committed"
except Exception, e:
	print "Something went wrong:"
	print e
finally:
	print "\nClosing DB"
	connection.close()

user = "Administrator User: "
user = raw_input(user)
password = getpass.getpass("Administrator Password: ")

ht = htpasswd.HtpasswdFile("admin/.htpasswd", create=True)
ht.update(user, password)
ht.save()

subprocess.call('cp -r admin/.htaccess.template admin/.htaccess', shell=True)
replace2('admin/.htaccess', 'AuthUserFile', getcwd()+'/admin/.htpasswd')

chmod('./.htaccess', 0777)
chmod('admin/.htpasswd', 0777)
chmod('admin/.htaccess', 0777)
#chmod a+r .htaccess
#chmod a+r admin/.htpasswd
#chmod a+r admin/.htaccess

print 'All Done. Have Fun!'
