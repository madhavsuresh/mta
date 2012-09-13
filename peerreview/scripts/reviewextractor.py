from api import *
from optparse import OptionParser
import StringIO

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
parser.add_option("-q", "--question", dest="question", type="int", help="The review question to extract")
parser.add_option("-i", "--instructor", dest="instructor", help="If instructor related responses should be displayed", action="store_true")
(options, args) = parser.parse_args()
assert(options.assignment)
assert(options.question)

#Standard init stuff
FileManager("./")
userMgr = UserManager("users.php")
#We only really care about one assignment
assignment = Assignment(options.assignment)
assignment.load();

#this is a beasty list comprehension that just figues out the max number of reviews that a person got
if options.instructor:
    colspan = max([len([review for review in assignment.essays[user].reviews.values() if review.reviewer.name in userMgr.users.keys()]) for user in userMgr.usersOrdered])
else:
    colspan = max([len([review for review in assignment.essays[user].reviews.values() if review.reviewer.name in userMgr.users.keys()]) for user in userMgr.usersOrdered if user in assignment.essays.keys()])


fp = StringIO.StringIO()
fp.write("<!--HTML-->")
fp.write("<table width='100%'>")
fp.write("<tr><td><h2>User</h2></td><td colspan='%d'><h2 style='text-align:center'>Responses to Question %d</td></tr>" % (colspan, options.question))
i = 0
for user in userMgr.usersOrdered:
    if user not in assignment.essays.keys() or user in assignment.deniedUsers:
        continue

    fp.write("<tr class='tableRow%d'><td>%s</td>" % (i%2, user.fancyName))
    #We now need to go and extract all the responses that this essay got
    count = colspan
    for review in assignment.essays[user].reviews.values():
        if not options.instructor and not review.reviewer.name in userMgr.users.keys():
            continue
        fp.write("<td>" + review.responses[options.question] + "</td>")
        count -= 1
    while count > 0:
        fp.write("<td></td>")
        count-=1
    fp.write("</tr>")
    i += 1
fp.write("</table>")

print fp.getvalue()

