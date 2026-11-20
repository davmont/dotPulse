<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* FILES $Id: files.class.php,v 1.1 2004/08/30 19:02:41 jcgonz Exp $ */
require_once( $AppUI->getSystemClass( 'libmail' ) );
require_once( $AppUI->getSystemClass( 'dp' ) );
require_once( $AppUI->getModuleClass( 'tasks' ) );
require_once( $AppUI->getModuleClass( 'projects' ) );
/**
* File Class
*/
class CMngDocumentFile extends CDpObject {

	var $SGD_id = NULL;
	var $SGD_name = NULL;
	var $SGD_description = NULL;
	var $SGD_type = NULL;
	var $SGD_date = NULL;
	var $SGD_parent = NULL;
	var $SGD_state = NULL;

	
	function __construct() {
		parent::__construct( 'SGD', 'SGD_id' );
	}

	// Uploads are kept outside the web root, under the record id rather than the name
	// the user gave the file. documentview.php sends them.
	function filePath() {
		return DP_BASE_DIR . '/files/mngdocument/' . (int)$this->SGD_id;
	}

	function check() {
	// ensure the integrity of some variables
		$this->SGD_id = intval( $this->SGD_id );
		$this->SGD_parent = intval( $this->SGD_parent );

		return NULL; // object is ok
	}

	function deletefile() {
		global $AppUI;
		$this->_message = "deleted";
		
	// remove the file from the file system
		@unlink( $this->filePath() );
	// delete the main table reference
		$sql = "DELETE FROM " . dPgetConfig('dbprefix', '') . "SGD WHERE SGD_id = " . (int)$this->SGD_id;
		if (!db_exec( $sql )) {
			return db_error();
		}
		return NULL;
	}

	function deletedirectory() {
		global $AppUI;
		$this->_message = "deleted";
		

	// delete the main table reference
		$sql = "DELETE FROM " . dPgetConfig('dbprefix', '') . "SGD WHERE SGD_id = " . (int)$this->SGD_id;
		if (!db_exec( $sql )) {
			return db_error();
		}
		return NULL;
	}

// move a file from a temporary (uploaded) location to the file system
	function moveTemp( $upload ) {
		global $AppUI;
	// check that directories are created
		if (!$this->SGD_id) {
			return false;
		}
		if (!is_dir(DP_BASE_DIR . '/files/mngdocument')) {
		    $res = mkdir( DP_BASE_DIR . '/files/mngdocument', 0777 );
		    if (!$res) {
			     return false;
			 }
		}

		$this->_filepath = $this->filePath();
	// move it
		$res = move_uploaded_file( $upload['tmp_name'], $this->_filepath );
		if (!$res) {
		    return false;
		}
		return true;
	}
}



/**
* CFileLog Class
*/
class CFileLog extends CDpObject {
	var $SGD_Logs_id = NULL;
	var $SGD_Logs_document_name = NULL;
	var $SGD_Logs_user_id = NULL;
	var $SGD_Logs_date = NULL;
	var $SGD_Logs_action = NULL;

	function __construct() {
		parent::__construct( 'SGD_Logs', 'SGD_Logs_id' );
	}
}
?>
