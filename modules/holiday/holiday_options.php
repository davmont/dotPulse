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

// Saving the settings changes data: POST with the CSRF token and edit permission only.
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dPgetCleanParam($_POST, 'action', '') : '';
if($action=="update") {
	$AppUI->verifyCsrfToken();
	if (!getPermission('holiday', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	$holiday_manual = (int)dPgetCleanParam($_POST, 'holiday_manual', 0);
	$holiday_auto = (int)dPgetCleanParam($_POST, 'holiday_auto', 0);
	$holiday_country = dPgetCleanParam($_POST, 'holiday_country', '');
	if (!array_key_exists($holiday_country, CHolidayCalendar::countries())) {
		$holiday_country = '';
	}

	$q = new DBQuery();
	$q -> addTable('holiday_settings');
	$q -> addUpdate('holiday_manual', $holiday_manual);
	$q -> addUpdate('holiday_auto', $holiday_auto);
	if (array_key_exists('holiday_country', holidaySettings())) {
		$q -> addUpdate('holiday_country', $holiday_country);
	}
	$q -> exec();

    $AppUI->setMsg( "Settings updated" );
	$AppUI->redirect();
}

$settings = holidaySettings();
$holiday_manual = intval(isset($settings['holiday_manual']) ? $settings['holiday_manual'] : 0);
$holiday_auto = intval(isset($settings['holiday_auto']) ? $settings['holiday_auto'] : 0);
$holiday_country = isset($settings['holiday_country']) ? $settings['holiday_country'] : '';
$countries = array('' => $AppUI->_('None', UI_OUTPUT_RAW));
foreach (CHolidayCalendar::countries() as $code => $name) {
	$countries[$code] = $AppUI->_($name, UI_OUTPUT_RAW);
}
if (!array_key_exists('holiday_country', $settings)) {
	echo '<p class="error">' . $AppUI->_('Upgrade the Holiday module in System Admin, Modules, to choose a country.') . '</p>';
} else {
	echo '<p>' . $AppUI->_('The default country gives the national holidays of the people without a calendar, and of the calendars without a country.') . '</p>';
}

?>

<form name="AddEdit" method="post">				
<input name="action" type="hidden" value="update">

<table border="1" cellpadding="4" cellspacing="0" width="100%" class="std">
<tr>
        <td><?php
                echo $AppUI->_( 'Enable manual holidays' );
                echo "<input type='checkbox' value='1' name='holiday_manual' ";
                echo $holiday_manual ? "checked='checked' />" : "/>";
        ?></td>
        <td><?php
                echo $AppUI->_( 'Enable automatic holidays' );
                echo "<input type='checkbox' value='1' name='holiday_auto' ";
                echo $holiday_auto ? "checked='checked' />" : "/>";
        ?></td>
        <td><?php
                echo $AppUI->_( 'Default country' ) . ' ';
                echo arraySelect( $countries,"holiday_country",'size="1" class="text"',$holiday_country);
        ?></td>
</tr>

<table border="0" cellspacing="0" cellpadding="3" width="100%">
<tr>
	<td height="40" width="30%">&nbsp;</td>
	<td  height="40" width="35%" align="right">
		<table>
		<tr><td>
			<input class="button" type="button" name="btnFuseAction" value="<?php echo $AppUI->_('save'); ?>"<?php echo dPaction(null, array(), null, array('submit' => '')); ?>>
		</td></tr>
		</table>
	</td>
</tr>
</table>	
</table>
</form>
<?php
// The public holidays the default country gives, this year and next.
if ($holiday_country !== '') {
	$this_year = (int)date('Y');
	foreach (array($this_year, $this_year + 1) as $year) {
		echo '<h3>' . $AppUI->_('Public holidays') . ' ' . $year . '</h3>';
		echo '<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">';
		foreach (CHolidayCalendar::forYear($holiday_country, $year) as $day => $name) {
			$d = new CDate($day);
			// Translate "(observed)" and "(substitute day)" on their own.
			if (preg_match('/^(.*) \((observed|substitute day)\)$/', $name, $parts)) {
				$name = $AppUI->_($parts[1], UI_OUTPUT_RAW) . ' (' . $AppUI->_($parts[2], UI_OUTPUT_RAW) . ')';
			} else {
				$name = $AppUI->_($name, UI_OUTPUT_RAW);
			}
			echo '<tr><td width="150" nowrap="nowrap">' . dPhtml($d->format($AppUI->getPref('SHDATEFORMAT')))
				. '</td><td>' . dPhtml($name) . '</td></tr>';
		}
		echo '</table>';
	}
	echo '<p>' . $AppUI->_('Only national holidays are included: add regional and local ones in a calendar (Calendars tab), or to the whitelist for everybody.') . '</p>';
}
?>
