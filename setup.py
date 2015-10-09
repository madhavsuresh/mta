#! /usr/bin/env python

import sys

import re
from tempfile import mkstemp
from shutil import move
from os import remove, close, getcwd, chmod, popen, system, path
import subprocess
import getpass
import random

try:
    import crypt
except ImportError:
    try:
        import fcrypt as crypt
    except ImportError:
        sys.stderr.write("Cannot find a crypt module.  "
                         "Possibly http://carey.geek.nz/code/python-fcrypt/\n")
        sys.exit(1)

def salt():
    """Returns a string of 2 randome letters"""
    letters = 'abcdefghijklmnopqrstuvwxyz' \
              'ABCDEFGHIJKLMNOPQRSTUVWXYZ' \
              '0123456789/.'
    return random.choice(letters) + random.choice(letters)

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

#Prompt for ROOT URL
root_url = raw_input('ROOT URL: ')
#and replace in config.php
replace("config.php", "\$SITEURL", '"'+root_url+'";')

#Make new sqlite database
scriptfilename = 'sqlite/sqliteimport.sql'
samplefile = 'sqlite/sample.sql'
dbfilename = raw_input('NAME OF SQLITE DATABASE:') or 'mta_sqlite_db'
if re.search('(\.db$)', dbfilename):
	dbfilename = re.search('(.+?)\.db$', dbfilename).group(1)
if re.search('(\.sqlite$)', dbfilename):
	dbfilename = re.search('(.+?)\.sqlite$', dbfilename).group(1)
print dbfilename+'.db created in sqlite directory'

replace("config.php", "\$SQLITEDB", '"'+dbfilename+'";')

import sqlite3 as sqlite
 
try:
	print "\nOpening DB"
	connection = sqlite.connect("sqlite/"+dbfilename+".db")
	cursor = connection.cursor()
	scriptFile = open(scriptfilename, 'r')
	script = scriptFile.read()
	scriptFile.close()
	cursor.executescript(script)
	connection.commit()
	print "Database Schema created..."
        scriptFile2 = open(samplefile, 'r')
        script2 = scriptFile2.read()
        scriptFile2.close()
	cursor.executescript(script2)
	connection.commit()
	print "Sample Data inserted successfully..."
except Exception, e:
	print "Something went wrong:"
	print e
finally:
	print "Closing DB\n"
	connection.close()

#if system("wget -O- https://www.cs.ubc.ca/~mglgms/mta/TEST100/login.php &> /dev/null"):
#error page
subprocess.call('cp -r ./.htaccess.template ./.htaccess', shell=True)
stuff = re.search('\S*\.[a-zA-Z]+(\/\S*)', root_url)
if stuff:
	replace2('.htaccess', 'RewriteBase', stuff.group(1))

f = open('fetch_target.html','w')
f.write('<html>\n<body>\n<h1>TESTING</h1>\n</body>\n</html>\n'); # python will convert \n to os.linesep
f.close() # you can omit in most cases as the destructor will call if
f = open('redirect_target.html','w')
f.close()

with open("./.htaccess", "a") as myfile:
    myfile.write("RewriteRule ^redirect_target.html$ $2fetch_target.html [L]")

chmod('./.htaccess', 0644)
chmod('fetch_target.html', 0644)
chmod('redirect_target.html', 0644)

system("wget -O- %s/redirect_target.html >out.txt 2> /dev/null" % root_url)
import filecmp
if filecmp.cmp('out.txt', 'fetch_target.html'):
	print 'htaccess works. Hurray!'
else:
	remove('.htaccess')
remove('out.txt')

user = raw_input("Administrator User: ") or "admin"
print "Administrator '"+user+"' created";
while True:
	password = getpass.getpass("Administrator Password: ")
	password_match = getpass.getpass("Re-type Administrator Password: ")
	if password == password_match:
		break	
	print 'Password was re-typed incorrectly'

entries = []
if path.exists('admin/.htpasswd'):
	lines = open('admin/.htpasswd', 'r').readlines()
        for line in lines:
        	username, pwhash = line.split(':')
        	entry = [username, pwhash.rstrip()]
        	entries.append(entry)
passwordhash = crypt.crypt(password, salt())
matching_entries = [entry for entry in entries
                    if entry[0] == user]
if matching_entries:
	matching_entries[0][1] = passwordhash
else:
	entries.append([user, passwordhash])
open('admin/.htpasswd', 'w').writelines(["%s:%s\n" % (entry[0], entry[1])
                                     for entry in entries])
subprocess.call('cp -r admin/.htaccess.template admin/.htaccess', shell=True)
replace2('admin/.htaccess', 'AuthUserFile', getcwd()+'/admin/.htpasswd')

chmod('admin/.htpasswd', 0644)
chmod('admin/.htaccess', 0644)

print 'All Done. Have Fun!'
