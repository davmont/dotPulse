<?php /* PUBLIC $Id: selector.php 6161 2012-06-13 05:36:24Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

/**
 * Limits $q to the records of $obj's module the user may view, the way the module's own
 * lists do (no access at all gives an empty list). $column holds the record id in $q.
 */
function selAllowed(&$q, $obj, $column) {
	global $AppUI;
	foreach ($obj->getAllowedSQL($AppUI->user_id, $column) as $where) {
		$q->addWhere($where);
	}
}

$debug = false;
// callback is written into a <script> block: accept only a JavaScript function name.
$callback = preg_replace('/[^A-Za-z0-9_.]/', '', dPgetCleanParam($_GET, 'callback', ''));
// table selects a class file and a table: accept only the tables handled below.
$table = dPgetCleanParam($_GET, 'table', '');
if (!in_array($table, array('companies', 'departments', 'files', 'file_folders', 'forums',
		'projects', 'tasks', 'users', 'SGD', 'helpdesk_items'), true)) {
	$table = '';
}
$user_id = (int)dPgetParam($_GET, 'user_id', 0);

$ok = $callback & $table;

$title = "Generic Selector";

$modclass = $AppUI->getModuleClass($table);
if ($modclass && file_exists ($modclass)) {
	require_once $modclass;
}

$q = new DBQuery;
$q->addTable($table, 'a');
$query_result = false;

switch ($table) {
	case 'companies':
		$obj = new CCompany;
		$title = 'Company';
		$q->addQuery('company_id, company_name');
		$q->addOrder('company_name');
		selAllowed($q, $obj, 'company_id');
		break;
	case 'departments':
		$title = 'Department';
		$company_id = (int)dPgetParam($_GET, 'company_id', 0);
		//$ok &= $company_id;  // Is it safe to delete this line ??? [kobudo 13 Feb 2003]
		//$where = selPermWhere('companies', 'company_id');
		$obj = new CDepartment;
		require_once $AppUI->getModuleClass('companies');
		selAllowed($q, $obj, 'dept_id');
		selAllowed($q, new CCompany(), 'company_id');
		$q->addWhere("dept_company = company_id ");
		$q->addTable('companies', 'b');
		
		$hide_company = (int)dPgetParam($_GET, 'hide_company', 0);
		$q->addQuery('dept_id');
		if ($hide_company == 1) {
			$q->addQuery("dept_name");
		} else {
			$q->addQuery("CONCAT_WS(': ',company_name,dept_name) AS dept_name");
		}
		
		if ($company_id) {
			$q->addWhere("dept_company = $company_id");
			$q->addOrder("dept_name");
		} else {
			$q->addOrder("company_name, dept_name");
		}
		break;
	case 'files':
		$title = 'File';
		$q->addQuery('file_id,file_name');
		$q->addOrder('file_name');
		require_once $AppUI->getModuleClass('projects');
		selAllowed($q, new CFile(), 'file_id');
		selAllowed($q, new CProject(), 'file_project');
		break;
	case 'file_folders':
		$title = 'File Folder';
		$q->addQuery('file_folder_id,file_folder_name');
		$q->addOrder('file_folder_name');
		require_once $AppUI->getModuleClass('files');
		selAllowed($q, new CFileFolder(), 'file_folder_id');
		break;
	case 'forums':
		$title = 'Forum';
		$q->addQuery('forum_id,forum_name');
		$q->addOrder('forum_name');
		require_once $AppUI->getModuleClass('projects');
		selAllowed($q, new CForum(), 'forum_id');
		selAllowed($q, new CProject(), 'forum_project');
		break;
	case 'projects':
		$project_company = (int)dPgetParam($_GET, 'project_company', 0);
		
		$title = 'Project';
		$obj = new CProject;
		$q->addQuery('a.project_id, project_name');
		$q->addOrder('project_name');
		if ($user_id > 0) {
			$q->addTable('project_contacts', 'b');
			$q->addWhere('b.project_id = a.project_id');
			$q->addWhere("b.contact_id = $user_id");
		}
		selAllowed($q, $obj, 'a.project_id');
		if ($project_company) {
			$q->addWhere("project_company = $project_company");
		}
		break;	
	case "tasks":
		$task_project = (int)dPgetParam($_GET, 'task_project', 0);
		
		$title = 'Task';
		$q->addQuery('task_id, task_name, task_parent, p.project_name');
		$q->addOrder('task_parent, task_parent = task_id desc');
		if ($task_project) {
			$q->addWhere("task_project = $task_project");
		}
		$q->innerJoin('projects', 'p', 'task_project = p.project_id');
		$q->addQuery('task_access, task_owner, task_project');
		require_once $AppUI->getModuleClass('projects');
		selAllowed($q, new CTask(), 'task_id');
		selAllowed($q, new CProject(), 'p.project_id');
		$task_list = array();
		foreach ($q->loadList() as $task) {
			// Tasks that are not public only when the user may access them.
			if ((int)$task['task_access'] !== 0) {
				$check = new CTask();
				$check->task_id = (int)$task['task_id'];
				$check->task_access = (int)$task['task_access'];
				$check->task_owner = (int)$task['task_owner'];
				$check->task_project = (int)$task['task_project'];
				if (!$check->canAccess($AppUI->user_id)) {
					continue;
				}
			}
			$task_list[] = $task;
		}
		$level = 0;
		$query_result = array();
		$last_parent = 0;
		foreach ($task_list as $task) {
			if ($task['task_parent'] != $task['task_id']) {
				if ($last_parent != $task['task_parent']) {
					$last_parent = $task['task_parent'];
					$level++;
				}
			} else {
				$last_parent = 0;
				$level = 0;
			}
			$query_result[$task['task_id']] = ($task['project_name'] . ' - ' 
			                                   . $task['task_name']);
			$html_disp_offset[$task['task_id']] = str_repeat('&nbsp;&nbsp;', $level);
		}
		break;
	case 'users':
		$title = 'User';
		$q->addQuery("user_id,CONCAT_WS(' ',contact_first_name,contact_last_name)");
		$q->addOrder('contact_first_name');
		$q->addTable("contacts", 'b');
		$q->addWhere("user_contact = contact_id");
		break;
	case 'SGD':
		$title = 'Document';
		$q->addQuery('SGD_id, SGD_name');
		$q->addOrder('SGD_name');
		if (!getPermission('mngdocument', 'view')) {
			$q->addWhere('1 = 0');
		}
		break;
	case 'helpdesk_items':
		// Added by HaTaX for helpdesk support
		include DP_BASE_DIR.'/modules/helpdesk/config.php';
		$title = 'HelpDesk Items';
		$q->addQuery("item_id, CONCAT_WS(' - ',CONCAT('#', item_id), item_title) AS name");
		$q->addWhere("item_status != ".(int)$HELPDESK_CONFIG['closed_status_id']);
		// Only the items the helpdesk lists show this user.
		require_once DP_BASE_DIR . '/modules/helpdesk/helpdesk.functions.php';
		$q->addWhere(getPermission('helpdesk', 'view') ? getItemPerms() : '1 = 0');
		$q->addOrder('item_id, name');
		break;
	default:
		$ok = false;
		break;
}

