<?php /* CONTACTS $Id: vcardimport.php 5872 2009-04-25 00:09:56Z merlinyoda $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$canAuthor = getPermission('contacts', 'add');
if (!($canAuthor)) {
	$AppUI->redirect('m=public&a=access_denied');
}

// check whether vCard file should be fetched from source or parsed for vCardKeys; criteria: get parameters
if (isset($_FILES['vcf']) && isset($_GET['suppressHeaders']) && ($_GET['suppressHeaders']=='true')) {	//parse and store vCard file

	// This view stores contacts: same CSRF rule as the dosql handlers.
	$AppUI->verifyCsrfToken();
	$vcf = $_FILES['vcf'];
	require_once $AppUI->getSystemClass('vcard');

	if (is_uploaded_file($vcf['tmp_name'])) {

		// parse the vCard file: one file can contain multiple vCards
		$cardinfo = CVcard::parse(file_get_contents($vcf['tmp_name']));

		// store the card info array

		foreach ($cardinfo as $ci) {	//one file can contain multiple vCards

			$obj = new CContact();
			// A value of the card: property, occurrence, component (empty when missing).
			$v = function ($name, $index, $component) use ($ci) {
				return isset($ci[$name][$index]['value'][$component][0]) ? $ci[$name][$index]['value'][$component][0] : '';
			};

			//transform the card info array to dP store format
			$contactValues = array();
			$contactValues["contact_last_name"] = $v('N', 0, 0);
			$contactValues["contact_first_name"] = $v('N', 0, 1);
			$contactValues["contact_title"] = $v('N', 0, 3);
			$contactValues["contact_birthday"] = $v('BDAY', 0, 0);
			// contact_company is a company id: the organisation's company, when there is one.
			$contactValues["contact_company"] = 0;
			if ($v('ORG', 0, 0) !== '') {
				$q = new DBQuery;
				$q->addTable('companies');
				$q->addQuery('company_id');
				$q->addWhere('company_name = ' . $q->quote($v('ORG', 0, 0)));
				$contactValues["contact_company"] = (int)$q->loadResult();
			}
			// The contact type travels as the card's first category (older exports put it
			// in the middle-name slot of N, so it is not read from there). A middle name has no
			// contact field and is not imported.
			$contactValues["contact_type"] = $v('CATEGORIES', 0, 0);
			$contactValues["contact_email"] = $v('EMAIL', 0, 0);
			$contactValues["contact_email2"] = $v('EMAIL', 1, 0);
			$contactValues["contact_phone"] = $v('TEL', 0, 0);
			$contactValues["contact_phone2"] = $v('TEL', 1, 0);
			$contactValues["contact_mobile"] = $v('TEL', 2, 0);
			if ($v('ADR', 0, 1) !== '') {
				$contactValues['contact_address1'] = $v('ADR', 0, 1);
				$contactValues['contact_address2'] = $v('ADR', 0, 2);
			} else {
				$contactValues['contact_address1'] = $v('ADR', 0, 2);
				$contactValues['contact_address2'] = '';
			}
			$contactValues["contact_city"] = $v('ADR', 0, 3);
			$contactValues["contact_state"] = $v('ADR', 0, 4);
			$contactValues["contact_zip"] = $v('ADR', 0, 5);
			$contactValues["contact_country"] = $v('ADR', 0, 6);
			$contactValues["contact_notes"] = $v('NOTE', 0, 0);
			$contactValues["contact_order_by"] = $contactValues["contact_last_name"].', '.$contactValues["contact_first_name"];
			$contactValues["contact_id"] = 0;

			// bind array to object
			if (!$obj->bind($contactValues)) {
				$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
				$AppUI->redirect();
			}

			// store vCard data for this object
			if (($msg = $obj->store())) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
			}
		}
		// one or more vCard imports were successful
		$AppUI->setMsg('vCard(s) imported', UI_MSG_OK, true);
		$AppUI->redirect();

	}
	else {	// redirect in case of file upload trouble
		$AppUI->setMsg("vCardFileUploadError", UI_MSG_ERROR);
		$AppUI->redirect();
	}
}
else if (isset($_GET['dialog']) && ($_GET['dialog']=='0')) {	//file upload formular

$titleBlock = new CTitleBlock('Import vCard', 'monkeychat-48.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=contacts", "contacts list");
$titleBlock->show();

?>

<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std">

	<form name="vcfFrm" action="?m=contacts&a=vcardimport&suppressHeaders=true" enctype="multipart/form-data" method="post">
		<input type="hidden" name="max_file_size" value="109605000" />
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Fetch vCard(s) File');?>:</td>
		<td align="left"><input type="File" class="button" name="vcf" style="width:280px" accept="text/x-vcard"></td>
	</tr>
	<tr>
		<td align="right" colspan="2" nowrap="nowrap"><input type="submit" class="button" value="<?php echo $AppUI->_('submit'); ?>"/></td>
	</tr>
	</form>
</table>

<?php } else {	// trouble with get parameters
$AppUI->setMsg("vCardImportError", UI_MSG_ERROR);
	$AppUI->redirect();
}

?>
