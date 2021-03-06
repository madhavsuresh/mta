<?php

class Review
{
    private $assignment;
    public $submissionID;
    public $reviewerID;
    public $answers = array();
	public $reviewTimeStamp;

    function __construct(PeerReviewAssignment $assignment)
    {
        $this->assignment = $assignment;
    }

    function getHTML($showHiddenQuestions=false)
    {
        #The first line contains the score that this review gave, we need to gobble it up
        $html = "<h2 class='submission-score'>Score for Submission: ".precisionFloat($this->getScore())."<h2>\n";

        #Now we can start loading up questions
        $count = 1;
        foreach($this->assignment->getReviewQuestions() as $question)
        {
            if($showHiddenQuestions || !$question->hidden)
            {
                $count++;
                $mod = $count % 2;
                if(array_key_exists($question->questionID->id, $this->answers)) {
                    $html .= "<div class='question".$mod."'>".$question->getHTML($this->answers[$question->questionID->id])."</div>";
                } else {
                    $html .= "<div class='question".$mod."'>".$question->getHTML(null)."</div>";
                }
                $html .= "\n";
            }
        }
        return $html;
    }

    function getShortHTML()
    {
        $html = "";
        foreach($this->assignment->getReviewQuestions() as $question)
        {
            if(array_key_exists($question->questionID->id, $this->answers)) {
                $html .= $question->getShortHTML($this->answers[$question->questionID->id]);
            } else {
                $html .= $question->getShortHTML(null);
            }
            $html .= "\n";
        }
        return $html;
    }

    function getValidationCode()
    {
        $code = "";
        foreach($this->assignment->getReviewQuestions() as $question)
        {
            $code .= $question->getValidationCode();
        }
        return $code;
    }

    function getFormHTML()
    {
        global $SITEURL, $dataMgr;
        $html = "";

        //Now the actual editor stuff
        foreach($this->assignment->getReviewQuestions() as $question)
        {
            if(array_key_exists($question->questionID->id, $this->answers)) {
                $html .= $question->getFormHTML($this->answers[$question->questionID->id]);
            } else {
                $html .= $question->getFormHTML(null);
            }
            $html .= "\n";
        }

		if(ISSET($this->reviewTimestamp)) $html .= "<h4>Last Updated: ".date("Y-m-d H:i:s",$this->reviewTimestamp)."</h4>";

        return $html;
    }

    function loadFromPost($POST, $hideErrors = false)
    {
        foreach($this->assignment->getReviewQuestions() as $question)
        {
            try
            {
                $ans = $question->loadAnswerFromPost($POST);
                $this->answers[$question->questionID->id] = $ans;
            }
            catch(Exception $e)
            {
                if(!$hideErrors)
                    throw $e;
            }
        }
    }

    function getScore()
    {
        $score = 0;
	$multiplier = 1;
        foreach($this->assignment->getReviewQuestions() as $question)
        {

			if(array_key_exists($question->questionID->id, $this->answers)) {
				if ($question->name == 'Overall') {
					$multiplier =  (intval($question->options[$this->answers[$question->questionID->id]->int]->label))/10;
				} else {
					$score += $question->getScore($this->answers[$question->questionID->id]);
				}
			} else {
				$score += $question->getScore(null);
			}
		}
	$score *= $multiplier;
        return $score;
    }
};
