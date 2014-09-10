import mysql.connector as my

DBARGS = {'db':'mta_mining', 'user':'mta_miner', 'host':'localhost'}

def query(sql,*args):
    db = my.connect(**DBARGS)
    c = db.cursor()
    c.execute(sql, args)
    return list(c)

def list_assignments(courseID=3):
    c = query("""
    select assignmentID, name, assignmentType
      from assignments
     where courseID = %s
    """, courseID)
    for (id, name, type) in c:
        if type <> 'peerreview':
            continue
        if name.find("alibr")>=0 or name.find("eprecate")>=0:
            continue
        print "%2d -- %s" % (id, name)

def submission_scores(assignmentID):
    """
    Return list of hashes with attributes `subID`, `reviewScores`,
    `instructorScore`.
    """
    instructors = set(id for (id,) in
                      query("select userID from users where userType in ('instructor', 'marker')"))

    ret = []

    c = query("""
    select submissionID from peer_review_assignment_submissions
     where assignmentID = %s
       and noPublicUse <> 1
    """, assignmentID)
    for (subID,) in c:
        obj = {'subID':subID, 'reviewScores':[]}
        m = query("""
        select mat.reviewerID, ans.questionID, ans.answerInt
          from peer_review_assignment_matches mat
          join peer_review_assignment_review_answers ans on ans.matchID = mat.matchID
         where mat.submissionID = %s
           and ans.answerInt is not null
         order by mat.reviewerID, ans.questionID
        """, subID)
        lastStudent = None
        for (reviewer, question, score) in m:
            if reviewer in instructors:
                if 'instructorScore' not in obj:
                    obj['instructorScore'] = {}
                obj['instructorScore'][question] = float(score)
            else:
                if lastStudent <> reviewer:
                    obj['reviewScores'].append({})
                obj['reviewScores'][-1][question] = float(score)
                lastStudent = reviewer
        ret.append(obj)

    return ret

def mse(submission, centroid=False):
    """
    Return the mean squared deviation of the student reviewers from the
    instructor reviewer, if present.  If no instructor review, then return
    `None` if 'centroid' is `False`, or the mse from the centroid otherwise.
    """
    if 'instructorScore' not in submission:
        if centroid:
            raise "Not implemented yet"
        else:
            return None

    ins = submission['instructorScore']
    devs = []
    for r in submission['reviewScores']:
        devs += [(r[q]-ins[q])**2.0 for q in ins]

    return sum(devs)/len(devs)

def print_scores(assignID_or_name, courseID=3):
    if isinstance(assignID_or_name, int):
        ((name,),) = query("select name from assignments where assignmentID = %s" % assignID_or_name)
        assignID = assignID_or_name
    elif isinstance(assignID_or_name, str):
        ((assignID,),) = query("select assignmentID from assignments where name = '%s' and courseID = %s" % (assignID_or_name, courseID))
        name = assignID_or_name
    else:
        raise ValueError("assignID_or_name must be int or str, not %s" % type(assignID_or_name))
    s = sorted(submission_scores(assignID), key=mse)
    print "Assignment %d -- %s" % (assignID, name)
    print "#  sub   mse score"
    for sub in s:
        if 'instructorScore' in sub:
            print "  %d %.3f %5.1f" % (sub['subID'], mse(sub), sum(sub['instructorScore'].values()))
