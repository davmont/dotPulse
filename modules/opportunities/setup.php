<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Name:      Opportunities
 * Directory: opportunities
 * Version:   0.1.3
 * Type:      user
 * UI Name:   Opportunities
 * UI Icon:
 */

// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Opportunities';		// name the module
$config['mod_version'] = '0.1.4';		// add a version number
$config['mod_directory'] = 'opportunities';		// tell dotProject where to find this module
$config['mod_setup_class'] = 'CSetupOpportunity';	// the name of the PHP setup class (used below)
$config['mod_type'] = 'user';			// 'core' for modules distributed with dP by standard, 'user' for additional modules from dotmods
$config['mod_ui_name'] = 'Opportunities';		// the name that is shown in the main menu of the User Interface
$config['mod_ui_icon'] = 'opportunities.png';	// name of a related icon
$config['mod_description'] = 'List of opportunities i.e. ideas or issues';	// some description of the module
$config['mod_config'] = false;			// show 'configure' link in viewmods

// show module configuration with the dPframework (if requested via http)
if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupOpportunity {

	function configure() {		// configure this module
	global $AppUI;
		$AppUI->redirect( 'm=opportunities&a=configure' );	// load module specific configuration page
  		return true;
	}

	function remove() {		// run this method on uninstall process
		$q = new DBQuery();
		//remove the main table
	     $q -> dropTable('opportunities');
	     $q -> exec();
		 
	     $q -> clear();
		//remove relation table (opportunities related to projects)
		 $q -> dropTable('opportunities_projects');
		 $q -> exec();
		 $q -> clear();
		 $q -> dropTable('opportunities_histories');
		 $q -> exec();
		 		
		 $q ->clear();
		//remove extra SysVals created on installation time
		$q -> setDelete('sysvals');
		$q -> addWhere(' sysval_title="OpportunitiesPriorities" ' .
				' OR sysval_title="OpportunitiesSizings" ' .
				' OR sysval_title="OpportunitiesStatus" ' .
				' OR sysval_title="OpportunitiesPoints" ' .
				' OR sysval_title="OpportunitiesYesNo" ' .
				' OR sysval_title="OpportunitiesPM" ' .
				' OR sysval_title="OpportunitiesBA" ');
		 $q -> exec();
		
		return null;
	}


	function upgrade( $old_version ) {
		// 0.1.4: tables in utf8mb4 (they were latin1: text outside Latin-1 was lost).
		return dPconvertTablesToUtf8mb4(array('opportunities', 'opportunities_histories', 'opportunities_projects'));
	}

	function install() {
				// creation of the maintable
				$q = new DBQuery();
				$q -> createTable('opportunities');
				$q -> createDefinition("( ".
		  " opportunity_id int(11) unsigned NOT NULL auto_increment, ".
		  " opportunity_name varchar(150) NOT NULL, ".
		  " opportunity_orig int(11) default NULL, ".
		  " opportunity_sponsor int(11) default NULL, ".
		  " opportunity_owner int(11) default NULL, ".
		  " opportunity_desc longtext, ".
		  " opportunity_sizing int(11) default NULL, ".
		  " opportunity_boundaries longtext, ".
		  " opportunity_background longtext, ".
		  " opportunity_curway longtext, ".
		  " opportunity_preyn int(11) default NULL, ".
		  " opportunity_precost varchar(50) default NULL, ".
		  " opportunity_preout longtext, ".
		  " opportunity_comments longtext, ".
		  " opportunity_strategy int(11) default NULL, ".
		  " opportunity_sholders int(11) default NULL, ".
		  " opportunity_horizontality int(11) default NULL, ".
		  " opportunity_rationale longtext, ".
		  " opportunity_project int(11) default NULL, ".
		  " opportunity_status int(11) default NULL, ".
		  " opportunity_pm int(11) default NULL, ".
		  " opportunity_created datetime default NULL, ".
		  " opportunity_lastupd timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, ".
		  " opportunity_risks int(11) default NULL, ".
		  " opportunity_costbenefit int(11) default NULL, ".
		  " opportunity_ba TEXT default NULL, ".	//system
		  " opportunity_proposal TEXT default NULL, ".	//system
		  " opportunity_must TINYINT default NULL, ".	
		  " opportunity_priority TINYINT default '0', ".	
		  " opportunity_must_rationale TEXT default NULL, ".	
		  " PRIMARY KEY  (opportunity_id), ".
		  " UNIQUE KEY opportunity_id (opportunity_id) ".
		" ) ENGINE=MyISAM ;");
		
		$q -> exec();
		
		//creation of a dependencies-help-table for dependencies between opportunitiess <-> projects
		// n,n realation between ops and projs
		$q -> clear();
		$q -> createTable('opportunities_projects');
		$q -> createDefinition(" (
					  opportunity_project_id int(11) unsigned NOT NULL auto_increment,
					  opportunity_project_projects int(11) unsigned NOT NULL,
					  opportunity_project_opportunities int(11) unsigned NOT NULL,
					  opportunity_project_description text,
					  PRIMARY KEY  (opportunity_project_id),
					  UNIQUE KEY opportunity_project_id (opportunity_project_id)
				) ENGINE=MyISAM;");
		$q -> exec();
		
		// Creating an history table
		$q -> clear();
		// Earlier versions left this table behind on remove.
		$histories_exist = db_loadResult("SHOW TABLES LIKE '" . db_escape(dPgetConfig('dbprefix', '') . 'opportunities_histories') . "'");
		if (!$histories_exist) {
		$q -> createTable('opportunities_histories');
		$q -> createDefinition(' (
				  opportunity_history_id int(11) unsigned NOT NULL auto_increment,
				  opportunity_history_user_id int(11) default NULL,
				  opportunity_history_name varchar(150) NOT NULL,
				  opportunity_history_orig int(11) default NULL,
				  opportunity_history_email varchar(150) default NULL,
				  opportunity_history_desc longtext,
				  opportunity_history_boundaries longtext,
				  opportunity_history_curway longtext,
				  opportunity_history_preyn int(11) default NULL,
				  opportunity_history_precost varchar(50) default NULL,
				  opportunity_history_preout longtext,
				  opportunity_history_comments longtext,
				  opportunity_history_invcost int(11) default NULL,
				  opportunity_history_recucost int(11) default NULL,
				  opportunity_history_financials int(11) default NULL,
				  opportunity_history_strategy int(11) default NULL,
				  opportunity_history_sholders int(11) default NULL,
				  opportunity_history_risks int(11) default NULL,
				  opportunity_history_sizing int(11) default NULL,
				  opportunity_history_horizontality int(11) default NULL,
				  opportunity_history_costbenefit int(11) default NULL,
				  opportunity_history_rationale longtext,
				  opportunity_history_project int(11) default NULL,
				  opportunity_history_ba text,
				  opportunity_history_status int(11) default NULL,
				  opportunity_history_pm int(11) default NULL,
				  opportunity_history_created datetime default NULL,
				  opportunity_history_lastupd timestamp NULL default CURRENT_TIMESTAMP,
				  opportunity_history_owner int(11) default "0",
				  opportunity_history_sponsor int(11) default "0",
				  opportunity_history_background longtext,
				  opportunity_history_priority tinyint(4) default "0",
				  opportunity_history_must tinyint(4) default "0",
				  opportunity_history_must_rationale text,
				  opportunity_history_opportunity int(11) default NULL,
				  opportunity_history_proposal text,
				  PRIMARY KEY  (opportunity_history_id)
			) ENGINE=MyISAM;');
			
		$q -> exec();
		}
		
		//inserting some extra sysvals
		//$sql = 'INSERT INTO sysvals ( sysval_key_id, sysval_title, sysval_value ) ' .
		$sql = 'REPLACE INTO '.dPgetConfig('dbprefix', '').'sysvals ( sysval_key_id, sysval_title, sysval_value ) ' .
			   'VALUES ( ' .
			  // ' "1","OpportunitiesPriorities","1|- \n2|Must \n3|High \n4|Medium \n5|Low \n6|Option" ' .
			  // ' ) , ( ' .
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
	
		return null;
	}

}

?>