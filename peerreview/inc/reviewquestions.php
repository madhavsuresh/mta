<?php

    class ReviewAnswer
    {
        public $text;
        public $int;
    }

    /** The generic class that all review questions derive from */
    abstract class ReviewQuestion
    {
        public $questionID;
        public $name = "";
        public $question = "";
        public $hidden = false;
        public $displayPriority;

        function __construct(QuestionID $questionID = null, $name, $question, $hidden=false, $displayPriority=0)
        {
            $this->questionID = $questionID;
            $this->name = $name;
            $this->question  = $question;
            $this->hidden = $hidden;
            $this->displayPriority = $displayPriority;
        }

        function getHTML(ReviewAnswer $answer = null)
        {
            $html  = "<h2>".$this->question."</h2>\n";
            $html .= $this->_getAnswerHTML($answer)."\n<br>\n";
            return $html;
        }

        function getShortHTML(ReviewAnswer $answer = null)
        {
            $html  = "<h2>".cleanString($this->name)."</h2>\n";
            $html .= $this->_getAnswerHTML($answer)."\n<br>\n";
            return $html;
        }

        function loadFromPost($POST)
        {
            $this->name = $POST['name'];
            $this->question = get_html_purifier()->purify($POST['question']);
            $this->hidden = isset_bool($POST['hidden']);
            $this->_loadFromPost($POST);
        }

        function getOptionsFormHTML()
        {
            $html  = "<table align='left' width='100%'>\n";

            $html .= "<tr><td>Question&nbsp;Name</td><td><input type='text' name='name' id='name' value='".htmlentities($this->name, ENT_COMPAT|ENT_QUOTES)."' size='50'/><div class=errorMsg><div class='errorField' id='error_name'></div></td></tr>\n";
            $html .= "<tr><td>&nbsp;</td></tr>\n";
            $html .= "<tr><td>Question</td><td><textarea name='question' id='question' cols='60' rows='10'/>$this->question</textarea></td></tr>\n";
            $html .= "<tr><td>&nbsp;</td></tr>\n";
            $tmp = '';
            if($this->hidden) { $tmp = 'checked'; }
            $html .= "<tr><td>Hidden</td><td><input type='checkbox' name='hidden' $tmp /> Don't show the result of this question to students</td></tr>\n";
            $html .= "<tr><td>&nbsp;</td></tr>\n";
            $html .= "</table>\n";
            $html .= $this->_getOptionsFormHTML();
            return $html;
        }

        function getValidateOptionsCode()
        {
            $code = $this->_getValidateOptionsCode();
            return $code;
        }

        function getScore(ReviewAnswer $answer = NULL)
        {
            return 0;
        }

        function getFormHTML(ReviewAnswer $answer = NULL)
        {
            $html  = "<h2>$this->question</h2>\n";
            $html .= $this->_getFormHTML($answer);
            return $html;
        }

        function getValidationCode()
        {
            return $this->_getValidationCode();
        }

        function loadAnswerFromPost($POST)
        {
            return $this->_loadAnswerFromPost($POST);
        }

        protected function _getOptionsFormHTML() {}
        protected function _getValidateOptionsCode() {}
        abstract protected function _getAnswerHTML(ReviewAnswer $answer = NULL);
        abstract protected function _getFormHTML(ReviewAnswer $answer = NULL);
        abstract protected function _getValidationCode();
        abstract protected function _loadFromPost($POST);
        abstract protected function _loadAnswerFromPost($POST);
    };


    class TextAreaQuestion extends ReviewQuestion
    {
        public $minLength = 0;

        function _getFormHTML(ReviewAnswer $answer = null)
        {
            $html  = "<div class=errorMsg><div class='errorField' id='error_qid$this->questionID'></div></div>\n";
            $html .= "<textarea name='qid$this->questionID' id='qid$this->questionID' cols='60' rows='10'>\n";
            if($answer)
                $html .= $answer->text;
            $html .= "</textarea>\n";
            return $html;
        }

        function _getValidationCode()
        {
            $code  = "$('#error_qid$this->questionID').html('').parent().hide();\n";

            #Check to make sure it is the proper length
            $code .= "var l = $('#qid$this->questionID').val().split(' ').length;\n";
            $code .= "if(l < $this->minLength){\n";
            $code .= "$('#error_qid$this->questionID').html('Response needs to be at least $this->minLength words long');\n";
            $code .= "$('#error_qid$this->questionID').parent().show();\n";
            $code .= "error=true;}\n";
            return $code;
        }

        function _getOptionsFormHTML()
        {
            $html  = "<h2>Text Area Question Options</h2>\n";
            $html .= "<table align='left' width='100%'>\n";
            $html .= "<tr><td>Min Words</td><td><input type='text' name='minLength' id='minLength' value='$this->minLength' size='50'/><br>(Set to 0 for no minimum)</td></tr>\n";
            $html .= "<tr><td>&nbsp;</td></tr>\n";
            $html .= "</table>\n";
            return $html;
        }

        function _loadFromPost($POST)
        {
            $this->minLength = max(intval($POST['minLength']), 0);
        }

        function _getAnswerHTML(ReviewAnswer $answer = null)
        {
            if($answer)
            {
                if(!isset($answer->text))
                    throw new Exception("Answer does not have text component");
                return cleanString($answer->text);
            }
            return "(No Answer)";
        }

        protected function _loadAnswerFromPost($POST)
        {
            if(!array_key_exists("qid$this->questionID", $POST))
                throw new Exception("Missing answer to $this->questionID in POST");
            $ans = new ReviewAnswer();
            $ans->text = $POST["qid$this->questionID"];
            return $ans;
        }
    }

    class RadioButtonOption
    {
        public $label;
        public $score;

        function __construct($label, $score)
        {
            $this->label = $label;
            $this->score = $score;
        }
    }

    class RadioButtonQuestion extends ReviewQuestion
    {
        public $options = array();

        function _getFormHTML(ReviewAnswer $answer = null)
        {
            $html  = "<div class=errorMsg><div class='errorField' id='error_qid$this->questionID'></div></div>\n";
            for($i = 0; $i < sizeof($this->options); $i++)
            {
                $html .= "<input type='radio' name='qid$this->questionID' id='qid$this->questionID"."_$i' value='$i'";
                if($answer && $answer->int == $i)
                {
                    $html .= " checked";
                }
                $html .= "><label for='qid$this->questionID"."_$i'>&nbsp;<span style='display:block;margin-left:30px;margin-top:-20px'>".cleanString($this->options[$i]->label)."</span></label><br>\n";
            }

            /*
            $html .= "<table width='100%'><tr>\n";
            for($i = 0; $i < sizeof($this->options); $i++)
            {
                $html .= "<td><input type='radio' name='qid$this->questionID' id='qid$this->questionID"."_$i' value='$i'";
                if($answer && $answer->int == $i)
                {
                    $html .= " checked";
                }
                $html .= "><label for='qid$this->questionID"."_$i'>&nbsp;".cleanString($this->options[$i]->label)."</label></td>\n";
            }
            $html .= "</tr></table>\n";
             */
            return $html;
        }

        function _getValidationCode()
        {
            $code  = "$('#error_qid$this->questionID').html('').parent().hide();\n";

            $code .= "if ($('input[name=qid$this->questionID]:checked').length == 0) {\n";
            #Check to make sure it is the proper length
            $code .= "$('#error_qid$this->questionID').html('One radio button needs to be selected');\n";
            $code .= "$('#error_qid$this->questionID').parent().show();\n";
            $code .= "error=true;}\n";
            return $code;
        }

        function _getOptionsFormHTML()
        {
            $labels = array_reduce($this->options, function($v, $w) { return "$v\n$w->label";});
            $scores = array_reduce($this->options, function($v, $w) { return "$v\n$w->score";});

            $html  = "<h2>Radio Button Question Options</h2>\n";
            $html .= "For every one line string that you want as an option, put a float in the same row in the scores box. Make sure you don't have accidental empty lines<br>\n";
            $html .= "<table>\n";
            $html .= "<tr><td><h2 style='text-align:center'>Options</h2></td><td><h2 style='text-align:center'>Scores</h2></td></tr>\n";
            $html .= "<tr><td style='text-align:center'><textarea wrap='off' name='options' id='options' cols='30' rows='10'>";
            $html .= $labels;
            $html .= "</textarea></td>\n";
            $html .= "<td style='text-align:center'><textarea wrap='off' name='scores' id='scores' cols='30' rows='10'>";
            $html .= $scores;
            $html .= "</textarea></td></tr>\n";
            $html .= "<tr><td><div class=errorMsg><div class='errorField' id='error_fewOptions'></div></div></td><td><div class=errorMsg><div class='errorField' id='error_fewScores'></div></div></td></tr>\n";
            $html .= "<tr><td>&nbsp;</td></tr>\n";
            $html .= "</table>\n";
            return $html;
        }

        function _getValidateOptionsCode()
        {
            $code  = "$('#error_fewOptions').html('').parent().hide();\n";
            $code .= "$('#error_fewScores').html('').parent().hide();\n";

            #Check to make sure it is the proper length
            $code .= "var optLen = $('#options').val().split('\\n').length;\n";
            $code .= "var scoreLen = $('#scores').val().split('\\n').length;\n";
            $code .= "if(optLen < scoreLen){\n";
            $code .= "$('#error_fewOptions').html('There are more scores than options');\n";
            $code .= "$('#error_fewOptions').parent().show();\n";
            $code .= "error=true;}\n";

            $code .= "if(optLen > scoreLen){\n";
            $code .= "$('#error_fewScores').html('There are more options than scores');\n";
            $code .= "$('#error_fewScores').parent().show();\n";
            $code .= "error=true;}\n";
            return $code;
        }

        function _loadFromPost($POST)
        {
            $labels = explode("\n", str_replace("\r", "", $POST['options']));
            $scores = array_map(function($v) { return floatval($v); }, explode("\n", str_replace("\r", "", $POST['scores'])));

            if(sizeof($labels) != sizeof($scores))
            {
                throw new Exception("Scores and labels are not of the same size!");
            }
            $this->options = array();
            for($i = 0; $i < sizeof($labels); $i++)
            {
                $this->options[] = new RadioButtonOption($labels[$i], $scores[$i]);
            }
        }

        function getScore(ReviewAnswer $answer = null)
        {
            if($answer)
            {
                if($answer->int < sizeof($this->options) && $answer->int >= 0)
                    return $this->options[$answer->int]->score;
                else
                    throw new Exception("An invalid answer was detected with index $answer->int");
            }
            return parent::getScore($answer);
        }

        function _getAnswerHTML(ReviewAnswer $answer = null)
        {
            if($answer)
            {
                if(!isset($answer->int))
                    throw new Exception("Answer does not have an integer component");

                if($answer->int < sizeof($this->options) && $answer->int >= 0)
                    return $this->options[$answer->int]->label;
                else
                    throw new Exception("An invalid answer was detected with index $answer->int");
            }
            return "(No Answer)";
        }

        protected function _loadAnswerFromPost($POST)
        {
            if(!array_key_exists("qid$this->questionID", $POST))
                throw new Exception("Missing answer to $this->questionID in POST");
            $ans = new ReviewAnswer();
            $ans->int = intval($POST["qid$this->questionID"]);
            return $ans;
        }

    }
