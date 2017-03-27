<?php
require_once(dirname(__FILE__)."/submission.php");
require_once("common.php");


class DocumentSubmission extends Submission
{
    public $document= "";
    public $partnerID= "";

    function checkIfValidPartner($dataMgr, $partnerID, $submissionID) {
	    $assignment = get_peerreview_assignment();
	    try {
		    $partnerSubmissionID = $assignment->getSubmissionID(new UserID($partnerID));
	    }catch(Exception $e) {}
	    $isNewSubmission = !isset($submissionID) OR is_null($submissionID);
	    if ($partnerSubmissionID != NULL && $isNewSubmission) {
			    throw new Exception("Invalid partner. Your chosen partner may already have a submission.
				    Any updates made have been discarded. Please return to the homepage and try again. ");
	    }
	    if ($partnerSubmissionID !=NULL && 
		    $partnerSubmission->id != $submissionID->id) {
		    throw new Exception("Invalid partner. Your chosen partner may already have a submission.
			    Any updates made have been discarded. Please return to the homepage and try again. ");
	    }
    }

    function _loadFromPost($POST)
    {
            //There better be a file
            global $_FILES;
	    global $dataMgr;
	    $db = $dataMgr->getDatabase();
	    global $USERID;
	    if(isset($POST["deleteSubmissionCheck"]) && !strncmp("DELETE", $POST["deleteSubmissionText"],6)) {
		    $sh = $db->prepare("DELETE from peer_review_assignment_submissions WHERE submissionID = ?");
		    $sh->execute(array($this->submissionID->id));
		    redirect_to_main();
	    }
	    if(isset($POST["removePartner"])) {
		    $sh = $db->prepare("DELETE from peer_review_partner_submission_map where
			    		submissionID=? and submissionPartnerID=?");
		    $sh->execute(array($this->submissionID->id, $USERID->id));
		    $sh = $db->prepare("UPDATE peer_review_assignment_document set partnerID=0 
			    where submissionID =?");
		    $sh->execute(array($this->submissionID->id));
		    redirect_to_main();
	    }
	    if ($_FILES["documentfile"]["error"] == 4 &&
		     $this->submissionID != NULL) { 

		     $sh = $db->prepare( "SELECT `document`, 
			     `partnerID` FROM peer_review_assignment_document 
			     WHERE submissionID = ?;");
		     $sh->execute(array($this->submissionID->id));
		     $res = $sh->fetch();
		     if ($res->document == NULL) {
			     throw new Exception("No file uploaded, please return to the homepage and edit your submission to include a file!");
		     } else {
			     $this->document = $res->document;
			     $this->checkIfValidPartner($dataMgr, $POST["partnerID"], $this->submissionID);
			     $this->partnerID = $POST["partnerID"];
		     }
		    //check if there's already an associated file with the submission

	    } else if ($_FILES["documentfile"]["error"] == 4 && $this->submissionID == NULL) {
		    throw new Exception("No file uploaded, please return to the homepage and edit your submission to include a file!");
	    } else if ($_FILES["documentfile"]["error"] > 0){
                throw new Exception("File upload error: " . $_FILES["documentfile"]["error"]);;
	    } else {
		    //Try and get the image data
		    $this->document = file_get_contents($_FILES["documentfile"]["tmp_name"]);
		    $this->checkIfValidPartner($dataMgr, $POST["partnerID"], $this->submissionID);
		    $this->partnerID = $POST["partnerID"];
	    }
    }

    function _getHTML($showHidden)
    {
        global $page_scripts;
        global $dataMgr; 
	global $USERID;
	$userDisplayMap = $dataMgr->getUserDisplayMap();
        $script = get_ui_url(false)."prettify/run_prettify.js";
        if(strlen($this->submissionSettings->language)){
            $script .= "?lang=".$this->submissionSettings->language;
        }
        $page_scripts[] = $script;
        $html = "";
	if($this->partnerID) { 
		if ($this->partnerID != $USERID->id) {
			$html .="<p> Partner: ".$userDisplayMap[$this->partnerID];
		} else {
			$html .="<p> Partner: ".$userDisplayMap[$this->authorID->id];
		}
	}
        if(strlen($this->document)) {
            $html .= "<p>Document has been uploaded.</p>";
        }
        else {
            $html .= "<p>No document has been uploaded yet.</p>";
        }
        $html .= "<embed width='765' height='500' src='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=0")."'></embed><br>";
        $html .= "<a href='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=1")."'>Download</a><br>";

        return $html;
    }

    function _dumpRaw($forceDownload = false, $dumpHeaders = true)
    {
        if($dumpHeaders){
            header('Content-Type: application/pdf');
        }
        if($forceDownload)
            header("Content-Disposition: attachment; filename=$this->submissionID.".$this->submissionSettings->extension);

        echo $this->document;
    }

    function _getFormHTML()
    {
        $html = "";
        global $dataMgr; 
	global $USERID;
	$userDisplayMap = $dataMgr->getUserDisplayMap();
	$instructors = $dataMgr->getInstructors();
	if ($this->partnerID == $USERID->id) {
		$html .="<strong>".$userDisplayMap[$this->authorID->id]." </strong>has set you as their partner <br/>";
		$html .="If this is incorrect, check here to remove yourself as a partner (submit button below PDF)<br> ";
        	$html .= "<input type='checkbox' name='removePartner'/>&nbsp;Remove Self from Partner Pair<br><br>\n";

	} else {
		if($this->submissionID) {
			$html .= "If you would like to delete your submission, check the box, and type DELETE (in caps) <br/>";
			$html .=" Delete Submission: <input type='checkbox' name='deleteSubmissionCheck'> &nbsp&nbsp";
			$html .="<input type='text' name='deleteSubmissionText' value=''/> <br/>";
		}
		$html .= "Partner netID: <select name='partnerID'>";
		if ($this->partnerID) {
			$partnerName = $userDisplayMap[$this->partnerID];
			$html .="<option value='$this->partnerID' selected=selected>
				$partnerName (selected partner) </option>";
			$html .="<option value='0' >
			----No Partner---- </option>";
		} else {
			$html .="<option value='0' selected=selected>
			----No Partner---- </option>";
		}

		foreach ($userDisplayMap as $userID => $name) { 
			if ($userID == $this->partnerID) {
				continue;
			}
			if (strncmp("Anonymous", $name, 9) == 0) {
				continue;
			}
			if (in_array($userID, $instructors)) {
				continue;
			}
			$html .= "<option value='$userID'> $name </option>";
		}
		$html .= "</select>";
	}
        $html .= "";
        $html .= "<input type='hidden' name='documentMode' id='hiddenDocumentMode'>";
        $html .= "<div id='documentFileDiv'>";
	if ($this->partnerID != $USERID->id) {
		if(strlen($this->document)){
			$html .= "Document File: <input type='file' name='documentfile' id='documentFile'/><br>
				(Choose a file only if you want to modify the current upload) 
				<br> <br>";
		} else {
			$html .= "Document File: <input type='file' name='documentfile' id='documentFile'/><br><br>";
		}
	}
        if(strlen($this->document)) {
          $html .= "<embed width='800' height='500' src='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=0")."'></embed><br>";
          $html .= "<a href='".get_redirect_url("peerreview/rawviewsubmission.php?submission=$this->submissionID&download=1")."'>Download</a><br>";
        }
        $html .= "<div class=errorMsg><div class='errorField' id='error_file'></div></div><br>\n";
        $html .= "</div>\n";

        return $html;
    }

    function _getValidationDocument()
    {
        //only if we have topics do we need to ensure that one has been picked
        $document  = "if($(documentMode).val() == 'upload'){\n";
        $document .= "$('#error_file').html('').parent().hide();\n";
        $document .= "if(!$('#documentFile').val()) {";
        $document .= "$('#error_file').html('You must select a document file');\n";
        $document .= "$('#error_file').parent().show();\n";
        $document .= "error = true;}\n";
        $document .= "else {";
        if($this->submissionSettings->extension){
            $document .= "if(!stringEndsWith($('#documentFile').val().toLowerCase(), '.".$this->submissionSettings->extension."')) {";
            $document .= "$('#error_file').html('Your file must have a .".$this->submissionSettings->extension." extension');\n";
            $document .= "$('#error_file').parent().show();\n";
            $document .= "error = true;}\n";
        }
        $document .= "}";
        $document .= "}";
        $document .= "if(!error) { $(hiddenDocumentMode).val($(documentMode).val()); $(documentMode).val('0'); }";
        return $document;
    }

    function getFormAttribs() {
        return "enctype='multipart/form-data' action='api/upload'";
    }

    function getDownloadContents()
    {
        return $this->document;
    }

    function getDownloadSuffix()
    {
        return $this->submissionSettings->extension;
    }

};

class DocumentSubmissionSettings extends SubmissionSettings
{
    public $extension = "";

    function getFormHTML()
    {
        $html  = "<table width='100%' align='left'>\n";
        $html .= "<tr><td width='190px'>File extension </td><td><input type='text' name='documentExtension' value='$this->extension'/></td></tr>\n";
        $html .= "<tr><td colspan='2'>Lower case. Leave blank if you want any type of file</td></tr>\n";
        $html .= "</table>\n";
        return $html;
    }

    function loadFromPost($POST)
    {
        //We need to figure out the topics
        if(!array_key_exists("documentExtension", $POST))
            throw new Exception("Failed to get the extension from POST");
        $this->extension= $POST["documentExtension"];
    }
};

class DocumentPDOPeerReviewSubmissionHelper extends PDOPeerReviewSubmissionHelper
{
    function saveAssignmentSubmissionSettings(PeerReviewAssignment $assignment, $isNewAssignment)
    {
        //Delete any old settings, and just write in the new ones
        //$sh = $this->prepareQuery("saveDocumentAssignmentSubmissionSettingsQuery", "INSERT INTO peer_review_assignment_document_settings (assignmentID, documentExtension) VALUES (?, ?) ON DUPLICATE KEY UPDATE documentExtension = ?;");
        $sh = $this->prepareQuery("saveDocumentAssignmentSubmissionSettingsQuery", "REPLACE INTO peer_review_assignment_document_settings (assignmentID, documentExtension) VALUES (?, ?);");
        $sh->execute(array($assignment->assignmentID, $assignment->submissionSettings->extension));
    }

    function loadAssignmentSubmissionSettings(PeerReviewAssignment $assignment)
    {
        $sh = $this->prepareQuery("loadDocumentAssignmentSubmissionSettingsQuery", "SELECT documentExtension FROM peer_review_assignment_document_settings WHERE assignmentID = ?;");
        $sh->execute(array($assignment->assignmentID));
        $res = $sh->fetch();
        $assignment->submissionSettings = new DocumentSubmissionSettings();
        if($res->documentExtension){
            $assignment->submissionSettings->extension = $res->documentExtension;
        }
    }

    function getAssignmentSubmission(PeerReviewAssignment $assignment, SubmissionID $submissionID)
    {
        $document = new DocumentSubmission($assignment->submissionSettings, $submissionID);
	$sh = $this->prepareQuery("getDocumentSubmissionQuery", "SELECT `document`, 
		`partnerID` FROM peer_review_assignment_document 
	       	WHERE submissionID = ?;");
        $sh->execute(array($submissionID));
	#$sh = $this->prepareQuery("getAuthorFromSubmission", "SELECT `authorID` from 
        if(!$res = $sh->fetch())
            throw new Exception("Failed to get document submission '$submissionID'");
        $document->document = $res->document;
	$document->partnerID = $res->partnerID;
#	$document->authorID = $res->authorID;
        return $document;
    }

    function saveAssignmentSubmission(PeerReviewAssignment $assignment, Submission $document, $isNewSubmission)
    {
        // This is for a DB compatibility issue I ran into. -- Jake Collins
        //$sh = $this->prepareQuery("saveDocumentSubmissionQuery", "REPLACE INTO peer_review_assignment_document (submissionID, `document`) VALUES (?, ?);");
        //$sh->execute(array($document->submissionID, $document->document));

        // This is more standard for this codebase.
        //$sh = $this->prepareQuery("saveDocumentSubmissionQuery", "INSERT INTO peer_review_assignment_document (submissionID, `document`) VALUES (?, ?) ON DUPLICATE KEY UPDATE document = ?;");
        $saveDocumentSubmissionQuery = $this->prepareQuery("saveDocumentSubmissionQuery", "REPLACE INTO peer_review_assignment_document (submissionID, `document`, `partnerID`) VALUES (?, ?, ?);");
#	$sh = $this->prepareQuery("savePartnerMapDocumentSubmissionQuery", "REPLACE INTO peer_review_partner_submission_map (submissionID, submissionOwnerID, submissionPartnerID) VALUES (?, ?, ?)");
        //$sh->execute(array($document->submissionID, $document->document, $document->document));
        $saveDocumentSubmissionQuery->execute(array($document->submissionID, $document->document, $document->partnerID));
	if ($document->partnerID != 0) { 
		$savePartnerMappingQuery = $this->prepareQuery("savePartnerMappingQuery", "REPLACE INTO peer_review_partner_submission_map (submissionID, submissionOwnerID, submissionPartnerID) VALUES (?,?,?);");
		$savePartnerMappingQuery->execute(array($document->submissionID, $document->authorID, $document->partnerID));
	} else {
		$deletePartnerMapping  = $this->prepareQuery("getPartnerMappingQuery", "DELETE from peer_review_partner_submission_map where submissionID = ?");
		$deletePartnerMapping->execute(array($document->submissionID));
	}
    }
}
