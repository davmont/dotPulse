<?php /* STYLE/DEFAULT $Id: lostpass.php 5872 2009-04-25 00:09:56Z merlinyoda $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}
?>
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title><?php echo @dPgetConfig('page_title'); ?></title>
	<meta http-equiv="Content-Type"
		content="text/html;charset=<?php echo isset($locale_char_set) ? $locale_char_set : 'UTF-8'; ?>" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta name="Version" content="<?php echo @$AppUI->getVersion(); ?>" />
	<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle; ?>/main.css" media="all" />
	<link rel="shortcut icon" href="./style/<?php echo $uistyle; ?>/images/favicon.ico" type="image/ico" />
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
<style type="text/css">
	body {
		background-color: #f0f2f5 !important;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		height: 100vh;
		margin: 0;
		font-family: Arial, sans-serif;
	}

	.login-container {
		background: #ffffff;
		padding: 30px 40px;
		border-radius: 8px;
		box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
		width: 55%;
		max-width: 600px;
		min-width: 300px;
	}

	.login-container table {
		width: 100%;
	}

	.login-container th {
		font-size: 1.25em;
		padding-bottom: 20px;
		text-align: center;
		color: #333;
	}

	.login-container td {
		padding: 5px 0;
		text-align: left;
	}

	.login-container input.text {
		width: 100%;
		padding: 10px;
		border: 1px solid #ccc;
		border-radius: 4px;
		box-sizing: border-box;
		margin-bottom: 10px;
	}

	.login-container .button {
		background-color: #1976d2;
		color: #fff;
		border: none;
		padding: 10px;
		border-radius: 4px;
		cursor: pointer;
		width: 100%;
		font-size: 1em;
		font-weight: bold;
	}

	.login-container .button:hover {
		background-color: #115293;
	}

	.login-footer {
		margin-top: 15px;
		text-align: center;
		font-size: 0.9em;
	}

	.login-footer a {
		text-decoration: none;
		color: #08245b;
	}

	.login-footer a:hover {
		text-decoration: underline;
	}

	.system-messages {
		margin-top: 20px;
		font-size: 0.85em;
		color: #666;
	}
</style>
</head>

<body>
	<div class="login-container">
		<form method="post" name="lostpassform">
			<input type="hidden" name="lostpass" value="1" />
			<input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect, ENT_QUOTES);?>" />

			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<th colspan="2"><em><?php echo $dPconfig['company_name']; ?></em></th>
				</tr>
				<tr>
					<td colspan="2"><input type="text" maxlength="20" name="checkusername" autofocus="autofocus" class="text"
							placeholder="<?php echo $AppUI->_('Username'); ?>" /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="email" maxlength="64" name="checkemail" class="text"
							placeholder="<?php echo $AppUI->_('EMail'); ?>" /></td>
				</tr>
				<tr>
					<td align="left" valign="middle" width="50%"><a href="https://github.com/davmont/dotPulse"><img src="./images/brand/logo.svg" height="28" border="0" alt="dotPulse" /></a></td>
					<td align="right" valign="middle" width="50%"><input type="submit" name="sendpass"
							value="<?php echo $AppUI->_('send password'); ?>" class="button" /></td>
				</tr>
			</table>
		</form>
	</div>

	<div class="system-messages" align="center">
		<?php if (@$AppUI->getVersion()) { ?>
			Version <?php echo @$AppUI->getVersion(); ?><br />
		<?php } ?>
		<?php echo dPcheckLoginSystem(); ?>
	</div>

</body>

</html>