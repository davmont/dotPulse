<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* journal $Id: index.php,v 1.1 2004/03/30 23:21:40 jcgonz Exp $ */
##
## journal module - a quick hack of the history module by HGS 3/16/2004

## (c) Copyright
## J. Christopher Pereira (kripper@imatronix.cl)
## IMATRONIX
## 

$AppUI->savePlace();
$module = dPgetParam($_GET, "m", 0);

// The project filter: the project tab's project, or the one picked on the journal page (POST).
$project_id = ($module <> "journal") ? intval(dPgetParam($_GET, "project_id", 0))
	: intval(dPgetParam($_POST, "project_id", 0));

//get stuff from db
$q = new DBQuery();
$q->addTable('journal', 'j');
$q->addQuery('*');
$q->addJoin('users', 'u', 'j.journal_user=u.user_id');
$q->addJoin('projects', 'p', 'j.journal_project=p.project_id');
$q->addOrder('journal_date DESC');
// check for project filter
if ($project_id) {
	$q->addWhere('p.project_id = ' . (int)$project_id);
}

$prc = $q->exec();

$journal = array();

?>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
	<form action=./?m=journal method="post" name="pickCompany">
		<tr valign="top">
			<td width="32"><img src="./images/icons/notepad.gif" alt="Tasks" border="0" height="24" width="24"></td>
			<td nowrap>
				<h1><?php echo $AppUI->_('Journal Entries'); ?> :</h1>
			</td>

			<?php if ($module == "journal") {
				echo "<td align=right width=100%>", $AppUI->_('Project'), ":</td>";
				echo "<td align=right>";
				// pull the projects list
				$q = new DBQuery();
				$q->addTable('projects');
				$q->addQuery('project_id,project_name');
				$q->addOrder('project_name');
				$projects = arrayMerge(array(0 => '(' . $AppUI->_('All') . ')'), $q->loadHashList());
				echo arraySelect($projects, 'project_id', 'class=text' . dPaction(null, array(), null, array('submit' => 'pickCompany')), $project_id);
				echo "</form></td>";
			}
			?>

			<td align="right"><input class="button" type="button" value="<?php echo $AppUI->_('Add note'); ?>"
					<?php echo dPaction(null, array(), null, array('href' => '?m=journal&a=addedit&project_id=' . (int)$project_id)); ?>></td>
</table>

<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
	<tr>
		<th width="10">&nbsp;</th>
		<th><?php echo $AppUI->_('Date'); ?></th>
		<th><?php echo $AppUI->_('Project'); ?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_('Description'); ?></th>
		<th nowrap="nowrap"><?php echo $AppUI->_('User'); ?>&nbsp;&nbsp;</th>
	</tr>
	<?php
	if ($prc) {
		while ($row = db_fetch_assoc($prc)) {
			?>
			<tr>
				<td><a href='<?php echo "?m=journal&a=addedit&journal_id=" . $row["journal_id"] ?>'><img
							src="./images/icons/pencil.gif" alt="<?php echo $AppUI->_('Edit journal') ?>" border="0"
							width="12" height="12"></a></td>
				<td><?php echo $row["journal_date"] ?></td>
				<td><?php echo dPhtml($row["project_name"]) ?></td>
				<td><?php echo dPhtml($row["journal_description"]) ?></td>
				<td><?php echo dPhtml($row["user_username"]) ?></td>
			</tr>
			<?php
		}
	}
	?>
</table>