<?php /* STYLE/DOTPULSE: tab boxes drawn with CSS */

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}
// The vector icons (tools/icons/build.py) instead of the bitmaps.
if (!defined('DP_VECTOR_ICONS')) {
	define('DP_VECTOR_ICONS', true);
}
class CTitleBlock extends CTitleBlock_core
{
}

##
##  This overrides the show function of the CTabBox_core function
##
class CTabBox extends CTabBox_core
{
	function show($extra = '', $js_tabs = false)
	{
		global $AppUI, $dPconfig, $currentTabId, $currentTabName;
		$uistyle = ($AppUI->getPref('UISTYLE')
			? $AppUI->getPref('UISTYLE')
			: (($dPconfig['host_style']) ? $dPconfig['host_style'] : 'default'));
		reset($this->tabs);
		$s = '';

		// tabbed / flat view options
		if (@$AppUI->getPref('TABVIEW') == 0) {
			$s .= '<table class="dp-tabview" border="0" cellpadding="2" cellspacing="0" width="100%">' . "\n";
			$s .= "<tr>\n";
			$s .= '<td nowrap="nowrap">' . "\n";
			$s .= '<a href="' . $this->baseHRef . 'tab=0">' . $AppUI->_('tabbed') . '</a> : ';
			$s .= '<a href="' . $this->baseHRef . 'tab=-1">' . $AppUI->_('flat') . '</a>' . "\n";
			$s .= "</td>\n" . $extra . "\n</tr>\n</table>\n";
			echo $s;
		} else {
			if ($extra) {
				echo ('<table border="0" cellpadding="2" cellspacing="0" width="100%">'
					. "\n<tr>\n" . $extra . "</tr>\n</table>\n");
			} else {
				echo '<img src="./images/shim.gif" height="10" width="1" alt="" />' . "\n";
			}
		}

		if ($this->active < 0 || @$AppUI->getPref('TABVIEW') == 2) {
			// flat view, active = -1
			echo '<table border="0" cellpadding="2" cellspacing="0" width="100%" summary="flat view">' . "\n";
			foreach ($this->tabs as $k => $v) {
				echo '<tr><td><strong>' . ($v[2] ? $v[1] : $AppUI->_($v[1])) . "</strong></td></tr>\n";
				echo '<tr><td>';
				$currentTabId = $k;
				$currentTabName = $v[1];
				include $this->baseInc . $v[0] . ".php";
				echo "</td></tr>\n";
			}
			echo "</table>\n";
		} else {
			// tabbed view: links styled as tabs (main.css), no images
			if (count($this->tabs) - 1 < $this->active) {
				//Last selected tab is not available in this view. eg. Child tasks
				$this->active = 0;
			}
			$s = '<table width="100%" border="0" cellpadding="0" cellspacing="0" summary="tabbed view">' . "\n";
			$s .= '<tr><td>' . "\n" . '<div class="tabrow">';
			foreach ($this->tabs as $k => $v) {
				$class = 'dp-tab' . (($k == $this->active) ? ' dp-tab-on' : '');
				$s .= '<span id="toptab_' . $k . '" class="' . $class . '"><a href="';
				if ($this->javascript) {
					$s .= '#" data-dp-call="' . htmlspecialchars($this->javascript, ENT_QUOTES) . '" data-dp-args="[' . (int)$this->active . ',' . (int)$k . ']';
				} else if ($js_tabs) {
					$s .= '#" data-dp-call="show_tab" data-dp-args="[' . (int)$k . ']';
				} else {
					$s .= $this->baseHRef . 'tab=' . $k;
				}
				$s .= '"' . (($k == $this->active) ? ' aria-current="page"' : '') . '>' . (($v[2]) ? $v[1] : $AppUI->_($v[1])) . '</a></span>';
			}
			$s .= '</div>' . "\n" . '</td></tr>' . "\n";
			$s .= '<tr><td width="100%" class="tabox">' . "\n";
			echo $s;
			//Will be null if the previous selection tab is not available in the new window eg. Children tasks
			if (isset($this->tabs[$this->active]) && isset($this->tabs[$this->active][0]) && $this->tabs[$this->active][0] != "") {
				$currentTabId = $this->active;
				$currentTabName = $this->tabs[$this->active][1];
				if (!$js_tabs)
					require $this->baseInc . $this->tabs[$this->active][0] . '.php';
			}
			if ($js_tabs) {
				foreach ($this->tabs as $k => $v) {
					echo '<div class="tab" id="tab_' . $k . '"' . ($k == $this->active ? '' : ' style="display:none"') . '>';
					$currentTabId = $k;
					$currentTabName = $v[1];
					require $this->baseInc . $v[0] . '.php';
					echo '</div>';
				}
			}
			echo '</td></tr>' . "\n" . '</table>' . "\n";
		}
	}
}
?>