import os.path
import datetime
from fabric.api import *

env.hosts = ['cs430@kunghit.ugrad.cs.ubc.ca']
mysql_dumpfile = datetime.date.today().strftime('mtadump-%Y%b%d.sql').lower()

@task
def load_dump():
    with lcd(os.path.dirname(os.path.abspath(env.real_fabfile))):
        if not os.path.exists(env.lcwd + "/" + mysql_dumpfile):
            dump_mysql()
            get(mysql_dumpfile, mysql_dumpfile)
        local("mysql --user=mta mta --password=mta < %s" % mysql_dumpfile)
        local("mysql --user=mta mta --password=mta < local.sql")

@task
def dump_mysql(force = False):
    if force:
        run('mysqldump --socket ~/mysql/tmp/mysql.sock.cs430 -u root -p mta > %s' % mysql_dumpfile)
    else:
        run('if [ ! -e %s ]; then mysqldump --socket ~/mysql/tmp/mysql.sock.cs430 -u root -p mta > %s; fi' % (mysql_dumpfile, mysql_dumpfile))
