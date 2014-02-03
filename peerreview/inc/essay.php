<?php
require_once(dirname(__FILE__)."/submission.php");

class EssaySubmission extends Submission
{
    public $text = "";
    public $topicIndex = null;

    function _loadFromPost($POST)
    {
        if(!array_key_exists("text", $POST))
            throw new Exception("Missing data in POST");

        $this->text = get_html_purifier()->purify($POST["text"]);

        if(array_key_exists("topic", $POST))
        {
            if($POST["topic"] == "NULL")
                throw new ErrorException("Topic was not picked");
            $this->topicIndex = $POST["topic"];
        }
    }

    function _getHTML($showHidden)
    {
        $html = "";
        if(!is_null($this->topicIndex))
        {
            $html = "<h3>Topic: ".$this->submissionSettings->topics[$this->topicIndex]."</h3>\n";
        }
        $html .= $this->text;
        return $html;
    }

    function _getValidationCode()
    {
        //only if we have topics do we need to ensure that one has been picked
        $code  = "$('#error_topic').html('').parent().hide();\n";
        $code .= "if($('#topicSelect').val() == 'NULL') {";
        $code .= "$('#error_topic').html('You must select a topic');\n";
        $code .= "$('#error_topic').parent().show();\n";
        $code .= "error = true;}\n";

        $code .= "$('#error_essay').html('').parent().hide();\n";
        //TODO: Make this a setting in an essay
        //$code .= "if(getWordCount('essayEdit') > 350) {";
        $code .= "if(getWordCount('essayEdit') > 99999) {";
        $code .= "$('#error_essay').html('Essays must not be longer than 300 words. (Note: Some editors add phantom characters to your document, try cleaning the text by copying it into a program like notepad then pasting it in if you feel you receive this message in error)');\n";
        $code .= "$('#error_essay').parent().show();\n";
        $code .= "error = true;}";
        return $code;
    }

    function _getFormHTML()
    {
        $html = "";
        if(sizeof($this->submissionSettings->topics))
        {
            $html  = "Topic: <select name='topic' id='topicSelect'>\n";
            $html .= "<option value='NULL'></option>\n";
            for($i = 0; $i < sizeof($this->submissionSettings->topics); $i++)
            {
                $tmp = '';
                if(!is_null($this->topicIndex) && $i == $this->topicIndex)
                    $tmp = "selected";
                $html .= "<option value='$i' $tmp>".$this->submissionSettings->topics[$i]."</option>\n";
            }
            $html .= "</select><br>";
            $html .= "<div class=errorMsg><div class='errorField' id='error_topic'></div></div><br>\n";
        }

        $html .= "<textarea name='text' cols='60' rows='40' class='mceEditor' id='essayEdit' accept-charset='utf-8'>\n";
        $html .= htmlentities($this->text, ENT_COMPAT|ENT_HTML401,'UTF-8');
        $html .= "</textarea><br>\n";
        $html .= "<div class=errorMsg><div class='errorField' id='error_essay'></div></div><br>\n";

        return $html;
    }

};

class EssaySubmissionSettings extends SubmissionSettings
{
    public $topics = array();

    function getFormHTML()
    {
        $html  = "<table width='100%' align='left'>\n";
        $html .= "<tr><td>Topic Combo Box Options (One per line)<br>Leave blank if you don't wany to have a selection</td>\n";
        $html .= "<td><textarea id='essayTopicTextArea' name='essayTopicTextArea' cols='40' rows='10' wrap='off'>";
        foreach($this->topics as $topic)
            $html .= "$topic\n";
        $html .= "</textarea></td><tr>\n";
        $html .= "</table>\n";
        return $html;
    }

    function loadFromPost($POST)
    {
        //We need to figure out the topics
        if(!array_key_exists("essayTopicTextArea", $POST))
            throw new Exception("Failed to get the topic text from POST");
        $this->topics = array();
        foreach(explode("\n", str_replace("\r", "", $POST['essayTopicTextArea'])) as $topic)
        {
            $topic = trim($topic);
            if($topic)
            {
                $this->topics[] = $topic;
            }
        }
    }
};

class EssayPDOPeerReviewSubmissionHelper extends PDOPeerReviewSubmissionHelper
{
    function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment)
    {
        //Delete any old topics, and just write in the new ones
        $sh = $this->prepareQuery("deleteAssignmentEssaySubmissionSettingsQuery", "DELETE FROM peer_review_assignment_essay_settings WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $sh = $this->prepareQuery("insertAssignmentEssaySubmissionSettingsQuery", "INSERT INTO peer_review_assignment_essay_settings (assignmentID, topicIndex, topic) VALUES (?, ?, ?);");
        $i = 0;
        foreach($assignment->submissionSettings->topics as $topic)
        {
            $sh->execute(array($assignment->assignmentID, $i, $topic));
            $i++;
        }
    }

    function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment)
    {
        //We just need to grab the topics
        $sh = $this->db->prepare("SELECT topic FROM peer_review_assignment_essay_settings WHERE assignmentID = ? ORDER BY topicIndex;");
        $sh->execute(array($assignment->assignmentID));

        $assignment->submissionSettings = new EssaySubmissionSettings();
        while($res = $sh->fetch())
        {
            $assignment->submissionSettings->topics[] = $res->topic;
        }
    }

    function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $essay = new EssaySubmission($assignment->submissionSettings, $submissionID);
        $sh = $this->prepareQuery("getEssaySubmissionQuery", "SELECT `text`, topicIndex FROM peer_review_assignment_essays WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get essay '$submissionID'");
        $essay->text = $res->text;
        $essay->topicIndex = $res->topicIndex;
        return $essay;
    }

    function saveAssignmentSubmission(PeerReviewAssignment $assignment, Submission $essay, $isNewSubmission)
    {
        if($isNewSubmission)
        {
            $sh = $this->prepareQuery("saveEssaySubmissionInsertQuery", "INSERT INTO peer_review_assignment_essays (submissionID, text, topicIndex) VALUES(?, ?, ?);");
            $sh->execute(array($essay->submissionID, $essay->text, $essay->topicIndex));
        }
        else
        {
            $sh = $this->prepareQuery("saveEssaySubmissionUpdateQuery", "UPDATE peer_review_assignment_essays SET text = ?, topicIndex = ? WHERE submissionID = ?;");
            $sh->execute(array($essay->text, $essay->topicIndex, $essay->submissionID));
        }
    }
}


