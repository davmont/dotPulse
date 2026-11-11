<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Annotations
 * Directory: annotations
 * Version:   0.2.6
 * Type:      user
 * UI Name:   Annotations
 * UI Icon: annotations.png
 *
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
	 * Developed by Christian Kien																		*
	 *	In cooperation with Jose Grincho, PCMO															*
	 * 	European Investment Fund																	*
	 *																						*
	 *	www.EIF.org																			*
	 *	christian.kien@web.de																			*
	 *	j.grincho@eif.org																			*
	 *																						*
	 *	Suggestions, questions or criticism welcome! Please e-mail us!												*
	 *	Please read the readme.txt																	*
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
 *
 * Adds the possibility to add/adjust/remove annotations on projects by date
 *
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Annotations';				// name the module
$config['mod_version'] = '0.2.8';					// add a version number
$config['mod_directory'] = 'annotations';			// tell dotProject where to find this module
$config['mod_setup_class'] = 'CSetupAnnotations';	// the name of the PHP setup class (used below)
$config['mod_type'] = 'user';						// 'core' for modules distributed with dP by standard, 'user' for additional modules from dotmods
$config['mod_ui_name'] = 'Annotations';				// the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'annotations.gif';						// name of a related icon
$config['mod_description'] = 'Annotations';			// some description of the module
$config['mod_config'] = false;						// show 'configure' link in viewmods

// show module configuration with the dPframework (if requested via http)
if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupAnnotations {

	function remove() {		// run this method on uninstall process
		$q = new DBQuery();
		$q -> dropTable('annotations');
		$q -> exec();// remove the pcmc table from database
		$q -> clear();
		$q -> dropTable('annotations_histories');
		$q -> exec();	// remove the pcmc table from database
		
	//remove extra SysVals created on installation time
		$q -> clear();		
		$q -> setDelete('sysvals');
		$q -> addWhere('sysval_title="AnnotationsPoints"');
		$q -> exec(); 
		return null;
	}

	function install() {
		$ok= true;
		$q = new DBQuery();
		$q -> createTable('annotations');
		$q -> createDefinition("( " .					// prepare the creation of a dbTable
			"  annotation_id int(11) unsigned NOT NULL auto_increment" .
			", annotation_project int(11) unsigned NOT NULL" .
			", annotation_previous TEXT NULL DEFAULT NULL" .
			", annotation_next TEXT NULL DEFAULT NULL" .
			", annotation_revised_priority int(11) NULL DEFAULT NULL" .
			", annotation_scope int(11) NULL DEFAULT NULL" .
			", annotation_resources int(11) NULL DEFAULT NULL" .
			", annotation_time int(11) NULL DEFAULT NULL" .
			", annotation_scope_desc TEXT NULL DEFAULT NULL" .
			", annotation_resources_desc TEXT NULL DEFAULT NULL" .
			", annotation_time_desc TEXT NULL DEFAULT NULL" .
			", annotation_date TIMESTAMP NULL" .
			", annotation_strategy TINYINT NULL DEFAULT '1' ".
			", annotation_sholders TINYINT NULL DEFAULT '1' ".
			", annotation_risks TINYINT NULL DEFAULT '1' ".
			", annotation_sizing TINYINT NULL DEFAULT '1' ".
			", annotation_horizontality TINYINT NULL DEFAULT '1' ".
			", annotation_costbenefit TINYINT NULL DEFAULT '1' ".
			", annotation_rationale TEXT NULL ".
			", annotation_must TINYINT NULL DEFAULT '0' ".
			", annotation_flag TINYINT NULL DEFAULT '0' ".
			", annotation_must_rationale TEXT NULL ".
			", annotation_team TEXT NULL ".
			", annotation_subject TEXT NULL ".
			", PRIMARY KEY  (annotation_id)" .			
			") ENGINE=MyISAM;");
		$ok = $ok && $q->exec();
		
		// Create History table
		$q -> clear();
		$q -> createTable('annotations_histories');
		$q -> createDefinition(' (
				 annotation_history_id int(11) NOT NULL auto_increment,
				 annotation_history_annotation int(11) NOT NULL,
				 annotation_history_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				 annotation_history_strategy tinyint(4) NOT NULL,
				 annotation_history_sholders tinyint(4) NOT NULL,
				 annotation_history_risks tinyint(4) NOT NULL,
				 annotation_history_sizing tinyint(4) NOT NULL,
				 annotation_history_horizontality tinyint(4) NOT NULL,
				 annotation_history_costbenefit tinyint(4) NOT NULL,
				 annotation_history_rationale text,
				 annotation_history_user_id int(11) NULL DEFAULT NULL,
				 annotation_history_scope tinyint(4) NULL DEFAULT NULL,
				 annotation_history_scope_desc text,
				 annotation_history_resources tinyint(4) NULL DEFAULT NULL,
				 annotation_history_resources_desc text,
				 annotation_history_time tinyint(4) NULL DEFAULT NULL,
				 annotation_history_flag tinyint(4) NULL DEFAULT NULL,
				 annotation_history_time_desc text,
				 annotation_history_previous text,
				 annotation_history_next text,
				 annotation_history_subject TEXT,
				 annotation_history_must tinyint(4) DEFAULT NULL,
				 annotation_history_must_rationale text,
				 annotation_history_project int(11) NOT NULL,
				  PRIMARY KEY  (annotation_history_id)
				) ENGINE=MyISAM ;');

		$ok = $ok && $q -> exec();
		db_error();
		
		//inserting some extra sysvals
		
		$sql = 'REPLACE INTO '.dPgetConfig('dbprefix', '').'sysvals ( sysval_key_id, sysval_title, sysval_value ) ' .
			   'VALUES ( ' .
			   ' "1","OpportunitiesPriorities","1|- \n2|Must \n3|High \n4|Medium \n5|Low \n6|Option" ' .
			   ' ) , ( ' .
			   ' "1","AnnotationsPoints","0|grey \n1|red \n2|orange \n3|green" ' .
			   ' ) , ( ' .
			  // ' "1","OpportunitiesContexts","1|- \n2|Institution \n3|Management \n4|Front Office \n5|Middle Office \n6|Back Office \n7|Support" ' .			   
			  // ' ) , ( ' .
			   ' "1","OpportunitiesSizings","1|- \n2|XLarge \n3|Large \n4|Medium \n5|Small \n6|XSmall" ' .			   
			   ' ) , ( ' .
			   ' "1","OpportunitiesStatus","1|Open \n2|Analysis \n3|ToProject \n4|Archieved" ' .			   
			   ' ) , ( ' .
			   ' "1","OpportunitiesPoints","0|0 \n1|1 \n2|2 \n3|3 \n4|4 \n5|5" ' .
			   ' ) , ( ' .
			   ' "1","OpportunitiesYesNo","1|no \n2|yes" ' .
			   ' ) , ( ' .
//			   ' "1","OpportunitiesPM","1|- \n2|Abdel \n3|Jose \n4|Monica \n5|Silvia \n6|Sophie" ' .
//			   ' ) , ( ' .
			   ' "1","OpportunitiesBA","1|- \n2|FIN \n3|MIS \n4|VC \n5|GS \n6|OTH \n7|LO" ' .
			   ');';
		db_exec( $sql ); db_error();						// execute the queryString		
		if($ok){		
			return null;
		}
		return $ok;	
	}
	
	//needed ?
	
	function upgrade($old_version = null) {
		// 0.2.8: tables in utf8mb4 (they were latin1: text outside Latin-1 was lost).
		return dPconvertTablesToUtf8mb4(array('annotations', 'annotations_histories'));
	}
}

?>