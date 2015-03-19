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
        $content .= "Class lists must be an HTML file following a specifc HTML format. Please contact admin for this HTML template. Do not remove the commented guide at the top.<br><br>";
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
        	//Check if it is an HTML file
        	preg_match('/.+\.html$/', $_FILES["file"]["name"], $isHTMLfile);
        	if(empty($isHTMLfile))
        	{
        		throw new RuntimeException("File uploaded is not in HTML format.");
				//redirect_to_page("?assignmentid=$assignment->assignmentID&action=uploaderror&type=0");
			}	
        	//Based on Professor Ron Garcia's HTML template
        	$contents = file_get_contents($_FILES["file"]["tmp_name"]);
        	
        	//Remove commented out rubric guide if it exists
			/*preg_match('/<!--(.*)-->/s', $contents, $commentmatches, PREG_OFFSET_CAPTURE);
			preg_match('/<h1>(.*)<\/h1>/i', $contents, $headermatches, PREG_OFFSET_CAPTURE);
			preg_match('/(-->)/i', $contents, $endcommentmatches, PREG_OFFSET_CAPTURE);
			if($commentmatches[1][1] <= $headermatches[1][1])
			{
				$contents = substr($contents, $endcommentmatches[1][1] + 3);
			}*/
			
			//Assert and remove commented out rubric guide
			preg_match('/<!--\s*<h1>Problem 0\.0: Title<\/h1>(.*)-->/s', $contents, $rubricmatches, PREG_OFFSET_CAPTURE);
			preg_match('/(-->)/i', $contents, $endcommentmatches, PREG_OFFSET_CAPTURE);
			if(isset($rubricmatches[1]))
				$contents = substr($contents, $endcommentmatches[1][1] + 3);
			else
			{
				throw new RuntimeException("HTML file uploaded does not contain a distinct feature. Please contact admin for the correct HTML template.");
				//redirect_to_page("?assignmentid=$assignment->assignmentID&action=uploaderror&type=1");
			}
			
			//Gather all questions created
			$questions = array();
			
			while(1){
				//Check if there is a question heading
			    preg_match('/<h1>(.*)<\/h1>/i', $contents, $matches, PREG_OFFSET_CAPTURE);
				//If not, stop parsing
				if(!isset($matches[1]))
					break;
				//Get question name
				$questionname = $matches[1][0];
				//Remove header from remaining contents
				$contents = substr($contents, $matches[1][1]);
				//See where the next question header is
				preg_match('/<h1>(.*)<\/h1>/i', $contents, $matches2, PREG_OFFSET_CAPTURE);
				//If there is an upcoming question header get the contents up until that point
				if(isset($matches2[1]))
					$questiondetails = substr($contents, 0, $matches2[1][1]);
				else // if not then just continue
					$questiondetails = $contents;
				preg_match('/<p>(.*)<\/p>/s', $questiondetails, $matches3, PREG_OFFSET_CAPTURE);
				if(isset($matches3[1]))
				{
					$questionbody = $matches3[1][0];
					//Check if there is a point spread portion
					preg_match('/<!--(.*)-->/s', $questiondetails, $commentmatches2, PREG_OFFSET_CAPTURE);
					//Store all point options in array 'pointSpreadMatches'
					preg_match_all('/(\d+)\s?points?/s', $commentmatches2[1][0], $pointSpreadMatches);
					//If there is no point spread then it is a text area question
					if(empty($pointSpreadMatches[0]))
						$question = new TextAreaQuestion(NULL, $questionname, $questionbody);
					else //otherwise it is a radio button question
					{
						$question = new RadioButtonQuestion(NULL, $questionname, $questionbody);
						//Create options based on pointSpread matches and add to radio button question
						for($i = 0; $i < sizeof($pointSpreadMatches[1]); $i++)
						{
							$option = new RadioButtonOption($pointSpreadMatches[0][$i], $pointSpreadMatches[1][$i] + 0);
							$question->options[] = $option;
						}
					}
					//Add the question to array
					$questions[] = $question;
				}
			}
			//Reverse array for the intended order
			$reversed = array_reverse($questions);
			//Save questions to assignment
			foreach($reversed as $question)
			{
				$assignment->saveReviewQuestion($question);
			}
        }
        redirect_to_page("?assignmentid=$assignment->assignmentID");
        break;
	/*case "uploaderror":
		$content .= "<h2>Upload error</h3>";
		$uploaderrors = array("File uploaded is not in HTML format",
						"HTML file uploaded does not contain a distinct feature. Please contact admin for the correct HTML template.");
		if(array_key_exists('type', $_GET))
			$content .= $uploaderrors[$_GET['type']]."\n";
		else
			$content .= "Unknown upload error occured\n";
        $content .= "<form action='?assignmentid=$assignment->assignmentID&action=upload' method='post'>\n";
       	$content .= "<input type='submit' name='submit' value='Go back'>\n";
        $content .= "</form>\n";
		render_page();
		break;*/
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
?>
