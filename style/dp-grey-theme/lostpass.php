<?php /* STYLE/DEFAULT $Id: lostpass.php 5972 2010-03-29 00:05:34Z merlinyoda $ */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?php echo @dPgetConfig('page_title');?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=<?php echo isset($locale_char_set) ? $locale_char_set : 'UTF-8';?>" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta name="Version" content="<?php echo @$AppUI->getVersion();?>" />
	<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle;?>/main.css" media="all" />
	<link rel="shortcut icon" href="./style/<?php echo $uistyle;?>/images/favicon.ico" type="image/ico" />
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
</head>

<body style="background-color: #f0f0f0">
<br /><br /><br /><br />
<?php //please leave action argument empty ?>
<!--form action="./index.php" method="post" name="loginform"-->
<form method="post" name="lostpassform">
<table align="center" border="0" width="250" cellpadding="6" cellspacing="0" class="std">
<input type="hidden" name="lostpass" value="1" />
<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES);?>" />
<tr>
	<th colspan="2"><em><?php echo $dPconfig['company_name'];?></em></th>
</tr>
<tr>
	<td align="right" nowrap><?php echo $AppUI->_('Username');?>:</td>
	<td align="left" nowrap><input type="text" size="25" maxlength="20" name="checkusername" autofocus="autofocus" class="text" /></td>
</tr>
<tr>
	<td align="right" nowrap><?php echo $AppUI->_('EMail');?>:</td>
	<td align="left" nowrap><input type="email" size="25" maxlength="32" name="checkemail" class="text" /></td>
</tr>
<tr>
	<td align="left" nowrap><a href="https://github.com/davmont/dotPulse"><img src="./images/brand/logo.svg" height="28" border="0" alt="dotPulse" /></a></td>
	<td align="right" valign="bottom" nowrap><input type="submit" name="sendpass" value="<?php echo $AppUI->_('send password');?>" class="button" /></td>
</tr>
</table>
<?php if (@$AppUI->getVersion()) { ?>
<div align="center">
	<span style="font-size:7pt">Version <?php echo @$AppUI->getVersion();?></span>
</div>
<?php } ?>
</form>
<div align="center">
<?php
	echo dPcheckLoginSystem();
?>
</div>
</body>
</html>
