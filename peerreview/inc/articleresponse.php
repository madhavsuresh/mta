<?php
require_once(dirname(__FILE__)."/submission.php");

class ArticleResponseSubmission extends Submission
{
    public $outline = "";
    public $response = "";
    public $articleIndex = null;

    function _loadFromPost($POST)
    {
        if(!array_key_exists("outline", $POST) || !array_key_exists("response", $POST))
            throw new Exception("Missing data in POST");

        $this->outline = get_html_purifier()->purify($POST["outline"]);
        $this->response = get_html_purifier()->purify($POST["response"]);

        if(array_key_exists("articleIndex", $POST))
        {
            if($POST["articleIndex"] == "NULL")
                throw new ErrorException("Article was not picked");
            $this->articleIndex = $POST["articleIndex"];
        }
    }

    function _getHTML($showHidden)
    {
        $html = "<h3>Article</h3>\n";
        $html .= "<a href='".$this->submissionSettings->articles[$this->articleIndex]->link."'>".$this->submissionSettings->articles[$this->articleIndex]->name."</a>\n";
        $html .= "<h3>Outline + Thesis</h3>\n";
        $html .= $this->outline;
        $html .= "<h3>Essay</h3>\n";
        $html .= $this->response;
        return $html;
    }

    function _getValidationCode()
    {
        //only if we have articles do we need to ensure that one has been picked
        $code  = "$('#error_article').html('').parent().hide();\n";
        $code .= "if($('#articleSelect').val() == 'NULL') {";
        $code .= "$('#error_article').html('You must select an article');\n";
        $code .= "$('#error_article').parent().show();\n";
        $code .= "error = true;}";
        return $code;
    }

    function _getFormHTML()
    {
        $html  = "Article: <select name='articleIndex' id='articleSelect'>\n";
        $html .= "<option value='NULL'></option>\n";
        for($i = 0; $i < sizeof($this->submissionSettings->articles); $i++)
        {
            $tmp = '';
            if(!is_null($this->articleIndex) && $i == $this->articleIndex)
                $tmp = "selected";
            $html .= "<option value='$i' $tmp>".$this->submissionSettings->articles[$i]->name."</option>\n";
        }
        $html .= "</select><br>";
        $html .= "<div class=errorMsg><div class='errorField' id='error_article'></div></div><br>\n";

        $html .= "<h3>Outline + Thesis</h3>\n";
        $html .= "<textarea name='outline' cols='60' rows='40' class='mceEditor'>\n";
        $html .= htmlentities($this->outline, ENT_COMPAT|ENT_HTML401, 'UTF-8');
        $html .= "</textarea><br>\n";

        $html .= "<h3>Essay</h3>\n";
        $html .= "<textarea name='response' cols='60' rows='40' class='mceEditor'>\n";
        $html .= htmlentities($this->response, ENT_COMPAT|ENT_HTML401,'UTF-8');
        $html .= "</textarea><br>\n";

        return $html;
    }

};

class ArticleResponseSubmissionSettings extends SubmissionSettings
{
    public $articles = array();

    function getFormHTML()
    {
        $html  = "Add one line to both fields for each article you want to use";
        $html .= "<table width='100%' align='center'>\n";
        $html .= "<tr><td>Article Names</td><td>Article Links</td></tr><tr>\n";
        $html .= "<td><textarea id='articleReviewArticleTextArea' name='articleReviewArticleTextArea' cols='40' rows='10' wrap='off'>";
        $html .= array_reduce($this->articles, function($v, $w) { return "$v\n$w->name";});
        $html .= "</textarea></td><td><textarea id='articleReviewLinkTextArea' name='articleReviewLinkTextArea' cols='40' rows='10' wrap='off'>";
        $html .= array_reduce($this->articles, function($v, $w) { return "$v\n$w->link";});
        $html .= "</textarea></td></tr>";
        $html .= "<tr><td><div class=errorMsg><div class='errorField' id='error_fewArticles'></div></div></td><td><div class=errorMsg><div class='errorField' id='error_fewLinks'></div></div></td></tr>\n";


        $html .= "</table>\n";
        return $html;
    }

