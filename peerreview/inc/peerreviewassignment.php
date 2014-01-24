<?php
require_once(dirname(__FILE__)."/common.php");
require_once("inc/assignment.php");
require_once("reviewquestions.php");

class PeerReviewAssignment extends Assignment
{
    public $submissionQuestion = "";

    public $submissionStartDate    = 0;
    public $submissionStopDate     = 0;
    public $reviewStartDate   = 0;
    public $reviewStopDate    = 0;
    public $markPostDate      = 0;
    public $appealStopDate    = 0;

    public $showMarksForReviewsReceived   = false;
    public $showOtherReviewsByStudents    = false;
    public $showOtherReviewsByInstructors = false;
    public $showMarksForOtherReviews      = false;
    public $showMarksForReviewedSubmissions  = false;
    public $showPoolStatus = false;
    
    public $maxSubmissionScore = 0;
    public $maxReviewScore = 0;
    public $defaultNumberOfReviews = 3;
    public $allowRequestOfReviews = false;

    public $reviewScoreMaxDeviationForGood = 0;
    public $reviewScoreMaxCountsForGood = 0;
    
    public $reviewScoreMaxDeviationForPass = 0;
    public $reviewScoreMaxCountsForPass = 0;

    public $submissionType;
    public $submissionSettings;

    public $calibrationPoolAssignmentIds = array();


    public $dateFormat = "MMMM Do YYYY, HH:mm";

    function __construct(AssignmentID $id = NULL, $name, AssignmentDataManager $dataMgr)
    {
        parent::__construct($id, $name, $dataMgr);

        global $NOW;

        $this->submissionStartDate = $NOW;
        $this->submissionStopDate = $NOW;
        $this->reviewStartDate = $NOW;
        $this->reviewStopDate = $NOW;
        $this->markPostDate = $NOW;
        $this->appealStopDate = $NOW;
    }

    function getAssignmentTypeDisplayName()
    {
        return "Peer Review";
    }

    function getPoolStatusHTML(UserID $user)
    {
      $independentUsers = $this->getIndependentUsers();
      if(sizeof($independentUsers) == 0) {
        # No independent users set for this assignment; check the previous one
        $assignments = $this->getAssignmentsBefore(1);
        if(sizeof($assignments) != 1){
          # Couldn't find exactly one previous assignment, give up silently
          return "<!-- Couldn't find exactly one previous assignment -->";
        }
        $independentUsers = $assignments[0]->getIndependentUsers();
        if(sizeof($independentUsers) == 0) {
          # Okay, nevermind
          return "<!-- Neither this assignment nor the previous one have independent assignments -->";
        }
      }
      $isIndependent = array_key_exists($user->id, $independentUsers);
      
      if($isIndependent) {
        return "<b style='color:green;'>Independent</b> reviewer";
      }
      else {
        return "<b style='color:red;'>Supervised</b> reviewer<br>";
      }
    }
    
