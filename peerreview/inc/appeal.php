<?php

class AppealMessage
{
    public $appealMessageID;
    public $matchID;
    public $authorID;
    public $message;
    public $appealType;

    function __construct($appealMessageID = NULL, $appealType, MatchID $matchID, UserID $authorID = NULL, $message="")
    {
        $this->appealMessageID = $appealMessageID;
        $this->appealType = $appealType;
        $this->authorID = $authorID;
        $this->matchID = $matchID;
        $this->message = $message;
    }

    function getFormHTML()
    {
        $html  = "<textarea name='message' cols='60' rows='40' class='mceEditor'>\n";
        $html .= htmlentities($this->message);
        $html .= "</textarea><br>\n";

        return $html;
    }

    function loadFromPost($POST)
    {
        $this->message= get_html_purifier()->purify($POST["message"]);
    }
}

class Appeal
{
    public $matchID;
    public $appealType;
    public $messages = array();

    function __construct(MatchID $matchID, $type)
    {
        $this->matchID = $matchID;
        $this->appealType = $type;
    }

    function getHTML()
    {
        global $dataMgr;

        //$this->messages = array(new AppealMessage(new UserID(122), "Hello"), new AppealMessage(new UserID(126), "Goodbye"));

        $userDisplayNameMap = $dataMgr->getUserDisplayMap();
        $html = "";
        foreach($this->messages as $message)
        {
            if($dataMgr->isInstructor($message->authorID))
            {
                //We put it on the right
                $html .= "<div style='width:80%;float:right'>\n";
            }
            else
            {
                //We put it on the left
                $html .= "<div style='width:80%;float:left'>\n";
            }
            $html .= "<h2>".$userDisplayNameMap[$message->authorID->id]."</h2>\n";
            $html .= $message->message;
            $html .= "</div>";
        }
        return $html;
    }
};

