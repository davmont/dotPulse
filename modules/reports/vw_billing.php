<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once dPgetConfig( 'root_dir' ).'/modules/ticketsmith/common.inc.php';


// may need so more thought as to which company to pull.  (maybe just search for 'internal' type companies)

// companies
$qc = new DBQuery();
$qc -> addTable('companies');
$qc -> addQuery('company_id, company_name');
$qc -> addOrder('company_name');
$companies = $qc -> loadList();

// projects
$qp = new DBQuery();
$qp -> addTable('projects');
$qp -> addQuery('project_id, project_name, project_company');
$qp -> addOrder('project_name');

$projects = $qp -> loadList();


// tasks
$qt = new DBQuery();
$qt -> addTable('tasks'); 
$qt -> addQuery('task_id, task_name, task_project');
$qt -> addOrder('task_name');

$tasks = $qt -> loadList();

// departments
$qd = new DBQuery();
$qd -> addTable('departments');
$qd -> addQuery('dept_id, dept_name, dept_company');
$qd -> addOrder('dept_name');
$qd -> addWhere("dept_company = " . (int)$AppUI->user_company);
$departments = $qd -> loadList();

// employees
$qu = new DBQuery();
$qu -> addTable('users','u');
$qu -> addJoin('contacts','c','u.user_contact=c.contact_id');
$qu -> addQuery('u.user_id, u.user_username, c.contact_first_name, c.contact_last_name, u.user_department');
$qu -> addWhere("u.user_company = " . (int)$AppUI->user_company);
$qu -> addOrder('contact_last_name, contact_first_name');

$employees = $qu -> loadList();

//$tasks = array( '0' => "[0, 0, 'All']");
$tasks = array();
//$projects = array( '0' => "[0, 0, 'All']");
$projects = array();
$companies = array( '0' => "All");
$departments = array( '0' => "All");
//$employees = array( '0' => "[0, 0, 'All']" );
$employees = array();

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

//$crumbs = array();
//$crumbs["?m=timetrack&timesheet_id=$timesheet_id"] = "timesheets list";
//$crumbs["?m=timetrack&a=view&timesheet_id=$timesheet_id"] = "view this timesheet";

##
## Set up JavaScript arrays
##
$ua = $_SERVER['HTTP_USER_AGENT'];
$isMoz = strpos( $ua, 'Gecko' ) !== false;

$s = "\nvar tasks = new Array(".implode( ",\n", $tasks ).")";
$s .= "\nvar projects = new Array(".implode( ",\n", $projects ).")";
$s .= "\nvar employees = new Array(".implode( ",\n", $employees ).")";

echo '<script language="javascript" nonce="' . dPcspNonce() . '">' . $s . '</script>';


$qu -> clear();
$qu -> addTable('users','u');
$qu -> addJoin('contacts','c','u.user_contact=c.contact_id');
$qu -> addQuery('u.user_id, u.user_username, c.contact_first_name, c.contact_last_name');
$qu -> addWhere("user_company = " . (int)$AppUI->user_company);
$qu -> addOrder('contact_last_name, contact_first_name');		 

$result = $qu -> loadList();

?>

<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
function setColor(color) {
	var f = document.editFrm;
	if (color) {
		f.project_color_identifier.value = color;
	}
	test.style.background = f.project_color_identifier.value;
}

