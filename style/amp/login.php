<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title><?php echo dPhtml((string)dPgetConfig('page_title'));?></title>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo isset($locale_char_set) ? $locale_char_set : 'UTF-8';?>" />
<title><?php echo dPhtml((string)dPgetConfig('company_name'));?>:: dotPulse</title>
<meta http-equiv="Pragma" content="no-cache" />
<meta name="Version" content="<?php echo @$AppUI->getVersion();?>" />
<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle;?>/main.css" media="all" />
<style type="text/css" media="all">
@import "./style/<?php echo $uistyle;?>/main.css";
</style>
<link rel="shortcut icon" href="./style/<?php echo $uistyle;?>/img/favicon.ico" type="image/ico" />
<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
</head>
<body bgcolor="#f0f0f0">
<center>
<br />
<br />
<div class="company_name" style="padding:0px;"><a href="<?php echo $dPconfig['base_url'] ?>"><?php echo dPhtml((string)dPgetConfig('company_name')) ?></a></div>
</div>
<br />
<form method="post" action="<?php echo $loginFromPage; ?>" name="loginform" id="loginform">
	<table cellpadding="0" cellspacing="0" border="0" width="300px">
	<tr>
		<td><img src="style/<?php echo $uistyle;?>/img/tl.png" /></td>
		<td bgcolor="#ffffff" height="8px"></td>
		<td><img src="style/<?php echo $uistyle;?>/img/tr.png" /></td>
	</tr>
	<tr>
		<td bgcolor="#ffffff" width="8px"></td>
		<td bgcolor="#ffffff"><table align="center" border="0" width="250" cellpadding="6" cellspacing="0" class="std">
				<input type="hidden" name="login" value="<?php echo time();?>" />
				<input type="hidden" name="lostpass" value="0" />
				<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES);?>" />
				<tr>
					<td align="right" nowrap><?php echo $AppUI->_('Username');?>:</td>
					<td align="left" nowrap><input type="text" size="25" maxlength="20" name="username" autofocus="autofocus" class="text" /></td>
				</tr>
				<tr>
					<td align="right" nowrap><?php echo $AppUI->_('Password');?>:</td>
					<td align="left" nowrap><input type="password" size="25" maxlength="32" name="password" class="text" /></td>
				</tr>
				<tr>
					<td align="left" nowrap></td>
					<td align="right" valign="bottom" nowrap><input type="submit" name="login" value="<?php echo $AppUI->_('login');?>" class="button" /></td>
				</tr>
				<tr>
					<td colspan="2" align="center"><button type="submit" form="loginform" name="lostpass" value="1" formnovalidate="formnovalidate" style="background:none;border:0;padding:0;margin:0;font:inherit;color:inherit;text-decoration:underline;cursor:pointer"><?php echo $AppUI->_('forgotPassword'); ?></button></td>
				</tr>
			</table>
			<?php if (@$AppUI->getVersion()) { ?>
			<div align="center"><span style="font-size:7pt">Version <?php echo @$AppUI->getVersion();?></span></div>
			<?php } ?>
</form>
<div align="center" style="font-size:9px;"><?php echo dPcheckLoginSystem(); ?></div>
<center>
<div style="font-size:10px;color:#999999; width:250px;">
<?php echo "* ".$AppUI->_("You must have cookies enabled in your browser"); ?>
</center>
</td>
<td bgcolor="#FFFFFF" width="8px"></td>
</tr>
<tr>
	<td><img src="style/<?php echo $uistyle;?>/img/bl.png" /></td>
	<td bgcolor="#FFFFFF"></td>
	<td><img src="style/<?php echo $uistyle;?>/img/br.png" /></td>
</tr>
</table>
</body>
</html>