    function _getHeaderHTML(UserID $user)
    {
        global $dataMgr, $NOW;
        $html = "";
        if($dataMgr->isMarker($user))
        {
            if($dataMgr->isInstructor($user)){
                //#Give us options for editing this assignment
                $html .= "<table align='left'><tr>\n";
                $html .= "<td><a title='Edit Peer Review Questions' href='".get_redirect_url("peerreview/editreviewquestions.php?assignmentid=$this->assignmentID")."'><div class='icon editQuestions'></div></a></td>\n";
                $html .= "<td><a title='Edit Denied/Independent Users' href='".get_redirect_url("peerreview/editdeniedindependentusers.php?assignmentid=$this->assignmentID")."'><div class='icon editDenied'></div></a></td>\n";
                $html .= "<td><a title='Edit Peer Review Assignments' href='".get_redirect_url("peerreview/editpeerreview.php?assignmentid=$this->assignmentID")."'><div class='icon editPeerAssignment'></div></a></td>\n";
                $html .= "</tr></table>\n";
            }
            $html .= "<table width=100%><tr>\n";

            $stats = $this->getAssignmentStatistics();

            $html .= "<td width='33%'><strong>Submissions</strong> ($stats->numSubmissions/$stats->numPossibleSubmissions)<br/>\n";
            $html .= "<table align='left'><tr><td>Start:</td><td id='submissionStartDate$this->assignmentID'/></tr>\n";
            $html .= "<tr><td>Due:</td><td id='submissionStopDate$this->assignmentID'/></tr></table></td>\n";

            $html .= "<td width='33%'><strong>Reviews</strong> ($stats->numStudentReviews/$stats->numPossibleStudentReviews)<br/>\n";
            $html .= "<table align='left'><tr><td>Start:</td><td id='reviewStartDate$this->assignmentID'/></tr>\n";
            $html .= "<tr><td>Due:</td><td id='reviewStopDate$this->assignmentID'/></tr></table></td>\n";

            $html .= "<td width='33%'><strong>Marks</strong><br/>\n";
            $html .= "<table align='left'><tr><td>Post:</td><td id='markPostDate$this->assignmentID'/></tr>\n";
            $html .= "<tr><td colspan='2'><a href='".get_redirect_url("peerreview/index.php?assignmentid=$this->assignmentID")."'>Go to marking page</a></td></tr></table></td>\n";

            $html .= "</tr></table>\n";
            $html .= "<table width='100%'><tr><td>Appeal Stop Date: <span id='appealStopDate$this->assignmentID'></span></td></tr>";
            $html .= "<tr><td>There are $stats->numPendingAppeals pending appeals</td></tr></table>\n";
            $html .= "<script type='text/javascript'>\n";
            $html .= set_element_to_date("submissionStartDate$this->assignmentID", $this->submissionStartDate, "html", $this->dateFormat, false, true);
            $html .= set_element_to_date("submissionStopDate$this->assignmentID", $this->submissionStopDate, "html", $this->dateFormat, false, true);
            $html .= set_element_to_date("reviewStartDate$this->assignmentID", $this->reviewStartDate, "html", $this->dateFormat, false, true);
            $html .= set_element_to_date("reviewStopDate$this->assignmentID", $this->reviewStopDate, "html", $this->dateFormat, false, true);
            $html .= set_element_to_date("markPostDate$this->assignmentID", $this->markPostDate, "html", $this->dateFormat, false, true);
            $html .= set_element_to_date("appealStopDate$this->assignmentID", $this->appealStopDate, "html", $this->dateFormat, false, true);
            $html .= "</script>\n";
        }
        else
        {
            //First check if they are allowed to submit
            if($this->dataMgr->deniedUser($this, $user))
            {
                return "You were excluded from this assignment";
            }

            else
            {
                #We need to put the cell for the submission submission
                $html  = "<table width='100%' cellpadding='4'>\n";

                if($this->showPoolStatus) {
                    $html .= "<tr><td>&nbsp;</td><td>\n";
                    $html .= $this->getPoolStatusHTML($user);
                    $html .= "</td></tr>\n";
                }
                
                $html .= "<tr><td width=30%>\n";
                if($this->submissionStopDate < $NOW)
                {
                    $html .= "Submissions closed\n";
                    if($this->dataMgr->submissionExists($this, $user))
                        $html .= "<br><a href='".get_redirect_url("peerreview/viewsubmission.php?assignmentid=$this->assignmentID")."'>View Submission</a>";
                }
                else
                {
                    if($this->dataMgr->submissionExists($this, $user))
                        $html .= "<a href='".get_redirect_url("peerreview/editsubmission.php?assignmentid=$this->assignmentID")."'>Edit submission</a><br>\n";
                    else
                        $html .= "<a href='".get_redirect_url("peerreview/editsubmission.php?assignmentid=$this->assignmentID")."''>Create submission</a><br>\n";
                    $html .= "Due: <span id='submissionStopDate$this->assignmentID'/>";
                }
                $html .= "</td>\n";
                $html .= set_element_to_date("submissionStopDate$this->assignmentID", $this->submissionStopDate, "html", $this->dateFormat);

                #The middle cell contains reviews
                $html .= "<td width=40%>\n";
                if($NOW < $this->reviewStartDate)
                {
                    $html .= "&nbsp;";
                }
                else if($this->reviewStopDate < $NOW)
                {
                    $html .= "Review submissions closed";
                }
                else
                {
                    #Do they have calibration reviews to do?
                    $calibrationAssignments = $this->dataMgr->getAssignedCalibrationReviews($this, $user);

                    $doneCalibrations = array();
                    if($calibrationAssignments)
                    {
                        $html .= "<table align=left width=100%>";

                        $id = 0;
                        $pending = false;
                        foreach($calibrationAssignments as $matchID)
                        {
                            $temp=$id+1;

                            if($this->dataMgr->reviewExists($this, $matchID)) {
                              $mark = $this->dataMgr->getReviewMark($this, $matchID);
                              $doneCalibrations[$id] = new stdClass;
                              if($mark->isValid){
                                $doneCalibrations[$id]->text = "($mark->reviewPoints)"; 
                                $doneCalibrations[$id]->points = $mark->reviewPoints; 
                              }else{
                                $doneCalibrations[$id]->text = "";
                                $doneCalibrations[$id]->points = 0;
                              }
                            } else {
                              $html .= "<tr><td>";
                              $html .= "<a href='".get_redirect_url("peerreview/editreview.php?assignmentid=$this->assignmentID&calibration=$id")."''>Calibration Review $temp</a>";
                                $html .= "</td><td>";
                                if($this->dataMgr->reviewDraftExists($this, $matchID)) {
                                    $html .= "In Progress";
                                    $pending = true;
                                } else {
                                    $html .= "Not Complete";
                                    $pending = true;
                                }
                            }

                            $html .= "</td><tr>";
                            $id = $id+1;
                        }
                        $html .= "</table>";
                    }
                    $html .= "<br>";
                    if(!$pending && count($this->calibrationPoolAssignmentIds) > 0)
                        $html .= "<a href='".get_redirect_url("peerreview/requestcalibrationreviews.php?assignmentid=$this->assignmentID")."'>Request Calibration Review</a><br>";
                    #Do they have reviews to do?
                    $reviewAssignments = $this->dataMgr->getAssignedReviews($this, $user);
                    if($reviewAssignments)
                    {
                        $html .= "<table align=left width=100%>";

                        # Flag independents
                        # $html .= "<tr><td>";
                        # $html .= $this->getPoolStatusHTML($user);
                        # $html .= "</td></tr>";
                          
                        
                        $id = 0;
                        foreach($reviewAssignments as $matchID)
                        {
                            $html .= "<tr><td>";
                            $temp=$id+1;
                            $html .= "<a href='".get_redirect_url("peerreview/editreview.php?assignmentid=$this->assignmentID&review=$id")."''>Peer Review $temp</a>";
                            $html .= "</td><td>";
                            if($this->dataMgr->reviewExists($this, $matchID)) {
                                $html .= "Complete";
                            } else if($this->dataMgr->reviewDraftExists($this, $matchID)) {
                                $html .= "In Progress";
                            } else {
                                $html .= "Not Complete";
                            }

                            $html .= "</td></tr>";
                            $id = $id+1;
                        }
                        $html .= "</table>";
                        $html .= "Due: <span id='reviewStopDate$this->assignmentID'> </span>";
                        $html .= set_element_to_date("reviewStopDate$this->assignmentID", $this->reviewStopDate, "html", $this->dateFormat);
                    }
                    else
                    {
                        $html .= "No assigned peer reviews";
                        if($this->allowRequestOfReviews)
                            $html .= "<br><a href='".get_redirect_url("peerreview/requestpeerreviews.php?assignmentid=$this->assignmentID")."'>Request Reviews</a>";
                    }
                    if(sizeof($doneCalibrations) > 0){
                        $html .= "<br>Completed Calibration Reviews:<br>";
                        $html .= "<table align=left width=100%>";
                        $pointsRunningTotal = 0;
                        foreach($doneCalibrations as $id => $obj)
                        {
                            $html .= "<tr><td>";
                            $temp=$id+1;
                            $html .= "<a href='".get_redirect_url("peerreview/editreview.php?assignmentid=$this->assignmentID&calibration=$id")."''>Calibration Review $temp</a>";
                            $html .= "</td><td>".$obj->text."</td><tr>";
                            $pointsRunningTotal = max(0, $pointsRunningTotal + $obj->points);
                        }
                        $html .= "<tr><td></td><td>$pointsRunningTotal points total</td></tr>";
                        $html .= "</table>";
                    }
                }
                $html .= "</td>\n";

                #The last cell contains a link to view their marks
                $html .= "<td width=30%>";
                $submissionExists = $this->dataMgr->submissionExists($this, $user);
                $reviewAssignments = $this->dataMgr->getAssignedReviews($this, $user);
                if(($submissionExists || $reviewAssignments) && $this->markPostDate < $NOW)
                {
                    $html .= "<a href='".get_redirect_url("peerreview/viewmarks.php?assignmentid=$this->assignmentID")."''>View Marks</a>";
                    #Display this user's marks
                    $html .= "<br><table align='left' width='100%'>\n";
                    if($submissionExists)
                    {
                        $html .= "<tr><td>Submission:</td><td>". $this->dataMgr->getSubmissionMark($this, $this->getSubmissionID($user))->getSummaryString($this->maxSubmissionScore) ."</td></tr>\n";

                        $hasUpdate = false;
                        foreach($this->dataMgr->getMatchesForSubmission($this, $this->getSubmissionID($user)) as $matchID)
                        {
                            $hasUpdate |= $this->dataMgr->hasNewAppealMessage($this, $matchID, "review");
                        }
                        if($hasUpdate)
                           $html .= "<tr><td colsplan='2'>(Appeal updated)</td></tr>\n";
                    }
                    #$reviewAssignments = $this->dataMgr->getAssignedReviews($this, $user);
                    $id = 0;
                    foreach($reviewAssignments as $matchID)
                    {
                        $html .= "<tr><td>Review ".($id+1).":</td><td>". $this->dataMgr->getReviewMark($this, $matchID)->getSummaryString($this->maxReviewScore)."</td></tr>\n";
                        if($this->dataMgr->hasNewAppealMessage($this, $matchID, "reviewMark"))
                        {
                            $html .= "<tr><td colsplan='2'>(Mark appeal updated)</td></tr>\n";
                        }
                        $id++;
                    }
                    $html .= "</table><br>\n";

                    if($NOW < $this->appealStopDate)
                    {
                        $html .= "Appeals Close:<br> <span id='appealStopDate$this->assignmentID'/>";
                        $html .= set_element_to_date("appealStopDate$this->assignmentID", $this->appealStopDate, "html", $this->dateFormat);
                    }
                }
                else
                {
                    $html .= "&nbsp;";
                }
                $html .= "</td></tr>";
            }
            $html .= "</table>";
        }
        return $html;
    }


