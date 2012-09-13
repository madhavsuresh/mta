from api import *
from optparse import OptionParser

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
parser.add_option("-s", "--minscore", dest="minscore", help="The minimum score needed to be good", type="int", default=2)
parser.add_option("-n", "--numconsecutive", dest="numconsecutive", help="The number of consecutive good reviews to be independent", type="int", default=2)
(options, args) = parser.parse_args()
assert(options.assignment)

#Standard init stuff
FileManager("./")
userMgr = UserManager("users.php")
assignMgr = AssignmentManager("assignmentlist.php")

baseAssignment = assignMgr.assignments[options.assignment]

assignList = []
#Now, we need to look at all the assignments that have already had their marks posted (but before this one is posted)
for assignment in assignMgr.assignmentsOrdered:
    if baseAssignment.essayStopDate >= assignment.markPostDate:
        assignList.append(assignment)

assignList.sort(key=lambda x: x.essayStopDate, reverse=True)

#Now, we need to go though each user to see if they are independent
baseAssignment.independentUsers = []

for user in userMgr.users.values():
    if user in assignment.deniedUsers:
        continue
    reviewMarks = []
    for assignment in assignList:
        try:
            revs = user.reviews[assignment].values()
            for rev in revs:
                reviewMarks.append(rev.mark.score >= options.minscore - 1e-4)
        except KeyError:
            pass

    #Now, make sure we have enough good reviews
    if sum(reviewMarks[0:options.numconsecutive]) == options.numconsecutive:
        baseAssignment.independentUsers.append(user)
        print user.fancyName

baseAssignment.saveDeniedIndependentUsers()
