<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* Hosting module -> $Id: addedit.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

$company_id = intval( dPgetParam( $_GET, "company_id", 0 ) );
$hosting_id = intval( dPgetParam( $_GET, "hosting_id", 0 ) );
$domain_id = intval( dPgetParam( $_GET, "domain_id", 0 ) );

require_once( $AppUI->getModuleClass ('companies' ) );

$row = new CCompany();
$companies = $row->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
$companies = arrayMerge( array( '0'=>'' ), $companies );

// setup the title block
$ttl = $domain_id > 0 ? "Edit Hosting/Domain" : "New Hosting/Domain";
$titleBlock = new CTitleBlock( $ttl, 'applet3-48.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=hosting", "list all" );
$titleBlock->addCrumbDelete( 'delete domain', true );
$titleBlock->show();

$row = new CHosting();

if ($domain_id && !$row->load($domain_id)) {
	$AppUI->setMsg('Hosting/Domain', UI_MSG_ERROR);
	$AppUI->setMsg('not found', UI_MSG_ERROR, true);
	$AppUI->redirect('m=hosting');
}
?>
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">
<form name="editFrm" action="./index.php?m=hosting" method="post">
	<input type="hidden" name="dosql" value="do_hosting_aed" />
	<input type="hidden" name="domain_id" value="<?php echo $domain_id;?>" />
	<input type="hidden" name="hosting_id" value="<?php echo $hosting_id?>" />
	<input type="hidden" name="del" value="0" />

<tr>
	<td width="50%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Domain Name');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="domain_name" value="<?php echo dPformSafe( $row->domain_name );?>" size="100" maxlength="150" class="text" style="width:100%; box-sizing:border-box" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Company');?></td>
			<td width="100%" nowrap="nowrap" colspan="2">
<?php
		echo arraySelect( $companies, 'company_id', 'class="text" size="1"', $row->company_id );
?> 			*</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Expiry Date (YYYY-mm-dd)');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="domain_expiry_date" value="<?php echo dPformSafe( $row->domain_expiry_date );?>" size="20" maxlength="10" class="text" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Registrar');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="domain_registrar" value="<?php echo dPformSafe( $row->domain_registrar );?>" size="100" maxlength="150" class="text" style="width:100%; box-sizing:border-box" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Registration Status');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="domain_status" value="<?php echo dPformSafe( $row->domain_status );?>" size="100" maxlength="150" class="text" style="width:100%; box-sizing:border-box" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap">
				<?php echo $AppUI->_('Domain Notes');?>
			</td>
			<td width="100%" colspan="2">
				<textarea name="domain_notes" style="width:100%; box-sizing:border-box" cols="50" rows="10" wrap="virtual" class="textarea"><?php echo dPformSafe( @$row->domain_notes );?></textarea>
			</td>
		</tr>
		</table>
		<br />
		<hr /> <!--    .....................  -->
		<br />
		<table cellspacing="0" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Hosting Package');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="hosting_package_name" value="<?php echo dPformSafe( $row->hosting_package_name );?>" size="100" maxlength="150" class="text" style="width:100%; box-sizing:border-box" />
			</td>
		</tr>
		
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Expiry Date (YYYY-mm-dd)');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="hosting_expiry_date" value="<?php echo dPformSafe( $row->hosting_expiry_date );?>" size="20" maxlength="10" class="text" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Registration Status');?></td>
			<td width="100%" colspan="2">
				<input type="text" name="hosting_status" value="<?php echo dPformSafe( $row->hosting_status );?>" size="100" maxlength="150" class="text" style="width:100%; box-sizing:border-box" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap">
				<?php echo $AppUI->_('Hosting Notes');?>
			</td>
			<td width="100%" colspan="2">
				<textarea name="hosting_notes" style="width:100%; box-sizing:border-box" cols="50" rows="10" wrap="virtual" class="textarea"><?php echo dPformSafe( $row->hosting_notes );?></textarea>
			</td>
		</tr>
		<tr>
			<td>
				<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel');?>" <?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Are you sure you want to cancel.', UI_OUTPUT_RAW), 'href' => './index.php?m=hosting')); ?> />
				<input class="button" type="submit" name="btnFuseAction" value="<?php echo $AppUI->_('submit');?>" />
			</td>
		</tr>
		</table>
	</td>
</tr>	
</form>
</table>