function setShort() {
	var f = document.editFrm;
	var x = 10;
	if (f.project_name.value.length < 11) {
		x = f.project_name.value.length;
	}
	if (f.project_short_name.value.length == 0) {
		f.project_short_name.value = f.project_name.value.substr(0,x);
	}
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// List Handling Functions
function emptyList( list ) {
<?php if ($isMoz) { ?>
	list.options.length = 0;
<?php } else { ?>
	while( list.options.length > 0 ) {
		//list.options.remove(0); CHANGED -- doesn't work with Konqueror
		list.options[0] = null;
	}
<?php } ?>
}

function addToList( list, text, value ) {
	//alert( list+','+text+','+value );
<?php if ($isMoz) { ?>
	list.options[list.options.length] = new Option(text, value);
<?php } else { ?>
	var newOption = document.createElement("OPTION");
	newOption.text = text;
	newOption.value = value;
	list.add( newOption, list.options.length );
<?php } ?>
}

function changeList( listName, source, target ) {
//	alert(listName+'  -,-  '+source+'  -,-  '+target);return;
	var f = document.reportForm;
	var list = f[listName];

// clear the options

	emptyList( list );
	
// refill the list based on the target
// add a blank first to force a change
	addToList( list, 'All', '0' );

	for (var i=0, n = source.length; i < n; i++) {
		if( source[i][0] == target ) {
			addToList( list, source[i][2], source[i][1] );
		}
	}
}

// select an item in the list by target value
function selectList( listName, target ) {
	var f = document.reportForm;
	var list = f[listName];

	for (var i=0, n = list.options.length; i < n; i++) {
//alert(listName+','+target+','+list.options[i].value);
		if( list.options[i].value == target ) {
			list.options.selectedIndex = i;
			return;
		}
	}
}
//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function checkBillingFields() {
	
	var f = document.reportForm;
		
	if ( f.start_date_billing.value == '' ) {
		alert ('Please enter a start date');
		return false;
	}
	if ( f.end_date_billing.value == '' ) {
		alert ('Please enter an end date');
		return false;
	}
	
	if ( !( f.listByCompany.checked || f.listByProject.checked || f.listByTask.checked || f.listByDepartment.checked || f.listByEmployee.checked ) ) {
		alert ('Please select at least one category for view');
		return false;
	}
	
}

// Refills a dependent list from the value of the select that changed.
function changeListFrom( select, listName, sourceName ) {
	changeList( listName, window[sourceName], select.options[select.selectedIndex].value );
}

function runCalendar(element) {
	return popCalendar(element);
}

var calendarField = '';
	
