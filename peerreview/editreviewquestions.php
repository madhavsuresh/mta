<?php
require_once("inc/common.php");
try
{
    $title .= " | Edit Review Questions";
    $authMgr->enforceInstructor();
    $dataMgr->requireCourse();

    #Depending on the action, we do different things
    $action = '';
    if(array_key_exists('action', $_GET)) {
        $action = $_GET['action'];
    }
    if(array_key_exists('questionid', $_GET)) {
        $questionID = new QuestionID($_GET['questionid']);
    }

    $assignment = get_peerreview_assignment();
    switch($action){	
    case 'moveUp':
        if(!isset($questionID)) {
            throw new Exception("No question id specified");
        }
        $assignment->moveReviewQuestionUp($questionID);
        redirect_to_page("?assignmentid=$assignment->assignmentID");
        break;
    case 'moveDown':
        if(!isset($questionID)) {
            throw new Exception("No question id specified");
        }
        $assignment->moveReviewQuestionDown($questionID);
        redirect_to_page("?assignmentid=$assignment->assignmentID");
        break;
    case "deleteconfirmed":
        if(!isset($questionID)) {
            throw new Exception("No question id specified");
        }
        $assignment->deleteReviewQuestion($questionID);
        redirect_to_page("?assignmentid=$assignment->assignmentID");
        break;
    case "delete":
        if(!isset($questionID)) {
            throw new Exception("No question id specified");
        }
        $question = $assignment->getReviewQuestion($questionID);
        $content .= "<div class='contentTitle'><h1>Delete Review Question ".$question->name."<h1></div>\n";
        $content .= "<div style='text-align:center'>\n";
        $content .= "Are you sure you wish to remove this question? All student responses will be deleted\n";
        $content .= "<table width='100%'><tr>\n";
        $content .= "<td style='text-align:center'><a href='".get_redirect_url("?assignmentid=$assignment->assignmentID")."'>Cancel</a></td>\n";
        $content .= "<td style='text-align:center'><a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=deleteconfirmed&questionid=$questionID")."'>Confirm</td></tr></table>\n";
        $content .= "</div>\n";
        render_page();
        break;
    case "create":
        $content .= "<table>\n";
        //Make a viewer of the types for them to pick from
        foreach($PEER_REVIEW_QUESTION_TYPES as $type => $name)
        {
            $content .= "<tr><td><a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&type=$type&action=edit")."'>$name</a></td></tr>\n";
        }
        $content .= "</table>\n";
        render_page();
        break;
	case "upload":
		$content .= '<h2>Upload Class List</h2>';
        $content .= "Class lists must be headerless CSV files (not tab separated like Open Office does by default), with the following order:<br>Last Name, First Name, Student ID Number, Account Name, User Type (one of student ,instructor or marker)<br><br>";
        $content .= "<form action='?assignmentid=$assignment->assignmentID&action=uploadpost' method='post' enctype='multipart/form-data'>\n";
        $content .= "<label for='file'>Filename:</label>\n";
        $content .= "<input type='file' name='file' id='file'><br>\n";
        $content .= "<input type='submit' name='submit' value='Upload'>\n";
        $content .= "</form>\n";
        render_page();
		break;
	case "uploadpost":
        //Parse through the uploaded file and insert all the questions as required
        if ($_FILES["file"]["error"] > 0)
        {
            throw new RuntimeException("Error reading uploaded HTML: " . $_FILES["file"]["error"]);
        }
        else
        {
        	$contents = file_get_contents($_FILES["file"]["tmp_name"]);
			$newcontents = preg_replace('/<!--(.|\s)*?-->/s', '', $contents);
			//preg_match('/<h1>(.*)<\/h1>/i', $newcontents, $matches, PREG_OFFSET_CAPTURE);
			eat($newcontents, $assignment);
			/*$questions = array();
           	$i = 0;
           	$isQuestionContent= 0;
           	$questionHeading = "";
			try{0
				foreach($questions as $question)
				{
					if($question->type == "radio")
					{
						$radioquestion = new RadioButtonQuestion(NULL, $question->name, $question->question);
						foreach($question->options as $label => $score)
						{
							$radioquestion->options[] = new RadioButtonOption($label, $score);
						}
						$assignment->saveReviewQuestion($radioquestion);
					}
					elseif($question->type == "textarea")
					{
						$textareaquestion = new TextAreaQuestion(NULL, $question->name, $question->question);
						$assignment->saveReviewQuestion($textareaquestion);
					}
				}
			}catch(Exception $e){
				
			}*/	
        }
		render_page();
        //redirect_to_page("?assignmentid=$assignment->assignmentID");
        break;
    case 'save':
        $type = require_from_get("type");
        $id = null;
        if(array_key_exists("questionid", $_GET)) {
            $id = new QuestionID($_GET["questionid"]);
        }
        $question = new $type($id, null, null);
        $question->loadFromPost($_POST);
        $assignment->saveReviewQuestion($question);

        redirect_to_page("?assignmentid=$assignment->assignmentID");
        break;
    case 'edit':
        $questionIDGet='';
        if(array_key_exists("type", $_GET))
        {
            $question = new $_GET["type"](NULL, "", "");
        }
        else if(isset($questionID))
        {
            $question = $assignment->getReviewQuestion($questionID);
            $questionIDGet="&questionid=$questionID";
        }
        else
        {
            throw new Exception("Couldn't figure out what to edit");
        }

        #Spit out the site preamble
        $content .= "<h1>Edit Review Question $question->name</h1>";
        #Begin the validate function
        $content .= "<script type='text/javascript'> $(document).ready(function(){ $('#editor').submit(function() {\n";
        $content .= "var error = false;\n";
        $content .= $question->getValidateOptionsCode();
        $content .= "if(error){return false;}else{return true;}\n";
        $content .= "}); }); </script>\n";

        $questionType=get_class($question);
        $content .= "<form id='editor' name='editor' action='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=save$questionIDGet&type=$questionType")."' method='post'>";
        $content .= $question->getOptionsFormHTML();
        $content .= "<br><input type='submit' name='action' value='Save' />\n";
        $content .= "</form>\n";

        render_page();
        break;
    default:
        $reviewQuestions = $assignment->getReviewQuestions();

        $content .= "<h1>Edit Review Questions</h1>\n";
        //$content .= "Note: If you change the order of questions or remove one after someone has submitted a review, horrible things can happen.<br><br>\n";

        #Give them the option of creating an assignment
        $content .= "<table align='left'><tr>\n";
        $content .= "<td><a title='Create new question' href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=create")."'><div class='icon new'></div></a</td>";
        $content .= "</tr></table>";
        
		$content .= "<a href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=upload")."'>Upload Questions</a><br>\n";

        $content .= "<table align='left' width='100%'>\n";
        $currentRowType = 0;
        for($i = 0; $i < sizeof($reviewQuestions); $i++)
        {
            $question = $reviewQuestions[$i];

            $content .= "<tr class='rowType$currentRowType'>\n";

            //$content .= "<form name='editForm$i' id='editForm$i' action='?assignmentid=$assignment->assignmentID&action=edit&index=$i' method='post'>\n";
            $content .= "<td width='150px'><table><tr>\n";
            $content .= "<td><a title='Move Up' href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=moveUp&questionid=$question->questionID")."'><div class='icon moveUp'></div></a</td>\n";
            $content .= "<td><a title='Move Down' href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=moveDown&questionid=$question->questionID")."'><div class='icon moveDown'></div></a></td>\n";
            $content .= "<td><a title='Delete' href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=delete&questionid=$question->questionID")."'><div class='icon delete'></div></a></td>\n";
            $content .= "<td><a title='Edit' href='".get_redirect_url("?assignmentid=$assignment->assignmentID&action=edit&questionid=$question->questionID")."'><div class='icon edit'></div></a></td>\n";
            $content .= "</tr></table></td>\n";
            $content .= "<td>$question->name</td>\n";

            $currentRowType = ($currentRowType+1)%2;
        }
        $content .= "<tr><td>&nbsp;</td><td>\n";
        $content .= "</table>\n";

        render_page();
    }
}catch(Exception $e){
    render_exception_page($e);
}
$j = 0;
function eat($newcontents, $assignment){
		global $content;
    	preg_match('/<h1>(.*)<\/h1>/i', $newcontents, $matches, PREG_OFFSET_CAPTURE);
		if(isset($matches[1])){
				$questionname = $matches[1][0];
				$content .= $questionname;
				$newercontents = substr($newcontents, $matches[1][1]);
				preg_match('/<h1>(.*)<\/h1>/i', $newercontents, $matches2, PREG_OFFSET_CAPTURE);
				if(isset($matches2[1]))
					$portion = substr($newercontents, 0, $matches2[1][1]);
				else
				{
					$portion = $newercontents;
					print_r($questionname);
				}
				preg_match('/<p>(.*)<\/p>/s', $portion, $matches3, PREG_OFFSET_CAPTURE);
				if(isset($matches3[1]))
				{
					$p = $matches3[1][0];
					if(substr_count($p, "Accumulate") > 0)
					{
						preg_match_all('/(\d+)\s+points/i', $p, $points);
						$question = new RadioButtonQuestion(NULL, $questionname, $p);
					}
					else
						$question = new TextAreaQuestion(NULL, $questionname, $p);
					$assignment->saveReviewQuestion($question);
					eat($newercontents, $assignment);
				}
			}
}
?>
