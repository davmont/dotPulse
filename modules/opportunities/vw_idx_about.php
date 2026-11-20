<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// this is an easy example showing how to use some of the UserInterface methods provided by the dPframework
// we will not have any database connection here

// as we are now within the tab box, we have to state (call) the needed information saved in the variables of the parent function
GLOBAL $AppUI, $canRead, $canEdit, $m;

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}
//provide some text used down below
$ainfo = "Opportunities is a new 'dotmod' (dotproject module) aiming at capturing ideas and/or issues which can originate projects.\n\n" . 
	"Opportunities may be interrelated with other opportunities, may originate directly a project or may be " .
	"grouped with other opportunities to compose a single project. In some cases, an opportunity may be addressed in several " .
	"projects.\n\nOpportunities will be described and qualified, which may require some pre-studies, in order that " .
	"a decision to get into project mode can be taken. This pre-project process or filter allows to identify ideas and/or " .
	"issues without getting into the formalities and protocols associated with Project Management.\n\n" .
	"In practice the process of qualifying an opportunity may originate complementary docs which will be associated " .
	"via the Files module.\n\nFinally, the same qualifying process may originate costs and those must be accounted " .
	"into the financials of each opportunity";
?>
<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
	<tr>
		<td align="center" valign="middle">
		<?php
		$imPath = dPfindImage('opportunity.jpg', $m);		// use the dPFramework to search for an image in a number of places
		echo dPshowImage( $imPath, '200', '100' ); 			// use the dPFramework to show this image, use the parameters as '200', '100' to resize as you want
		?>
		</td>
		<td>
			<textarea name="about" cols="120" readonly="readonly" style="width:100%; box-sizing:border-box; height:100px; font-size:8pt"><?php

				echo dPformSafe($ainfo); 	// use dPformSafe to run several safings on the text used in forms (i18n, htmlspecialchars)

			?></textarea>
		</td>
	</tr>
</table>