    function _loadFromPost($POST)
    {
        global $PEER_REVIEW_SUBMISSION_TYPES;

        $this->submissionQuestion = $POST['submissionQuestion'];

        #Validate the submission times
        #TODO: should probably do something smarter here
        $this->submissionStartDate  = intval($POST['submissionStartDateSeconds']);
        $this->submissionStopDate  = intval($POST['submissionStopDateSeconds']);

        #Validate the review times
        $this->reviewStartDate  = intval($POST['reviewStartDateSeconds']);
        $this->reviewStopDate  = intval($POST['reviewStopDateSeconds']);

        #Now validate the mark post date
        $this->markPostDate = intval($POST['markPostDateSeconds']);
        $this->appealStopDate = intval($POST['appealStopDateSeconds']);

        $this->maxSubmissionScore = floatval($POST["maxSubmissionScore"]);
        $this->maxReviewScore = floatval($POST["maxReviewScore"]);
        $this->defaultNumberOfReviews= intval($POST["defaultNumberOfReviews"]);
        $this->allowRequestOfReviews = isset_bool($POST["allowRequestOfReviews"]);

        $this->showMarksForReviewsReceived = isset_bool($POST['showMarksForReviewsReceived']);
        $this->showOtherReviewsByStudents = isset_bool($POST['showOtherReviewsByStudents']);
        $this->showOtherReviewsByInstructors = isset_bool($POST['showOtherReviewsByInstructors']);
        $this->showMarksForOtherReviews = isset_bool($POST['showMarksForOtherReviews']);
        $this->showMarksForReviewedSubmissions = isset_bool($POST['showMarksForReviewedSubmissions']);
        $this->showPoolStatus = isset_bool($POST['showPoolStatus']);
        
        $this->reviewScoreMaxDeviationForGood = floatval($POST["reviewScoreMaxDeviationForGood"]);
        $this->reviewScoreMaxCountsForGood = intval($POST["reviewScoreMaxCountsForGood"]);

        $this->reviewScoreMaxDeviationForPass = floatval($POST["reviewScoreMaxDeviationForPass"]);
        $this->reviewScoreMaxCountsForPass = intval($POST["reviewScoreMaxCountsForPass"]);

        if(!array_key_exists("calibrationPoolAssignmentIds", $POST))
            $this->calibrationPoolAssignmentIds = array();
        else
            $this->calibrationPoolAssignmentIds = $POST["calibrationPoolAssignmentIds"];

        //Figure out what type of submission they have chosen
        $this->submissionType = $POST["submissionType"];
        if(array_key_exists($this->submissionType, $PEER_REVIEW_SUBMISSION_TYPES))
        {
            //Load up this critter's settings
            $submissionSettingsType = $this->submissionType."SubmissionSettings";
            $this->submissionSettings = new $submissionSettingsType();
            $this->submissionSettings->loadFromPost($POST);
        }
        else
        {
            throw new Exception("Unhandled submission type '$this->submissionType'");
        }
    }

