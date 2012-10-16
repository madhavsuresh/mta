<?php

class SpotCheck
{
    public $submissionID;
    public $checkerID;
    public $status;

    private static $options = array("pending"=>"Pending", "nochange"=>"No Change Needed", "change"=>"Change Needed");

    function __construct(SubmissionID $submissionID=null, UserID $checkerID=null, $status='pending')
    {
        $this->submissionID = $submissionID;
        $this->checkerID = $checkerID;
        if(!array_key_exists($status, SpotCheck::$options))
            throw new Exception("Invalid spot check status '$status'");
        $this->status = $status;
    }

    function getFormHTML()
    {
        $html  = "<input type='hidden' name='spotCheckSubmissionID' value='$this->submissionID' />\n";
        $html .= "<input type='hidden' name='spotCheckCheckerID' value='$this->checkerID' />\n";
        $html .= "Status: <select name='spotCheckStatus'>\n";
        foreach(SpotCheck::$options as $status=>$display)
        {
            $tmp = '';
            if($this->status == $status)
                $tmp = "selected";
            $html .= "<option value='$status' $tmp>".$display."</option>\n";
        }
        $html .= "</select><br>";
        return $html;
    }

    function getStatusString()
    {
        return SpotCheck::$options[$this->status];
    }

    function loadFromPost($POST)
    {
        if(!array_key_exists("spotCheckSubmissionID", $POST)
           || !array_key_exists("spotCheckCheckerID", $POST)
           || !array_key_exists("spotCheckStatus", $POST))
            throw new Exception("Missing data in POST");


        $this->submissionID = new SubmissionID($POST["spotCheckSubmissionID"]);
        $this->checkerID = new UserID($POST["spotCheckCheckerID"]);
        $this->status = $POST["spotCheckStatus"];
    }
};

