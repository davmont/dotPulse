<?php /* HELPDESK $Id: selector.php,v 1.14 2011/08/02 06:22:55 hatax Exp $ */

if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('departments');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('tasks');
require_once $AppUI->getModuleClass('forums');

/**
 * Limits $q to the records of $obj's module the user may view, the way the module's own
 * lists do. $column is the column holding the record id in the query.
 */
function selAllowed(&$q, $obj, $column) {
	global $AppUI;
	foreach ($obj->getAllowedSQL($AppUI->user_id, $column) as $where) {
		$q->addWhere($where);
	}
}

$debug = false;
// callback is written into a <script> block: accept only a JavaScript function name.
$callback = preg_replace('/[^A-Za-z0-9_.]/', '', dPgetParam( $_GET, 'callback', '' ));
$table = dPvalidateOrder( dPgetParam( $_GET, 'table', '' ), array('companies', 'departments', 'forums', 'projects', 'tasks', 'users', 'contacts'), '' );
$comp=dPgetIntParam($_GET, 'comp', 0);

$ok = ($callback !== '' && $table !== '');

$title = "Generic Selector";
$q = new DBQuery;

switch ($table) {
case 'companies':
	$title = 'Company';
	$q->addTable('companies');
	$q->addQuery('company_id, company_name');
	$q->addOrder('company_name');
	selAllowed($q, new CCompany(), 'company_id');
	break;
case 'departments':
	$title = 'Department';
	$company_id = dPgetIntParam( $_GET, 'company_id', 0 );
	$q->addTable('departments');
	$q->addTable('companies');
	$q->addWhere('dept_company = company_id');
	selAllowed($q, new CDepartment(), 'dept_id');
	selAllowed($q, new CCompany(), 'company_id');
	$hide_company = dPgetIntParam( $_GET, 'hide_company', 0 );
	if ( $hide_company == 1 ){
		$q->addQuery('dept_id, dept_name');
	}else{
		$q->addQuery("dept_id, CONCAT_WS(': ',company_name,dept_name) AS dept_name");
	}
	if ($company_id) {
		$q->addWhere('dept_company = ' . $company_id);
		$q->addOrder('dept_name');
	} else {
		$q->addOrder('company_name, dept_name');
	}
	break;
case 'forums':
	$title = 'Forum';
	$q->addTable('forums');
	$q->addQuery('forum_id, forum_name');
	$q->addOrder('forum_name');
	selAllowed($q, new CForum(), 'forum_id');
	selAllowed($q, new CProject(), 'forum_project');
	break;
case 'projects':
	$project_company = dPgetIntParam( $_GET, 'project_company', 0 );

	$title = 'Project';
	$q->addTable('projects');
	$q->addQuery('project_id, project_name');
	$q->addOrder('project_name');
	selAllowed($q, new CProject(), 'project_id');
	if ($project_company) {
		$q->addWhere('project_company = ' . $project_company);
	}
	break;
case 'tasks':
	$task_project = dPgetIntParam( $_GET, 'task_project', 0 );

	$title = 'Task';
	$q->addTable('tasks');
	$q->addQuery('task_id, task_name, task_access, task_owner, task_project');
	$q->addOrder('task_name');
	selAllowed($q, new CTask(), 'task_id');
	selAllowed($q, new CProject(), 'task_project');
	if ($task_project) {
		$q->addWhere('task_project = ' . $task_project);
	}
	// Tasks that are not public only when the user may access them (CTask::canAccess()).
	$list = array();
	foreach ($q->loadList() as $row) {
		if ((int)$row['task_access'] !== 0) {
			$task = new CTask();
			$task->task_id = (int)$row['task_id'];
			$task->task_access = (int)$row['task_access'];
			$task->task_owner = (int)$row['task_owner'];
			$task->task_project = (int)$row['task_project'];
			if (!$task->canAccess($AppUI->user_id)) {
				continue;
			}
		}
		$list[$row['task_id']] = $row['task_name'];
	}
	$list = arrayMerge(array(0 => ''), $list);
	break;
case 'users':
	$title = 'User';
	$tempuserlist = getAllowedUsers($comp, 1);
	foreach($tempuserlist as $key=>$value){
		$list[$key]=$value;
	}
	break;
case 'contacts':
	$title = 'Contact';
	$q->addTable('contacts');
	$q->addQuery("contact_id, CONCAT_WS(' ',contact_first_name,contact_last_name)");
	$q->addOrder("CONCAT_WS(' ',contact_first_name,contact_last_name)");
	if (!getPermission('contacts', 'view')) {
		$q->addWhere('1 = 0');
	}
	// As the contacts list: private contacts only for their owner, and companies the user may see.
	$q->addWhere('(contact_private = 0 OR contact_owner = ' . (int)$AppUI->user_id
		. ' OR contact_owner IS NULL OR contact_owner = 0)');
	$company = new CCompany();
	$allowedCompanies = $company->getAllowedSQL($AppUI->user_id, 'contact_company');
	if (count($allowedCompanies)) {
		$q->addWhere('((' . implode(' AND ', $allowedCompanies) . ') OR contact_company = 0)');
	}
	if ($comp) {
		$q->addWhere('contact_company = ' . $comp);
	}
	break;
default:
	$ok = false;
	break;
}

if (!$ok) {
	echo "Incorrect parameters passed\n";
	if ($debug) {
		echo "<br />callback = $callback \n";
		echo "<br />table = ".dPhtml($table)." \n";
		echo "<br />ok = $ok \n";
	}
} else {
	if(!isset($list)){
		$list = arrayMerge( array( 0=>''), $q->loadHashList());
	}
?>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
	// Calls window.opener.<callback>(key, val); the callback may be a dotted path.
	function setClose(key, val){
		var parts = <?php echo dPjs($callback); ?>.split('.');
		var owner = window.opener;
		for (var i = 0; owner && i < parts.length - 1; i++) {
			owner = owner[parts[i]];
		}
		var name = parts[parts.length - 1];
		if (!owner || typeof owner[name] !== 'function') {
			throw new Error('No callback function in the opener window');
		}
		owner[name].call(owner, key, val);
		window.close();
	}
</script>

<table cellspacing="0" cellpadding="3" border="0">
<tr>
	<td>
<?php
	if (count( $list ) > 1) {
		echo $AppUI->_( 'Select' ).' '.$AppUI->_( $title ).':<br />';
		foreach ($list as $key => $val) {
			echo '<a href="#"' . dPaction('setClose', array((string)$key, (string)$val)) . '>' . dPhtml($val) . "</a><br>\n";
		}
?>
	</td>
</tr>
<tr>
	<td align="right">
		<input type="button" class="button" value="<?php echo $AppUI->_( 'cancel' );?>"<?php echo dPaction('window.close'); ?> />
<?php
	} else {
		echo $AppUI->_( "no$table" );
	}
?>
	</td>
</tr>
</table>

<?php } ?>

