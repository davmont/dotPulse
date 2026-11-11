<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// this is another example showing how the dPFramework is working
// additionally we will have an easy database connection here

// as we are now within the tab box, we have to state (call) the needed information saved in the variables of the parent function
GLOBAL $AppUI, $canRead, $canEdit, $project_id;

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}
//prepare an html table with a head section
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th nowrap="nowrap">&nbsp;</th>
	<th nowrap="nowrap">&nbsp;</th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Name' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Description' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Result' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
	?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_( 'Task' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
	?></th>

</tr>
<?php
// retrieving some dynamic content using an easy database query
$tab = $AppUI->getState( 'TestingIdxTab' ) !== NULL ? $AppUI->getState( 'TestingIdxTab' ) : "";
$sql = "SELECT unittest_id,unittest_name,unittest_passed,unittest_description,unittest_actualresult,unittest_lasttested ,task_name
				FROM " . dPgetConfig('dbprefix', '') . "unittest
				LEFT OUTER JOIN " . dPgetConfig('dbprefix', '') . "tasks ON unittest_task_id = task_id";	// prepare the sqlQuery command to get all quotes from the einstein table
$sql.="	WHERE 1=1";
if($tab=="0")
	$sql.= " AND unittest_passed IS NULL";
if($tab=="1")
	$sql.= " AND unittest_passed = 0";
if($tab=="2")
	$sql.= " AND unittest_passed = 1";
if($project_id > 0)
	$sql.= " AND unittest_project_id = ".(int)$project_id;
	
// pass the query to the database, please consider always using the (still poor) database abstraction layer
$quotes = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of einstein quotes via an abstract db method

// add/show now gradually the einstein quotes

foreach ($quotes as $row) {		//parse the array of einstein quotes
?>
<tr>
	<td nowrap="nowrap" width="20">
	<?php if ($canEdit) {	// in case of writePermission on the module show an icon providing edit functionality for the given quote item

		// call the edit site with the unique id of the quote item
		echo "\n".'<a href="./index.php?m=testing&a=addedit&unittest_id=' . $row["unittest_id"] . '">';
		
		// use the dPFrameWork to show the image
		// always use this way via the framework instead of hard code for the advantage
		// of central improvement of code in case of bugs etc. and for other reasons
		echo dPshowImage( './images/icons/stock_edit-16.png', '16', '16' );
		echo "\n</a>";
	}
	?>
	</td>
	<td nowrap="nowrap" width="20">
	<?php
	 if ($canEdit) {	// in case of writePermission on the module show an icon providing edit functionality for the given quote item

		// call the edit site with the unique id of the quote item
		echo "\n".'<a href="./index.php?m=testing&a=runtest&unittest_id=' . $row["unittest_id"] . '">';
		
		// use the dPFrameWork to show the image
		// always use this way via the framework instead of hard code for the advantage
		// of central improvement of code in case of bugs etc. and for other reasons
		echo dPshowImage( './images/icons/stock_ok-16.png', '16', '16' );
		echo "\n</a>";
	}
	?>
	</td>
	<td >
	<?php
	echo dPhtml($row["unittest_name"]);		
	?>
	</td>
	<td >
	<?php
	echo dPhtml($row["unittest_description"]);		
	?>
	</td>
	<td >
	<?php
	echo dPhtml($row["unittest_actualresult"]);		
	?>
	</td>
	<td >
	<?php
	echo dPhtml($row["task_name"]);		
	?>
	</td>
</tr>
<?php
}
?>
</table>