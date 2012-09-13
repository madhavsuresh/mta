from api import *
from optparse import OptionParser

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
(options, args) = parser.parse_args()
assert(options.assignment)

#Standard init stuff
FileManager("./")
userMgr = UserManager("users.php")
#We only really care about one assignment
assignment = Assignment(options.assignment)
assignment.load();

#Now go through all the essays, and do the word count
counts = [(essay.author, len(essay.submission.split(None))) for essay in assignment.essays.values()]

counts.sort(key=lambda (x,v): v, reverse=True)

i = 0
print "<!--HTML-->"
print "<table width='100%'>"
print "<tr><td><h2>Username</h2></td><td><h2 style='text-align:center'>Word Count</td></tr>"
for (user, count) in counts:
    print "<tr class='tableRow%d'><td>" % (i%2), user.fancyName,"</td><td style='text-align:center'>", count ,"</td></tr>"
    i += 1
print "</table>"
