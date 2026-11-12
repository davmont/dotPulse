<?php /* $Id: index.php 6200 2013-01-15 06:24:08Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

if (!($canAccess)) {
	$AppUI->redirect('m=public&a=access_denied');
}

$perms =& $AppUI->acl();
$q = new DBQuery;
$search_string = dPgetCleanParam($_GET, 'search_string', null);

// To configure an aditional filter to use in the search string
$additional_filter = '';
// retrieve any state parameters
if ($search_string) {
	$AppUI->setState('ContIdxWhere', $search_string);
	$get_search = $q->quote_sanitised('%' . $search_string . '%');
	$additional_filter = ("contact_first_name LIKE " . $get_search
		. " OR contact_last_name LIKE " . $get_search
		. " OR company_name LIKE " . $get_search
		. " OR contact_notes LIKE " . $get_search
		. " OR contact_email LIKE " . $get_search);
} else if (isset($_GET['where'])) {
	$AppUI->setState('ContIdxWhere', $_GET['where']);
}

$where = $q->quote_sanitised($AppUI->getState('ContIdxWhere') ? ($AppUI->getState('ContIdxWhere') . '%') : '%');

// Pull First Letters
$let = ":";
$search_map = array('contact_order_by', 'contact_first_name', 'contact_last_name');
foreach ($search_map as $search_name) {
	$q->addTable('contacts', 'c');
	$q->leftJoin('users', 'u', 'u.user_contact=c.contact_id');
	$q->addQuery('DISTINCT UPPER(SUBSTRING(' . $search_name . ',1,1)) as L, user_id');
	$q->addWhere('contact_private = 0 OR (contact_private = 1 AND contact_owner = '
		. $AppUI->user_id . ') OR contact_owner IS NULL OR contact_owner = 0');
	$arr = $q->loadList();
	foreach ($arr as $L) {
		if (!($L['user_id']) || $perms->checkLogin($L['user_id'])) {
			$let .= $L['L'];
		}
	}
}
$q->clear();

// optional fields shown in the list (could be modified to allow breif and verbose, etc)
$showfields = array(
	'contact_company' => 'contact_company',
	'company_name' => 'company_name',
	'contact_phone' => 'contact_phone',
	'contact_email' => 'contact_email'
);

require_once $AppUI->getModuleClass('companies');
$company = new CCompany;
$allowedCompanies = $company->getAllowedSQL($AppUI->user_id);

// assemble the sql statement
$q->addTable('contacts', 'a');
$q->leftJoin('companies', 'b', 'a.contact_company = b.company_id');
$q->leftJoin('users', 'u', 'u.user_contact=a.contact_id');
$q->addQuery('contact_id, contact_order_by');
$q->addQuery('contact_first_name, contact_last_name, contact_phone, contact_owner');
$q->addQuery($showfields);
$q->addQuery('user_id');
$filters = array();
foreach ($search_map as $search_name) {
	$filters[] = $search_name . " LIKE $where";
}
if ($additional_filter) {
	$filters[] = $additional_filter;
}
$where_filter = implode(' OR ', $filters);
$q->addWhere('(' . $where_filter . ')');
$q->addWhere('(contact_private = 0 OR (contact_private = 1 AND contact_owner = ' . $AppUI->user_id
	. ') OR contact_owner IS NULL OR contact_owner = 0)');
if (count($allowedCompanies)) {
	$comp_where = implode(' AND ', $allowedCompanies);
	$q->addWhere('((' . $comp_where . ') OR contact_company = 0)');
}
$q->addOrder('contact_order_by');

$sql = $q->prepare();
$q->clear();

$rn = 0;
$disp_arr = array();
if (!($res = db_exec($sql))) {
} else {
	while ($row = db_fetch_assoc($res)) {
		if (!($row['user_id']) || $perms->checkLogin($row['user_id'])) {
			$disp_arr[] = $row;
			$rn++;
		}
	}
}

$project_counts = array();
if (count($disp_arr) > 0) {
	// Instead of relying on a potentially missing mapping table, we
	// natively count occurrences from the projects table's CSV column in PHP.
	$q = new DBQuery;
	$q->addTable('projects');
	$q->addQuery('project_contacts');
	$q->addWhere("project_contacts IS NOT NULL AND project_contacts != ''");
	$res = $q->exec();

	if ($res) {
		while ($row = db_fetch_assoc($res)) {
			$contacts = explode(',', $row['project_contacts']);
			foreach ($contacts as $cid) {
				$cid = (int)trim($cid);
				if ($cid > 0) {
					if (!isset($project_counts[$cid])) {
						$project_counts[$cid] = 0;
					}
					$project_counts[$cid]++;
				}
			}
		}
	}
	$q->clear();
}


/**
 * Contact search form
 */
$default_search_string = dPformSafe($AppUI->getState('ContIdxWhere'), true);