if (!$ok) {
	echo ("Incorrect parameters passed\n");
	if ($debug) {
		echo "<br />callback = $callback \n";
		echo "<br />table = $table \n";
		echo "<br />ok = $ok \n";
	}
} else {
	$list = arrayMerge(array(0 => $AppUI->_('[none]')), 
	                   ((!(empty($query_result))) ? $query_result : $q->loadHashList()));
?>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function setClose(key, val) {
	dPopenerCall(<?php echo dPjs($callback); ?>, [key, val]);
	window.close();
}

window.onresize = window.onload = function setHeight() {

if (document.compatMode && document.compatMode != "BackCompat" 
    && document.documentElement.clientHeight) {
	var wh = document.documentElement.clientHeight;
} else {
	var wh = document.all ? document.body.clientHeight : window.innerHeight;
}

var wh = getInnerHeight(window);
var selector = document.getElementById("selector");
var count = 0;
obj = selector;
while (obj!=null) {
	count += obj.offsetTop;
	obj = obj.offsetParent;
}
selector.style.height = (wh - count - 5) + "px";

}
</script>
<form name="frmSelector">
<b><?php echo $AppUI->_('Select').' '.$AppUI->_($title).':'?></b>
<table width="100%">
<tr>
	<td>
		<div style="white-space:normal; overflow:auto; max-height:calc(100vh - 60px);" id="selector">
		<ul style="padding-left:0px">
		<?php
if (count($list) > 1) {
	foreach ($list as $key => $val) {
		echo ('<li>' . $html_disp_offset[$key] . '<a href="#"'
		      . dPaction('setClose', array((string)$key, (string)$val)) . '>' . $AppUI->___($val) 
		      . "</a></li>\n");
	}
} else {
	echo $AppUI->_("no $table");
}
?>
		</ul>
		</div>
	</td>
	<td valign="bottom">
		<input type="button" class="button" value="<?php 
echo $AppUI->_('cancel');?>"<?php echo dPaction('window.close'); ?> />
	</td>
</tr>
</table>
</form>

<?php } ?>
