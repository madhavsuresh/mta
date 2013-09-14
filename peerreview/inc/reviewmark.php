<?php
require_once(dirname(__FILE__)."/mark.php");

class ReviewMark extends Mark
{
    public $reviewPoints = 0;

    function __construct($score=null, $comments=null, $automatic=false, $reviewPoints = 0)
    {
        parent::__construct($score, $comments, $automatic);
        $this->reviewPoints = $reviewPoints;
    }

    function getFormHTML()
    {
        $html  = "<h1>Mark</h1>\n";
        $html .= "<h2>Score</h2>\n";
        $html .= "<input type='text' value='$this->score' name='score'>\n";
        $html .= "<h2>Review Points</h2>\n";
        $html .= "<input type='text' value='$this->reviewPoints' name='reviewPoints'>\n";
        $html .= "<h2>Comments</h2>\n";
        $html .= "<textarea name='comments' cols='60' rows='10'>\n";
        $html .= "$this->comments";
        $html .= "</textarea>\n";

        return $html;
    }

    function loadFromPost($POST)
    {
        $res = parent::loadFromPost($POST);
        if(is_null($res)){
            if(!isset($POST["reviewPoints"])){
                return "Missing reviewPoints in POST";
            }
            $this->reviewPoints = $POST["reviewPoints"];
        }
        return NULL;
    }

    function getHTML($outOf=null)
    {
        if($this->isValid)
        {
            $html = "$this->score";
            if(!is_null($outOf))
                $html .= "/$outOf";
            $html .= " for a total of $this->reviewPoints review points.";
            if($this->isAutomatic) {
                $html .= "<br/><br/>This mark was assigned automatically\n";
            } else if($this->comments){
                $html .= "<br/><br/>".nl2br($this->comments)."\n";
            }
        }else{
            $html = "(No Mark)\n";
        }
        return $html;
    }
// 
//     function getSummaryString($outOf=null)
//     {
//         if($this->isValid)
//         {
//             $str = precisionFloat($this->score);
//             if(!is_null($outOf))
//                 $str .= "/$outOf";
//             if(strlen($this->comments) != 0)
//                 $str .= "+";
//             if($this->isAutomatic)
//                 $str .= "A";
//             return $str;
//         }
//         return "--";
//     }

    function getReviewPoints()
    {
        return precisionFloat($this->reviewPoints);
    }
};


