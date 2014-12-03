import subprocess

subprocess.call(['rm','-r','admin/.htpasswd'])
subprocess.call(['rm','-r','sqlite/testdb.db'])
subprocess.call(['rm','-r','.user.ini'])
subprocess.call(['rm','-r','config.php'])
#subprocess.call(['rm','-r','/.htaccess'])
#subprocess.call(['rm','-r','/admin/.htaccess'])
