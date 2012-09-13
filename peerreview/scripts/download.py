import zipfile
from io import BytesIO
from api import *
from optparse import OptionParser
import sys

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
parser.add_option("-i", "--userindex", dest="userIndex", help="Replaces user names with their index for file names", action="store_true")
(options, args) = parser.parse_args()
assert(options.assignment)

#Standard init stuff
FileManager("./")
userMgr = UserManager("users.php")
#We only really care about one assignment
assignment = Assignment(options.assignment)
assignment.load();

output = BytesIO()
zf = zipfile.ZipFile(output, mode='w', compression=zipfile.ZIP_DEFLATED)

#Write all the essays in this assignment into the zip
i = 0
for (user, essay) in assignment.essays.items():
    name = user.fancyName
    if(options.userIndex):
        name = "user%d" % i
    zf.writestr(options.assignment + "/" + name +" essay.txt", essay.submission)
    i+=1

zf.close();

#Now print out the header stuff
print "HEADER Content-type: application/octet-stream"
print "HEADER Content-disposition: attachment; filename=%s-essays.zip" % options.assignment
print output.getvalue()
output.close()

