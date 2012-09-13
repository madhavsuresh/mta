import zipfile
from io import BytesIO
from api import *
from optparse import OptionParser
import sys
import os 

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
(options, args) = parser.parse_args()
assert(options.assignment)

output = BytesIO()
zf = zipfile.ZipFile(output, mode='w', compression=zipfile.ZIP_DEFLATED)

#Write all the essays in this assignment into the zip
for root, _, files in os.walk('./%s' % options.assignment):
    for f in files:
        f = os.path.join(root, f)
        zf.write(f);

zf.close();

#Now print out the header stuff
print "HEADER Content-type: application/octet-stream"
print "HEADER Content-disposition: attachment; filename=%s-backup.zip" % options.assignment
print output.getvalue()
output.close()

