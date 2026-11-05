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

$holiday_id = (int)defVal( @$_GET["holiday_id"], 0);
$holiday_white = defVal( @$_GET["white"], -1);

// Create date objects
$log_start_date         = dPgetParam( $_POST, "log_start_date", 0 );
$log_end_date           = dPgetParam( $_POST, "log_end_date", 0 );
$start_date = intval( $log_start_date ) ? new CDate( $log_start_date ) : new CDate();
$end_date   = intval( $log_end_date )   ? new CDate( $log_end_date ) : new CDate();
$holiday_description = dPgetParam( $_POST, "holiday_description", '');
$holiday_annual = dPgetParam( $_POST, "holiday_annual", 0);
// The calendar the day belongs to; 0: every calendar (and the default holidays).
$calendars = holidayCalendars();
$holiday_calendar = (int)dPgetParam( $_POST, "holiday_calendar", 0);
if (!isset($calendars[$holiday_calendar])) {
        $holiday_calendar = 0;
}
        
// Adding, updating and deleting change data: POST with the CSRF token and edit
// permission only.
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dPgetCleanParam($_POST, 'action', '') : '';
$q = new DBQuery();
if($action) {
        $AppUI->verifyCsrfToken();
        if (!getPermission('holiday', 'edit')) {
                $AppUI->redirect('m=public&a=access_denied');
        }
        if( $action == "add" ) {
        		$q -> addTable('holiday');
        		$q -> addInsert('holiday_description',$holiday_description);
        		$q -> addInsert('holiday_start_date',$start_date->format(FMT_DATETIME_MYSQL));
        		$q -> addInsert('holiday_end_date',$end_date->format(FMT_DATETIME_MYSQL));
        		$q -> addInsert('holiday_white',$holiday_white);
        		$q -> addInsert('holiday_annual',$holiday_annual);
        		if (holidayHasCalendars()) {
        			$q -> addInsert('holiday_calendar', $holiday_calendar);
        		}
                $okMsg = "Holiday registered";
        } else if ( $action == "update" ) {
        		$q -> addTable('holiday');
        		$q -> addUpdate('holiday_description', $holiday_description);
        		$q -> addUpdate('holiday_start_date',$start_date->format(FMT_DATETIME_MYSQL));
        		$q -> addUpdate('holiday_end_date',$end_date->format(FMT_DATETIME_MYSQL));
        		$q -> addUpdate('holiday_annual',$holiday_annual);
        		if (holidayHasCalendars()) {
        			$q -> addUpdate('holiday_calendar', $holiday_calendar);
        		}
        		$q -> addWhere('holiday_id = ' . (int)$holiday_id);              
                $okMsg = "Holiday updated";
        }
        else if ( $action == "del" ) {
        		$q -> setDelete('holiday');
        		$q -> addWhere('holiday_id = ' . (int)$holiday_id);		
                $okMsg = "Holiday removed";
	}
        if(!$q -> exec()) {
                $AppUI->setMsg( db_error() );
        } else {
                $AppUI->setMsg( $okMsg );
        }
	$AppUI->redirect();
}

// pull the holiday from the database
$q ->clear();
$q -> addTable('holiday');
$q -> addQuery('*');
$q -> addWhere('holiday_id = ' . (int)$holiday_id);
$holiday = $q -> loadHash();
if (!$holiday) {
	// A new holiday (or one that no longer exists).
	$holiday = array('holiday_white' => 0, 'holiday_annual' => 0, 'holiday_description' => '',
		'holiday_start_date' => null, 'holiday_end_date' => null,
		'holiday_calendar' => (int)dPgetCleanParam($_GET, 'calendar_id', 0));
}

if($holiday_white == -1)
{
	$holiday_white = $holiday['holiday_white'];
}

?>

