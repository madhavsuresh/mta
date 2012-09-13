from api import *
from optparse import OptionParser
import random
import time

parser = OptionParser()
parser.add_option("-a", "--assignment", dest="assignment", help="The assignment to work on")
parser.add_option("-d", "--autoappealdiff", dest="autoappealdiff", help="The difference between the min/max of all reviewer's scores for an auto appeal", type="int", default=2)
parser.add_option("-s", "--defaultscore", dest="defaultscore", help="The default score to give to a review", type="int", default=2)
parser.add_option("-p", "--spotcheckpercent", dest="spotcheckpercent", help="Randomly selects the given percentage for spot checking", type="float", default = 0.3)
(options, args) = parser.parse_args()
assert(options.assignment)

#Standard init stuff
FileManager("./")
userMgr = UserManager("users.php")
#We only really care about one assignment
assignment = Assignment(options.assignment)
assignment.load();

missingReviews = []
notIndependentReviewed = []
autoAppeals = []
autoGraded = []

#So we need to figure out who was reviewed properly and what not
for author, essay in assignment.essays.items():
    if author in assignment.deniedUsers:
        continue

    #We don't care about marked things
    if essay.mark:
        continue

    #First, we need to figure out if this reviewer got all of their assigned reviews
    if not all([reviewer in assignment.reviews[author].keys() for reviewer in assignment.reviewLookup[author]]):
        missingReviews.append(author)
        continue

    #Now, were they independently reviewed
    if not all([reviewer in assignment.independentUsers for reviewer in assignment.reviewLookup[author]]):
        notIndependentReviewed.append(author)
        continue

    scores = [assignment.reviews[author][reviewer].score for reviewer in assignment.reviewLookup[author] if (reviewer in userMgr.users.values() or reviewer in userMgr.autoUsers.values()) ]
    if (max(scores) - min(scores)) > options.autoappealdiff:
        autoAppeals.append(author)
        continue

    #If we get here, we can automatically mark the essay!
    essay.setMark(Mark(score = sum(scores)/len(scores), comments="This essay was marked automatically, but it may have been spot checked!"))
    for reviewer in assignment.reviewLookup[author]:
        assignment.reviews[author][reviewer].setMark(Mark(score = options.defaultscore))
    autoGraded.append(author)

#Nowe, save all the marks
assignment.saveMarks()
#And select some random number of essays to work with
random.seed(time.time())
random.shuffle(autoGraded)

print "Missing Reviews:\n"
print "\n".join([x.fancyName for x in missingReviews])
print "\n\nNot Independently Reviewed:\n"
print "\n".join([x.fancyName for x in notIndependentReviewed])
print "\n\nAuto Appeals:\n"
print "\n".join([x.fancyName for x in autoAppeals])
print "\n\nSpot Checks:\n"
print "\n".join([x.fancyName for x in autoGraded[0:int(len(autoGraded) * options.spotcheckpercent)]])