function popCalendar( field ){
	calendarField = field;
	idate = document.reportForm['format_' + field].value;
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = document.reportForm['format_' + calendarField];
	fld_fdate = document.reportForm[calendarField];
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

</script>

<form name="reportForm" action="index.php?m=reports" method="post"<?php echo dPaction('checkBillingFields', array(), 'submit'); ?>>

<table border="0" width="600">
	<tr>
		<td>Date Range</td>
		<td>
			<?php 
				$fmt_start_date = new CDate( $AppUI->getState( 'start_date_billing' ) );
				$fmt_end_date = new CDate( $AppUI->getState( 'end_date_billing' ) );
				$view_start_date_billing = $fmt_start_date->format( $df );
				$view_end_date_billing   = $fmt_end_date->format( $df );
			?>
			
			<input type="hidden" name="format_start_date_billing" value="<?php echo $fmt_start_date->format( FMT_TIMESTAMP_DATE );?>">
			<input type="text" class="text" name="start_date_billing" id="date1_billing" disabled="disabled" value="<?php echo $view_start_date_billing ?>">
			<a href="#"<?php echo dPaction('popCalendar', array('start_date_billing')); ?>>
				<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
			</a>
			&nbsp;&nbsp;
			-
			&nbsp;&nbsp;
			<input type="hidden" name="format_end_date_billing" value="<?php echo $fmt_end_date->format( FMT_TIMESTAMP_DATE );?>">
			<input type="text" class="text" name="end_date_billing" id="date2_billing" disabled="disabled" value="<?php echo $view_end_date_billing ?>">
			<a href="#"<?php echo dPaction('popCalendar', array('end_date_billing')); ?>>
				<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
			</a>
		</td>
	</tr>
	<tr>
		<td>Options</td>
		<td>
			
			<?php 
				$companyChecked =    $AppUI->getState( 'list_by_company_billing' ) ? " checked" : "";
				$projectChecked =    $AppUI->getState( 'list_by_project_billing' ) ? " checked" : "";
				$taskChecked =       $AppUI->getState( 'list_by_task_billing' ) ? " checked" : "";
				$departmentChecked = $AppUI->getState( 'list_by_department_billing') ? " checked" : "";
				$employeeChecked =   $AppUI->getState( 'list_by_employee_billing' ) ? " checked" : "";
				$detailsChecked =    $AppUI->getState( 'show_details_billing' ) ? " checked" : "";
			?>
			<?php
				$selectMe = $AppUI->getState('billing_report_employee') ? $AppUI->getState('billing_report_employee') : 0;
			?>

			<table border="0" width="300">
				<tr>
					<td width="150">
						<input type="checkbox" name="listByCompany" value="1" <?php echo $companyChecked ?>> List By Company
					</td>
					<td width="150">
						<?php 
						$params = "size=\"1\" style=\"width:150px;\" class=\"text\"" . dPaction('changeListFrom', array('$this', 'project', 'projects'));
						
						echo arraySelect( $companies, 'company', $params, @$AppUI->getState('billing_report_company'));
						?>

					</td>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="listByProject" value="1"<?php echo $projectChecked ?>> List By Project
					</td>
					<td>
						<select name="project" size="1" style="width:150px;" class="text"<?php echo dPaction('changeListFrom', array('$this', 'task', 'tasks')); ?>>
						</select>
					</td>
				</tr>

				<tr>
					<td>
						<input type="checkbox" name="listByTask" value="1"<?php echo $taskChecked ?>> List By Task
					</td>
					<td>
						<select name="task" size="1" style="width:150px;" class="text">
						</select>
					</td>
				</tr>

				<?php
				
				if ($AppUI->user_type > 0 and $AppUI->user_type < 7) {
				
					echo "\t\t\t\t<tr>\n";
						echo "\t\t\t\t\t<td colspan=\"2\"><hr noshade=\"noshade\" size=\"1\"></td>\n";
					echo "\t\t\t\t</tr>\n";
	
					echo "\t\t\t\t<tr>\n";
						echo "\t\t\t\t\t<td>\n";
							echo "\t\t\t\t\t\t<input type=\"checkbox\" name=\"listByDepartment\" value=\"1\"$departmentChecked > List By Department\n";
						echo "\t\t\t\t\t</td>\n";
						echo "\t\t\t\t\t<td>\n";
							
							$params = "size=\"1\" style=\"width:150px;\" class=\"text\"" . dPaction('changeListFrom', array('$this', 'employee', 'employees'));
							
							echo arraySelect( $departments, 'department', $params, @$AppUI->getState('billing_report_department'));
						echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
				
					echo "\t\t\t\t<tr>\n";
						echo "\t\t\t\t\t<td>\n";
							echo "\t\t\t\t\t\t<input type=\"checkbox\" name=\"listByEmployee\" value=\"1\" $employeeChecked > List By Employee\n";
						echo "\t\t\t\t\t</td>\n";
						echo "\t\t\t\t\t<td>\n";
							echo "\t\t\t\t\t\t<select name=\"employee\" size=\"1\" style=\"width:150px;\" class=\"text\">\n";
							echo "\t\t\t\t\t\t</select>\n";
						echo "\t\t\t\t\t</td>\n";
					echo "\t\t\t\t</tr>\n";
				
				} else {
					echo "<input type=\"hidden\" name=\"listByEmployee\" value=\"1\">\n";
					echo "<input type=\"hidden\" name=\"employee\" size=\"1\" value=\"" . $AppUI->user_id . "\">\n";
				}
				?>
				
				<tr>
					<td colspan="2"><hr noshade="noshade" size="1"></td>
				</tr>
				
				<tr>
					<td>
						<input type="checkbox" name="showDetails" value="1"<?php echo $detailsChecked ?>> Show Details
					</td>
					<td>
						<br>
					</td>
				</tr>
			</table>

		</td>
	</tr>
	<tr>
		<td colspan="2" align="right">
			<input type="submit" name="do_billing_report" value="Submit" class="button">
		</td>
</table>

</form>

<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
	changeList('project', projects, <?php echo (int)$AppUI->getState('billing_report_company');?>);
	changeList('task', tasks, <?php echo (int)$AppUI->getState('billing_report_project');?>);
	<?php if ($AppUI->user_type > 0 and $AppUI->user_type < 7) {?>
	changeList('employee', employees, <?php echo (int)$AppUI->getState('billing_report_department');?>);
	<?php } ?>
	selectList( 'project', <?php echo (int)$AppUI->getState('billing_report_project');?> );
	selectList( 'task', <?php echo (int)$AppUI->getState('billing_report_task');?> );
	<?php if ($AppUI->user_type > 0 and $AppUI->user_type < 7) {?>
	selectList( 'employee', <?php echo (int)$AppUI->getState('billing_report_employee');?> );
	<?php } ?>
</script>