    function _showForUser(UserID $user)
    {
        //Is this user an instructor?
        global $dataMgr, $NOW;
        if($dataMgr->isInstructor($user))
            return true;

        //Otherwise, we need to compare against the start date
        return $this->submissionStartDate < $NOW;
    }

    function _getFormHTML()
    {
        global $PEER_REVIEW_SUBMISSION_TYPES;
        $html  = "<table align='left' width='100%'>\n";
        $html .= "<tr><td>Submission&nbsp;Question</td></tr><tr><td colspan=2><textarea name='submissionQuestion' id='submissionQuestion' cols='60' rows='10' class='mceEditor'/>".htmlentities($this->submissionQuestion)."</textarea></td></tr>\n";

        //Now we need to show the pickers for the different types of submissions
        $html .= "</table>\n";
        $html .= "<table>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "<tr><td width='190'>Submission&nbsp;Type</td><td align='left'><select name='submissionType' id='submissionTypeSelect'/>";
        foreach($PEER_REVIEW_SUBMISSION_TYPES as $type => $displayName)
        {
            $tmp = '';
            if($type == $this->submissionType)
                $tmp = "selected";
            $html .= "<option value='$type' $tmp>$displayName</option>\n";
        }
        $html .= "</select>\n";
        $html .= "</td></tr>\n";
        $html .= "</table>\n";
        $html .= "<div id='submissionTypeDivContainer'>\n";
        foreach($PEER_REVIEW_SUBMISSION_TYPES as $type => $displayName)
        {
            $html .= "<div id='$type'>\n";
            if($type == $this->submissionType) {
                $html .= $this->submissionSettings->getFormHTML();
            }else{
                $settingsType = $type."SubmissionSettings";
                $settings = new $settingsType();
                $html .= $settings->getFormHTML();
            }
            $html .= "</div>\n";
        }
        $html .= "</div>\n";

        $html .= "<script type='text/javascript'>
        $('#submissionTypeSelect').change(function(){
            $('#' + this.value).show().siblings().hide();
        });
        $('#submissionTypeSelect').change();
        </script>\n";

        $html .= "<table align='left' width='100%'>\n";
        $html .= "<tr><td width=190>&nbsp;</td></tr>\n";
        $html .= "<tr><td>Submission&nbsp;Start&nbsp;Date</td><td><input type='text' name='submissionStartDate' id='submissionStartDate' /></td></tr>\n";
        $html .= "<tr><td>Submission&nbsp;Stop&nbsp;Date</td><td><input type='text' name='submissionStopDate' id='submissionStopDate'/></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "<tr><td>Review&nbsp;Start&nbsp;Date</td><td><input type='text' name='reviewStartDate' id='reviewStartDate' /></td></tr>\n";
        $html .= "<tr><td>Review&nbsp;Stop&nbsp;Date</td><td><input type='text' name='reviewStopDate' id='reviewStopDate' /></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "<tr><td>Mark&nbsp;Post&nbsp;Date</td><td><input type='text' name='markPostDate' id='markPostDate' /></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "<tr><td>Max&nbsp;Submission&nbsp;Score</td><td><input type='text' name='maxSubmissionScore' id='maxSubmissionScore' value='$this->maxSubmissionScore'/></td></tr>\n";
        $html .= "<tr><td>Max&nbsp;Review&nbsp;Score</td><td><input type='text' name='maxReviewScore' id='maxReviewScore' value='$this->maxReviewScore'/></td></tr>\n";
        $html .= "<tr><td>Default&nbsp;Number&nbsp;of&nbsp;Reviews</td><td><input type='text' name='defaultNumberOfReviews' id='defaultNumberOfReviews' value='$this->defaultNumberOfReviews'/></td></tr>\n";
        $tmp = "";
        if($this->allowRequestOfReviews)
            $tmp = "checked";

        $html .= "<tr><td>Allow&nbsp;Request&nbsp;of&nbsp;Reviews</td><td><input type='checkbox' name='allowRequestOfReviews' id='allowRequestOfReviews' $tmp /></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "<tr><td>Appeal&nbsp;Stop&nbsp;Date</td><td><input type='text' name='appealStopDate' id='appealStopDate' /></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>\n";

        $tmp = '';
        if($this->showMarksForReviewsReceived)
            $tmp = 'checked';
        $html .= "<tr><td style='text-align:right'><input type='checkbox' name='showMarksForReviewsReceived' $tmp /></td><td>Show the marks/comments that were given to the reviews that authors recieve</td></tr>\n";

        $tmp = '';
        if($this->showOtherReviewsByStudents)
            $tmp = 'checked';
        $html .= "<tr><td style='text-align:right'><input type='checkbox' name='showOtherReviewsByStudents' $tmp /></td><td>Show the other reviews written by students that were given to the papers that students reviewed</td></tr>\n";
        
        $tmp = '';
        if($this->showOtherReviewsByInstructors)
            $tmp = 'checked';
        $html .= "<tr><td style='text-align:right'><input type='checkbox' name='showOtherReviewsByInstructors' $tmp /></td><td>Show the other reviews written by instructors that were given to the papers that students reviewed</td></tr>\n";

        $tmp = '';
        if($this->showMarksForOtherReviews)
            $tmp = 'checked';
        $html .= "<tr><td style='text-align:right'><input type='checkbox' name='showMarksForOtherReviews' $tmp /></td><td>Show the marks/comments for other reviews that were given to the papers that students reviewed</td></tr>\n";

        $tmp = '';
        if($this->showMarksForReviewedSubmissions)
            $tmp = 'checked';
        $html .= "<tr><td style='text-align:right'><input type='checkbox' name='showMarksForReviewedSubmissions' $tmp /></td><td>Show the marks/comments for submissions that students reviewed</td></tr>\n";

        $tmp = '';
        if($this->showPoolStatus)
            $tmp = 'checked';
        $html .= "<tr><td style='text-align:right'><input type='checkbox' name='showPoolStatus' $tmp /></td><td>Show independent/supervised status</td></tr>\n";
        
        $html .= "<tr><td>&nbsp;</td></tr>\n";
        $html .= "</table>";

        $html .= "<input type='hidden' name='submissionStartDateSeconds' id='submissionStartDateSeconds' />\n";
        $html .= "<input type='hidden' name='submissionStopDateSeconds' id='submissionStopDateSeconds' />\n";
        $html .= "<input type='hidden' name='reviewStartDateSeconds' id='reviewStartDateSeconds' />\n";
        $html .= "<input type='hidden' name='reviewStopDateSeconds' id='reviewStopDateSeconds' />\n";
        $html .= "<input type='hidden' name='markPostDateSeconds' id='markPostDateSeconds' />\n";
        $html .= "<input type='hidden' name='appealStopDateSeconds' id='appealStopDateSeconds' />\n";


        $html .= "<h3>Calibration Auto Scoring</h3>";
        $html .= "<table align='left' width='100%'>\n";
        $html .= "<tr><td width='320px'>Max review score deviation for good</td><td><input type='text' name='reviewScoreMaxDeviationForGood' value='$this->reviewScoreMaxDeviationForGood'/></td></tr>\n";
        $html .= "<tr><td>Max counts of max deviation for good</td><td><input type='text' name='reviewScoreMaxCountsForGood' value='$this->reviewScoreMaxCountsForGood'/></td></tr>\n";
        $html .= "<tr><td>&nbsp;</td></tr>";
        $html .= "<tr><td>Max review score deviation for pass</td><td><input type='text' name='reviewScoreMaxDeviationForPass' value='$this->reviewScoreMaxDeviationForPass'/></td></tr>\n";
        $html .= "<tr><td>Max counts of max deviation for pass</td><td><input type='text' name='reviewScoreMaxCountsForPass' value='$this->reviewScoreMaxCountsForPass'/></td></tr>\n";
        $html .= "</table><br>\n";


        global $dataMgr;
        $html .= "<h3>Calibration Pool Selection</h3>";

        foreach($dataMgr->getAssignmentHeaders() as $assgn)
        {
            if($assgn->assignmentType != "peerreview")
                continue;

            $tmp = "";
            if(in_array($assgn->assignmentID->id, $this->calibrationPoolAssignmentIds))
                $tmp = "checked";

            $html .= "<input type='checkbox' name='calibrationPoolAssignmentIds[]' value='$assgn->assignmentID' $tmp /> $assgn->name <br>\n";
        }

        return $html;
    }