$a2z = "\n" . '<table cellpadding="2" cellspacing="1" border="0">';
$a2z .= "\n<tr>";
$a2z .= '<td width="100%" align="right">' . $AppUI->_('Show') . ': </td>';
$a2z .= '<td><a href="?m=contacts&amp;where=0">' . $AppUI->_('All') . '</a></td>';
for ($c = 65; $c < 91; $c++) {
	$cu = chr($c);
	$cell = ((mb_strpos($let, "$cu") > 0)
		? ('<a href="?m=contacts&amp;where=' . $cu . '">' . $cu . '</a>')
		: ('<font color="#999999">' . $cu . '</font>'));
	$a2z .= "\n\t<td>$cell</td>";
}
$a2z .= ("\n</tr>\n<tr>" . '<td colspan="28">'
	. '<form action="./index.php" method="get">' . $AppUI->_('Search for')
	. '<input type="text" name="search_string" value="' . $default_search_string . '" />'
	. '<input type="hidden" name="m" value="contacts" /><input type="submit" value=">" />'
	. '<a href="./index.php?m=contacts&amp;search_string=">' . $AppUI->_('Reset search')
	. '</a></form></td></tr>'
	. "\n</table>\n");

// setup the title block

// what purpose is the next line for? Commented out by gregorerhardt, Bug #892912
// $contact_id = $carr[$z][$x]["contact_id"];

$titleBlock = new CTitleBlock('Contacts', 'monkeychat-48.png', $m, "$m.$a");
$titleBlock->addCell($a2z);
if ($canAuthor) {
	$titleBlock->addCell(
		('<input type="submit" class="button" value="' . $AppUI->_('new contact')
			. '">'),
		'',
		'<form action="?m=contacts&amp;a=addedit" method="post">',
		'</form>'
	);
	$titleBlock->addCrumbRight('<a href="?m=contacts&amp;a=csvexport&amp;suppressHeaders=true">'
		. $AppUI->_('CSV Download') . '</a> | '
		. '<a href="?m=contacts&amp;a=vcardimport&amp;dialog=0">'
		. $AppUI->_('Import vCard') . '</a>');
}
$titleBlock->show();

?>
<script language="javascript" type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
	// Callback function for the generic selector
	function goProject(key, val) {
		var f = document.modProjects;
		if (val != '') {
			f.project_id.value = key;
			f.submit();
		}
	}
</script>
<form action="./index.php" method='get' name="modProjects">
	<input type='hidden' name='m' value='projects' />
	<input type='hidden' name='a' value='view' />
	<input type='hidden' name='project_id' />
</form>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl" summary="Contacts">
	<tr>
		<th><?php echo $AppUI->_('Name'); ?></th>
		<th><?php echo $AppUI->_('Company'); ?></th>
		<th><?php echo $AppUI->_('Email'); ?></th>
		<th><?php echo $AppUI->_('Phone'); ?></th>
		<th><?php echo $AppUI->_('Projects'); ?></th>
		<th><?php echo $AppUI->_('Action'); ?></th>
	</tr>
	<?php
	foreach ($disp_arr as $row) {
		$contactid = $row['contact_id'];
		$contact_name = $AppUI->___(($row['contact_order_by'])
			? $row['contact_order_by']
			: ($row['contact_first_name'] . ' ' . $row['contact_last_name']));
		?>
		<tr>
			<td>
				<a
					href="?m=contacts&amp;a=view&amp;contact_id=<?php echo $contactid; ?>"><strong><?php echo $contact_name; ?></strong></a>
			</td>
			<td>
				<?php echo dPhtml((!is_numeric($row['contact_company']) ? $row['contact_company'] : $row['company_name'])); ?>
			</td>
			<td>
				<?php if ($row['contact_email']) { ?>
					<a href="mailto:<?php echo dPhtml($row['contact_email']); ?>"
						class="mailto"><?php echo dPhtml($row['contact_email']); ?></a>
				<?php } ?>
			</td>
			<td>
				<?php echo dPhtml($row['contact_phone']); ?>
			</td>
			<td>
				<?php
				$projects_contact = isset($project_counts[$contactid]) ? $project_counts[$contactid] : 0;
				if ($projects_contact > 0) {
					echo ('<a href="#"' . dPaction('window.open', array(
						'?m=public&a=selector&dialog=1&callback=goProject&table=projects&user_id=' . (int)$contactid,
						'selector', 'left=50,top=50,height=250,width=400,resizable'))
						. '>' . $AppUI->_('Projects') . '</a>');
				}
				?>
			</td>
			<td>
				<a title="<?php echo $AppUI->___($AppUI->_('Export vCard for') . ' ' . $contact_name); ?>"
					href="?m=contacts&amp;a=vcardexport&amp;suppressHeaders=true&amp;contact_id=<?php echo $contactid; ?>">(vCard)</a>&nbsp;
				<?php if (getPermission('contacts', 'edit', $contactid)) { ?>
				<a title="<?php echo $AppUI->_('Edit'); ?>"
					href="?m=contacts&amp;a=addedit&amp;contact_id=<?php echo $contactid; ?>"><?php echo $AppUI->_('Edit'); ?></a>
				<?php } ?>
			</td>
		</tr>
	<?php } ?>
</table>