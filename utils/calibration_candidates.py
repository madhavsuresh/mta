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

    fame = set(id for (id,) in
               query("select questionID from peer_review_assignment_questions where questionName like '%%all of%%'"))
    ret = []

    c = query("""
    select submissionID from peer_review_assignment_submissions
     where assignmentID = %s
       and noPublicUse <> 1
    """, assignmentID)
    for (subID,) in c:
        obj = {'subID':subID, 'reviewScores':[]}
        m = query("""
        select u.firstName, u.lastName, mat.reviewerID, ans.questionID, ans.answerInt
          from peer_review_assignment_matches mat
          join peer_review_assignment_review_answers ans on ans.matchID = mat.matchID
          join peer_review_assignment_submissions sub on sub.submissionID = mat.submissionID
          join users u on u.userID = sub.authorID
         where mat.submissionID = %s
           and ans.answerInt is not null
         order by mat.reviewerID, ans.questionID
        """, subID)
        lastStudent = None
        for (authorFirst, authorLast, reviewer, question, score) in m:
            if question in fame:
                continue
            if reviewer in instructors:
                if 'instructorScore' not in obj:
                    obj['instructorScore'] = {}
                obj['instructorScore'][question] = float(score)
            else:
                if lastStudent <> reviewer:
                    obj['reviewScores'].append({})
                    obj['author'] = "%s %s" % (authorFirst, authorLast)
                obj['reviewScores'][-1][question] = float(score)
                lastStudent = reviewer
        ret.append(obj)

    return ret

def centroid(submission):
    qs = [q for q in submission['reviewScores'][0]]
    ret = {}
    denom = len(submission['reviewScores'])
    for q in qs:
        mean = sum(r[q] for r in submission['reviewScores'])/denom
        ret[q] = mean
    return ret

def mse(submission, use_centroid=True):
    """
    Return the mean squared deviation of the student reviewers from the
    instructor reviewer, if present.  If no instructor review, then return
    `None` if 'use_centroid' is `False`, or the mse from the centroid otherwise.
    """
    if 'instructorScore' in submission:
        ins = submission['instructorScore']
    elif use_centroid:
        ins = centroid(submission)
    else:
        return None

    devs = []
    for r in submission['reviewScores']:
        devs += [(r[q]-ins[q])**2.0 for q in ins]

    return sum(devs)/len(devs)

def print_scores(assignID_or_name, courseID=3, use_centroid=True):
    if isinstance(assignID_or_name, int):
        ((name,),) = query("select name from assignments where assignmentID = %s" % assignID_or_name)
        assignID = assignID_or_name
    elif isinstance(assignID_or_name, str):
        ((assignID,),) = query("select assignmentID from assignments where name = '%s' and courseID = %s" % (assignID_or_name, courseID))
        name = assignID_or_name
    else:
        raise ValueError("assignID_or_name must be int or str, not %s" % type(assignID_or_name))
    s = sorted(submission_scores(assignID), key=lambda s: mse(s, use_centroid))
    print "Assignment %d -- %s" % (assignID, name)
    print "#   mse score  sub"
    for sub in s:
        if 'instructorScore' in sub:
            print "  %.3f %5.1f %d \t%s \t(w/ins)" % (mse(sub), sum(sub['instructorScore'].values()), sub['subID'], sub['author'])
        elif use_centroid:
            print "  %.3f %5.1f %d \t%s" % (mse(sub), sum(centroid(sub).values()), sub['subID'], sub['author'])
            
