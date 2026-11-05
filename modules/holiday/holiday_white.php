<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

##
## holiday module - A dotProject module for keeping track of holidays 
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no) 
##

require_once DP_BASE_DIR . '/modules/holiday/holiday_func.php';
$AppUI->savePlace();
$calendars = holidayCalendars();
?>

<form action=./?m=holiday method="post">
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<th width="10">&nbsp;</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Description');?></th>
	<th width="50" nowrap="nowrap"><?php echo $AppUI->_('Start date');?></th>
	<th width="100" nowrap="nowrap"><?php echo $AppUI->_('End date');?></th>
	<th width="10" nowrap="nowrap"><?php echo $AppUI->_('Annual');?></th>
<?php if ($calendars) { ?>	<th nowrap="nowrap"><?php echo $AppUI->_('Calendar');?></th><?php } ?>
</tr>
<?php
//get stuff from db
$q = new DBQuery();
$q -> addTable('holiday');
$q -> addQuery('*');
$q -> addWhere('holiday_white=1');
$q -> addOrder('holiday_start_date DESC');

$prc = $q -> exec();

while ($row = db_fetch_assoc( $prc )) {
?>
<tr>
	<?php $tmp_start_date=new CDate($row['holiday_start_date']); ?>
	<?php $tmp_end_date=new CDate($row["holiday_end_date"]); ?>
	<td><a href='<?php echo "?m=holiday&a=addedit&holiday_id=" . $row["holiday_id"] ?>'><img src="./images/icons/pencil.gif" title="<?php echo $AppUI->_( 'Edit holiday' ) ?>" border="0" width="12" height="12"></a></td>
	<td><?php echo dPhtml($row["holiday_description"])?></td>
	<td width="100" nowrap="nowrap"><?php echo $tmp_start_date->format($AppUI->getPref('SHDATEFORMAT'))?></td>
	<td width="100" nowrap="nowrap"><?php echo $tmp_end_date->format($AppUI->getPref('SHDATEFORMAT'))?></td>
	<td width="100" nowrap="nowrap"><?php echo $row["holiday_annual"] ? 'Yes' : 'No'; ?></td>
<?php if ($calendars) { ?>	<td nowrap="nowrap"><?php echo isset($calendars[$row['holiday_calendar']]) ? dPhtml($calendars[$row['holiday_calendar']]['calendar_name']) : $AppUI->_('All calendars'); ?></td><?php } ?>
</tr>
<?php
}
?>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
        <tr valign="top">
                <td align="left"><?php echo $AppUI->_('Whitelist items allows you to add holidays manually');?>.</td>
                <td align="right"><input class="button" type="button" value="<?php echo $AppUI->_('Add holiday');?>" <?php echo dPaction(null, array(), null, array('href' => '?m=holiday&a=addedit&white=1')); ?>></td>
        </tr>
</table>

