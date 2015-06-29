<?php

class Mark
{
    public $score = 0;
    public $comments = "";
    public $isValid = false;
    public $isAutomatic;
	public $markTimestamp;

    function __construct($score=null, $comments=null, $automatic=false)
    {
        $this->isValid = !is_null($score);
        $this->score = $score;
        $this->comments = $comments;
        $this->isAutomatic = $automatic;
    }

    function getFormHTML()
    {
        $html  = "<h1>Mark</h1>\n";
        $html .= "<h2>Point Value</h2>\n";
        $html .= "<input type='text' value='$this->score' name='score'>\n";
        $html .= "<h2>Comments</h2>\n";
        $html .= "<textarea name='comments' cols='60' rows='10'>\n";
        $html .= "$this->comments";
        $html .= "</textarea>\n";
		if($this->markTimestamp) $html .= "<h4>Last Updated: ".date("Y-m-d H:i:s",$this->markTimestamp)."</h4>";

        return $html;
    }

    function loadFromPost($POST)
    {
        if(!isset($POST["score"]) || !isset($POST["comments"]) || !is_numeric($POST["score"]))
        {
            return "Could not load mark from POST";
        }
        $this->score = $POST["score"];
        $this->comments = $POST["comments"];
        return NULL;
    }

    function getHTML($outOf=null)
    {
        if($this->isValid)
        {
            $html = "$this->score";
            if(!is_null($outOf))
                $html .= "/$outOf";
            if($this->isAutomatic) {
                $html .= "<br/><br/>This mark was assigned automatically\n";
            } else if($this->comments){
                $html .= "<br/><br/>$this->comments\n";
            }
        }else{
            $html = "(No Mark)\n";
        }
        return $html;
    }

    function getSummaryString($outOf=null)
    {
        if($this->isValid)
        {
            $str = precisionFloat($this->score);
            if(!is_null($outOf))
                $str .= "/$outOf";
            if(strlen($this->comments) != 0)
                $str .= "+";
            if($this->isAutomatic)
                $str .= "A";
            return $str;
        }
        return "--";
    }

    function getScore()
    {
        return precisionFloat($this->score);
    }
};

