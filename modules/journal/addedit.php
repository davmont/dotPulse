<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* $Id: addedit.php,v 1.1 2004/03/30 23:21:40 jcgonz Exp $ */
##
## journal module - a quick hack of the history module by HGS 3/16/2004

## (c) Copyright
## J. Christopher Pereira (kripper@imatronix.cl)
## IMATRONIX
##

$journal_id = (int)defVal( @$_GET["journal_id"], 0);

$project_id = intval( dPgetParam( $_GET, "project_id", 0 ) );


// check permissions
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}


// Adding, updating and deleting change data: POST with the CSRF token only
// (edit permission is checked above).
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dPgetCleanParam($_POST, 'action', '') : '';
if($action) {
	$AppUI->verifyCsrfToken();
	$journal_description = $_POST["journal_description"];
	$journal_project = $_POST["journal_project"];
	$userid = $AppUI->user_id;
	$q = new DBQuery();	
	if( $action == "add" ) {
		$q -> addTable('journal'); // this is not out of the block because we have the setDelete and may cause problems
		$q -> addInsert('journal_date',date('Y-m-d H:i:s'));
		$q -> addInsert('journal_description',$journal_description);
		$q -> addInsert('journal_user',$userid);
		$q -> addInsert('journal_project',$journal_project);		
		$okMsg = "journal added";
	} else if ( $action == "update" ) {
		$q -> addTable('journal'); // this is not out of the block because we have the setDelete and may cause problems
		$q -> addUpdate('journal_description', $journal_description);
		$q -> addUpdate('journal_project',$journal_project);
		$q -> addWhere('journal_id =' . (int)$journal_id);		
		$okMsg = "journal updated";
	} else if ( $action == "del" ) {
		$q -> setDelete('journal');
		$q -> addWhere('journal_id =' . (int)$journal_id);		
		$okMsg = "journal deleted";				
	}
	if(!$q -> exec()) {
		$AppUI->setMsg( db_error() );
	} else {	
		$AppUI->setMsg( $okMsg );
	}
	$AppUI->redirect();
}

// pull the journal
$q = new DBQuery();
$q -> addTable('journal');
$q -> addQuery('*');
$q -> addWhere("journal_id = $journal_id");
$journal = $q -> loadHash();
if (!$journal) {
	// A new entry (or one that no longer exists).
	$journal = array('journal_project' => 0, 'journal_description' => '');
}


if ($journal["journal_project"]){
    $project_id=$journal["journal_project"];
}
// TODO : remove this header and add use CTitleBlock
?>

<form name="AddEdit" method="post">				
<table width="100%" border="0" cellpadding="0" cellspacing="1">
<input name="action" type="hidden" value="<?php echo $journal_id ? "update" : "add"  ?>">
<tr>
	<td><img src="./images/icons/notepad.gif" alt="" border="0"></td>
	<td align="left" nowrap="nowrap" width="100%"><h1><?php echo $AppUI->_( $journal_id ? 'Edit Note' : 'New Note' );?></h1></td>
</tr>
</table>

<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" align="right">
		<a href="#"<?php echo dPaction('delIt'); ?>><img align="absmiddle" src="./images/icons/trash.gif" width="16" height="16" alt="" border="0"><?php echo $AppUI->_('delete journal');?></a>
	</td>
</tr>
</table>

<table border="1" cellpadding="4" cellspacing="0" width="50%" class="std">
	
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
	function delIt() {
		AddEdit.action.value = "del";
		AddEdit.submit();
	}	
</script>
	
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Project' );?>:</td>
	<td width="60%">
<?php
// pull the projects list
$q = new DBQuery();
$q -> addTable('projects');
$q -> addQuery('project_id,project_name');
$q -> addOrder('project_name');
$projects = arrayMerge( array( 0 => '('.$AppUI->_('any').')' ), $q -> loadHashList() );
echo arraySelect( $projects, 'journal_project', 'class="text"', $project_id );

?>
	</td>
</tr>
	
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_( 'Description' );?>:</td>
	<td width="60%">
		<textarea name="journal_description" class="textarea" cols="60" rows="5" wrap="virtual"><?php echo dPhtml($journal["journal_description"]);?></textarea>
	</td>
</tr>	
		
<table border="0" cellspacing="0" cellpadding="3" width="50%">
<tr>
	<td height="40" width="30%">&nbsp;</td>
	<td  height="40" width="35%" align="right">
		<table>
		<tr>
			<td>
				<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel'); ?>"<?php echo dPaction(null, array(), null, array('confirm' => 'Are you sure you want to cancel.', 'href' => '?' . $AppUI->getPlace())); ?>>
			</td>
			<td>
				<input class="button" type="button" name="btnFuseAction" value="<?php echo $AppUI->_('save'); ?>"<?php echo dPaction(null, array(), null, array('submit' => '')); ?>>
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>	
	
</table>
</form>		
</body>
</html>