    protected function _duplicate()
    {
        $duplicate = clone $this;
        return $duplicate;
    }

    function finalizeDuplicateFromBase(Assignment $baseAssignment)
    {
        foreach(array_reverse($baseAssignment->getReviewQuestions()) as $question)
        {
            $question->questionID = NULL;
            $this->dataMgr->saveReviewQuestion($this, $question);
        }
    }

    function _getValidationCode()
    {
        global $PEER_REVIEW_SUBMISSION_TYPES;
        $code = "$('#submissionStartDateSeconds').val(moment($('#submissionStartDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        $code .= "$('#submissionStopDateSeconds').val(moment($('#submissionStopDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        $code .= "$('#reviewStartDateSeconds').val(moment($('#reviewStartDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        $code .= "$('#reviewStopDateSeconds').val(moment($('#reviewStopDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        $code .= "$('#markPostDateSeconds').val(moment($('#markPostDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";
        $code .= "$('#appealStopDateSeconds').val(moment($('#appealStopDate').val(), 'MM/DD/YYYY HH:mm').unix());\n";

        //Now we have to build the validators for each of the different types
        foreach($PEER_REVIEW_SUBMISSION_TYPES as $type => $displayType)
        {
            $code .= "if($('#submissionTypeSelect').val() == '$type'){\n";
            $settingsType = $type."SubmissionSettings";
            $settings = new $settingsType();
            $code .= $settings->getValidationCode();
            $code .= "}";
        }
        return $code;
    }

    function _getFormScripts()
    {
        $code  = init_tiny_mce(false);
        $code .= $this->getScriptForDatePickers('submissionStartDate','submissionStopDate',$this->submissionStartDate, $this->submissionStopDate);
        $code .= $this->getScriptForDatePickers('reviewStartDate','reviewStopDate', $this->reviewStartDate, $this->reviewStopDate);
        $code .= "<script type='text/javascript'> $('#markPostDate').datetimepicker({ defaultDate : new Date(".($this->markPostDate*1000).")}); </script>\n";
        $code .= "<script type='text/javascript'> $('#appealStopDate').datetimepicker({ defaultDate : new Date(".($this->appealStopDate*1000).")}); </script>\n";
        $code .= set_element_to_date("markPostDate", $this->markPostDate);
        $code .= set_element_to_date("appealStopDate", $this->appealStopDate);
        return $code;
    }

    #Function for the date pickers
    static private function getScriptForDatePickers($startID, $stopID, $startDate='', $stopDate='')
    {
        $minDate = 'null';
        $maxDate = 'null';
        if($startDate != '')
            $minDate = "new Date(".($startDate*1000).")";
        if($stopDate != '')
            $maxDate = "new Date(".($stopDate*1000).")";
        return "  <script type='text/javascript'>
                $('#$startID').datetimepicker({
                    maxDate: $maxDate,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    defaultDate : $minDate,
                    onClose: function(dateText, inst) {
                        var endDateTextBox = $('#$stopID');
                        if (endDateTextBox.val() != '') {
                            var testStartDate = new Date(dateText);
                            var v = endDateTextBox.val();
                            var testEndDate = new Date(endDateTextBox.val());
                            if (testStartDate > testEndDate)
                                endDateTextBox.val(dateText);
                        }
                        else {
                            endDateTextBox.val(dateText);
                        }
                    },
                    onSelect: function (selectedDateTime){
                        var start = $(this).datetimepicker('getDate');
                        var d = new Date($('#$stopID').datetimepicker('getDate'));
                        $('#$stopID').datetimepicker('option', 'minDate', new Date(start.getTime()));
                        $('#$stopID').val(zeroFill(d.getMonth() + 1, 2) + '/' + zeroFill(d.getDate(), 2) + '/' + d.getFullYear() + ' ' + zeroFill(d.getHours(), 2) + ':' + zeroFill(d.getMinutes(), 2));
                    },
                });
                $('#$stopID').datetimepicker({
                    minDateTime: $minDate,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    defaultDate : $maxDate,
                    onClose: function(dateText, inst) {
                        var startDateTextBox = $('#$startID');
                        if (startDateTextBox.val() != '') {
                            var testStartDate = new Date(startDateTextBox.val());
                            var testEndDate = new Date(dateText);
                            if (testStartDate > testEndDate)
                                startDateTextBox.val(dateText);
                        }
                        else {
                            startDateTextBox.val(dateText);
                        }
                    },
                    onSelect: function (selectedDateTime){
                        var end = $(this).datetimepicker('getDate');
                        var d = new Date($('#$startID').datetimepicker('getDate'));
                        $('#$startID').datetimepicker('option', 'maxDate', new Date(end.getTime()));
                        $('#$startID').val(zeroFill(d.getMonth() + 1, 2) + '/' + zeroFill(d.getDate(), 2) + '/' + d.getFullYear() + ' ' + zeroFill(d.getHours(), 2) + ':' + zeroFill(d.getMinutes(), 2));
                    }
    });".
        set_element_to_date($startID, $startDate, "val", "MM/DD/YYYY HH:mm", false, true).
        set_element_to_date($stopID, $stopDate, "val", "MM/DD/YYYY HH:mm", false, true).
       "</script>";
    }

    function getPasswordLockedHTML()
    {
        return "<a href='".get_redirect_url("peerreview/viewquestion.php?assignmentid=$this->assignmentID")."'>View Question</a><br>";
    }

    function getAssignmentsBefore($maxAssignments = 4)
    {
        global $dataMgr;
        //Get all the assignments
        $assignmentHeaders = $dataMgr->getAssignmentHeaders();
        $assignments = array();
        $foundCurrent = false;
        foreach($assignmentHeaders as $header)
        {
            if($foundCurrent && $header->assignmentType == "peerreview") {
                $assignments[] = $dataMgr->getAssignment($header->assignmentID, "peerreview");
            } else if ($header->assignmentID->id == $this->assignmentID->id) {
                $foundCurrent = true;
            }
        }
        //Sort the assignments based on their date
        //usort($assignments, function($a, $b) { return $a->reviewStopDate < $b->reviewStopDate; } );

        if($maxAssignments < 0)
            return $assignments;

        return array_splice($assignments, 0, $maxAssignments);
    }

    function getGrades()
    {
        #Figure out the maximum number of reviews that a person did
        $reviewAssignment = $this->dataMgr->getReviewerAssignment($this);
        $maxReviews = array_reduce($reviewAssignment, function ($a, $b) { return max(sizeof($a), sizeof($b)); });
        $authorMap = $this->dataMgr->getAuthorSubmissionMap($this);

        $grades = new AssignmentGrades();
        $grades->headers[] = "Essay";
        for($i = 0; $i < $maxReviews; $i++)
        {
            $grades->headers[] = "Review ".($i+1);
        }

        #Insert all the essay marks
        foreach($authorMap as $authorID => $submissionID)
        {
            $mark = $this->dataMgr->getSubmissionMark($this, $submissionID);
            if($mark->isValid)
                $grades->gradesForUsers[$authorID] = array(1.0 * $mark->getScore() / $this->maxSubmissionScore);
        }

        #Now get all of the review marks
        foreach($reviewAssignment as $submissionID => $reviewAssign)
        {
            foreach($reviewAssign as $reviewerID)
            {
                if(!array_key_exists($reviewerID->id, $grades->gradesForUsers))
                     $grades->gradesForUsers[$reviewerID->id] = array("");

                $mark = $this->dataMgr->getReviewMark($this, $this->dataMgr->getReview($this, new SubmissionID($submissionID), $reviewerID)->matchID);
                if($mark->isAutomatic)
                    $grades->gradesForUsers[$reviewerID->id][] = "A";
                else if($mark->isValid)
                    $grades->gradesForUsers[$reviewerID->id][] =  (1.0 * $mark->getScore() / $this->maxReviewScore);
            }
        }

        return $grades;
    }

};

