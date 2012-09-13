from api import *
from optparse import OptionParser
import random

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
parser.add_option("-n", "--numreviews", dest="numreviews", help="The number of reviews to give to each person", type="int", default = 2)
parser.add_option("-c", "--clobber", dest="clobber", help="Creates a new assignment from scratch, rather then working with what is already saved", action="store_true")
(options, args) = parser.parse_args()
assert(options.assignment)

#Standard init stuff
FileManager("./")
userMgr = UserManager("users.php")
#We only really care about one assignment
assignment = Assignment(options.assignment)
assignment.load();

if(options.clobber):
    assignment.reviewAssignments = {}

assert(assignment.reviewAssignments == {}) #We don't support incremental updates yet

independents = []
dependents = []

#So we need to figure out who was reviewed properly and what not
for author, essay in assignment.essays.items():
    if author in assignment.deniedUsers:
        continue
    if author in assignment.independentUsers:
        independents.append(author)
    else:
        dependents.append(author)

#And select some random number of essays to work with
random.shuffle(independents)
random.shuffle(dependents)

#Now, we need to make the pairings

def createPairings(users):
    assert(len(users) > options.numreviews)
    l2 = map(lambda x: (x,), users)
    for i in xrange(options.numreviews-1):
        l2 = l2[1:] + [l2[0]]
        l2 = map(lambda (x,y) : [x] + list(y), zip(users, l2))
    return l2



def createAssignments(users):
    pairings = createPairings(users)
    d = {}
    for user, assignment in zip([users[-1]] + users[0:-1], pairings):
        d[user] = assignment
    return d

assignments = {}
assignments.update(createAssignments(independents))
assignments.update(createAssignments(dependents))
assignment.reviewAssignments = assignments

assignment.saveReviewerAssignments()

print "Assignments saved"