<script nonce="<?php echo dPcspNonce(); ?>">
var calendarField = '';
function popCalendar( field ){
        calendarField = field;
        idate = document.AddEdit['log_' + field].value;
        window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scollbars=false' );
}
function setCalendar( idate, fdate ) {
        fld_date = document.AddEdit['log_' + calendarField];
        fld_fdate = document.AddEdit[calendarField];
        fld_date.value = idate;
        fld_fdate.value = fdate;
}
function delIt() {
	document.AddEdit.action.value = "del";
	document.AddEdit.submit();
}

</script>


<form name="AddEdit" method="post">				
<table width="100%" border="0" cellpadding="0" cellspacing="1">
<input name="action" type="hidden" value="<?php echo $holiday_id ? "update" : "add"  ?>">
<tr>
	<td><img src="./images/icons/tasks.gif" alt="" border="0"></td>
	<td align='left' nowrap='nowrap' width='100%'><h1>
	<?php 
		if($holiday_white)
		{
			echo $AppUI->_( $holiday_id ? 'Edit holiday' : 'New holiday' );
		}
		else
		{
			echo $AppUI->_( $holiday_id ? 'Edit workday' : 'New workday' );
		}
	?>
	</h1></td>
</tr>
</table>

<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" align="right">
		<a href="#"<?php echo dPaction('delIt'); ?>><img align="absmiddle" src="./images/icons/stock_delete-16.png" width="16" height="16" alt="" border="0"><?php echo $AppUI->_( $holiday_white ? 'Delete holiday' : 'Delete workday' );?></a>
	</td>
</tr>
</table>

<table border="1" cellpadding="4" cellspacing="0" width="98%" class="std">
<tr><td align="CENTER">

        <?php echo $AppUI->_( 'Start date' );?>
        <input type="hidden" name="log_start_date" value="<?php echo $start_date->format( FMT_TIMESTAMP_DATE );?>" />
        <input type="text" name="start_date" value="<?php echo $start_date->format( $AppUI->getPref('SHDATEFORMAT') );?>" class="text" disabled="disabled" />
        <a href="#"<?php echo dPaction('popCalendar', array('start_date')); ?>>
        <img src="./images/calendar.gif" width="24" height="12" title="<?php echo $AppUI->_('Calendar');?>" border="0" />
        </a>

        <?php echo $AppUI->_( 'End date' );?>
        <input type="hidden" name="log_end_date" value="<?php echo $end_date->format( FMT_TIMESTAMP_DATE );?>" />
        <input type="text" name="end_date" value="<?php echo $end_date->format( $AppUI->getPref('SHDATEFORMAT') );?>" class="text" disabled="disabled" size="20" />
        <a href="#"<?php echo dPaction('popCalendar', array('end_date')); ?>>
        <img src="./images/calendar.gif" width="24" height="12" title="<?php echo $AppUI->_('Calendar');?>" border="0" />
        </a>

	<td>
        <?php echo $AppUI->_( 'Annual' );?>
	<input type="checkbox" value="1" name="holiday_annual" <?php if($holiday["holiday_annual"]){?>checked="checked"<?php }?> />
	</td>

<?php if ($calendars) {
	$calendar_names = array(0 => $AppUI->_('All calendars', UI_OUTPUT_RAW));
	foreach ($calendars as $id => $calendar) {
		$calendar_names[$id] = $calendar['calendar_name'];
	}
?>
	<td nowrap="nowrap">
        <?php echo $AppUI->_( 'Calendar' );?>
        <?php echo arraySelect($calendar_names, 'holiday_calendar', 'size="1" class="text"', isset($holiday['holiday_calendar']) ? (int)$holiday['holiday_calendar'] : 0); ?>
	</td>
<?php } ?>

	<td>
        <?php echo $AppUI->_( 'Description' );?>
        <input type="text" class="text" SIZE="100%" name="holiday_description" value="<?php echo dPhtml($holiday["holiday_description"]);?>">
	</td>
</td></tr>

<table border="0" cellspacing="0" cellpadding="3" width="98%">
<tr>
	<td height="40" width="30%">&nbsp;</td>
	<td  height="40" width="35%" align="right">
		<table>
		<tr>
			<td>
				<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel'); ?>" <?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW), 'href' => '?' . $AppUI->getPlace())); ?>>
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