    function getValidationCode()
    {
        $code  = "$('#error_fewArticles').html('').parent().hide();\n";
        $code .= "$('#error_fewLinks').html('').parent().hide();\n";

        #Check to make sure it is the proper length
        $code .= "var artLen = $('#articleReviewArticleTextArea').val().split('\\n').length;\n";
        $code .= "var linkLen = $('#articleReviewLinkTextArea').val().split('\\n').length;\n";
        $code .= "if(artLen < linkLen){\n";
        $code .= "$('#error_fewArticles').html('There are more links than articles');\n";
        $code .= "$('#error_fewArticles').parent().show();\n";
        $code .= "error=true;}\n";

        $code .= "if(artLen > linkLen){\n";
        $code .= "$('#error_fewLinks').html('There are more articles than links');\n";
        $code .= "$('#error_fewLinks').parent().show();\n";
        $code .= "error=true;}\n";
        return $code;
    }

    function loadFromPost($POST)
    {
        //We need to figure out the topics
        if(!array_key_exists("articleReviewArticleTextArea", $POST))
            throw new Exception("Failed to get the article text from POST");
        if(!array_key_exists("articleReviewLinkTextArea", $POST))
            throw new Exception("Failed to get the link text from POST");
        $this->articles = array();

        $names = explode("\n", str_replace("\r", "", $POST['articleReviewArticleTextArea']));
        $links = explode("\n", str_replace("\r", "", $POST['articleReviewLinkTextArea']));

        if(sizeof($names) != sizeof($links))
            throw new Exception("Number of links does not match the number of articles");
        for($i = 0; $i < sizeof($links); $i++)
        {
            $obj = new stdClass;
            $obj->name = trim($names[$i]);
            $obj->link= trim($links[$i]);
            $this->articles[] = $obj;
        }
    }
};

class ArticleResponsePDOPeerReviewSubmissionHelper extends PDOPeerReviewSubmissionHelper
{
    function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment)
    {
        //We just need to grab the topics
        $sh = $this->db->prepare("SELECT name, link FROM peer_review_assignment_article_response_settings WHERE assignmentID = ? ORDER BY articleIndex;");
        $sh->execute(array($assignment->assignmentID));

        $assignment->submissionSettings = new ArticleResponseSubmissionSettings();
        while($res = $sh->fetch())
        {
            $obj = new stdClass;
            $obj->name = $res->name;
            $obj->link = $res->link;
            $assignment->submissionSettings->articles[] = $obj;
        }
    }

    function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment)
    {
        //Delete any old topics, and just write in the new ones
        $sh = $this->prepareQuery("deleteAssignmentArticleResponseSubmissionSettingsQuery", "DELETE FROM peer_review_assignment_article_response_settings WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));

        $sh = $this->prepareQuery("insertAssignmentArticleResponseSubmissionSettingsQuery", "INSERT INTO peer_review_assignment_article_response_settings (assignmentID, articleIndex, name, link) VALUES (?, ?, ?, ?);");
        $i = 0;
        foreach($assignment->submissionSettings->articles as $article)
        {
            $sh->execute(array($assignment->assignmentID, $i, $article->name, $article->link));
            $i++;
        }
    }

    function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $response = new ArticleResponseSubmission($assignment->submissionSettings, $submissionID);
        $sh = $this->prepareQuery("getArticleResponseSubmissionQuery", "SELECT `outline`, `response`, articleIndex FROM peer_review_assignment_article_responses WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get article response '$submissionID'");
        $response->outline = $res->outline;
        $response->response = $res->response;
        $response->articleIndex = $res->articleIndex;
        return $response;
    }


    function saveAssignmentSubmission(PeerReviewAssignment $assignment, Submission $response, $isNewSubmission)
    {
        if($isNewSubmission)
        {
            $sh = $this->prepareQuery("saveArticleResponseSubmissionInsertQuery", "INSERT INTO peer_review_assignment_article_responses (submissionID, outline, response, articleIndex) VALUES(?, ?, ?, ?);");
            $sh->execute(array($response->submissionID, $response->outline, $response->response, $response->articleIndex));
        }
        else
        {
            $sh = $this->prepareQuery("saveArticleResponseSubmissionUpdateQuery", "UPDATE peer_review_assignment_article_responses SET outline = ?, response = ?, articleIndex = ? WHERE submissionID = ?;");
            $sh->execute(array($response->outline, $response->response, $response->articleIndex, $response->submissionID));
        }
    }
}

