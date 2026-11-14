<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

class GanttRenderer {
    private static $assetsLoaded = false;

    static function assets() {
        if (self::$assetsLoaded) return;
        self::$assetsLoaded = true;
        $base = DP_BASE_URL . '/lib/frappe-gantt/';
        echo '<link rel="stylesheet" href="' . $base . 'frappe-gantt.min.css" />' . "\n";
        echo '<script src="' . $base . 'frappe-gantt.min.js"></script>' . "\n";
    }

    /**
     * The scripts that draw the charts, for a page shown without the theme header
     * (which loads every js/ file), such as a print view.
     */
    static function scripts() {
        foreach (array('dp-actions.js', 'dp-gantt.js') as $file) {
            echo '<script src="' . DP_BASE_URL . '/js/' . $file . '"></script>' . "\n";
        }
    }

    /**
     * Chart rows for task rows (task_id, task_name, task_start_date, task_end_date,
     * task_percent_complete), with the dependencies between those tasks.
     */
    static function taskRows(array $tasks) {
        $deps = array();
        $ids = array_map('intval', array_column($tasks, 'task_id'));
        if ($ids) {
            $q = new DBQuery;
            $q->addTable('task_dependencies');
            $q->addQuery('dependencies_task_id, dependencies_req_task_id');
            $q->addWhere('dependencies_task_id IN (' . implode(',', $ids) . ')');
            $q->addWhere('dependencies_req_task_id IN (' . implode(',', $ids) . ')');
            foreach ($q->loadList() as $dep) {
                $deps[$dep['dependencies_task_id']][] = (int)$dep['dependencies_req_task_id'];
            }
        }
        $rows = array();
        foreach ($tasks as $t) {
            $start = substr((string)$t['task_start_date'], 0, 10);
            $end   = substr((string)$t['task_end_date'], 0, 10);
            if (empty($start) || $start == '0000-00-00') $start = date('Y-m-d');
            if (empty($end) || $end == '0000-00-00' || $end <= $start) {
                $end = date('Y-m-d', strtotime($start . ' +1 day'));
            }
            $rows[] = array(
                'id'           => (string)$t['task_id'],
                'name'         => $t['task_name'],
                'start'        => $start,
                'end'          => $end,
                'progress'     => intval($t['task_percent_complete']),
                'dependencies' => isset($deps[$t['task_id']]) ? implode(',', $deps[$t['task_id']]) : '',
            );
        }
        return $rows;
    }

    /** Chart rows for the tasks of a project that the current user may see, by start date. */
    static function projectTaskRows($project_id) {
        global $AppUI;
        $q = new DBQuery;
        $q->addTable('tasks', 't');
        $q->addQuery('t.task_id, t.task_name, t.task_start_date, t.task_end_date, t.task_percent_complete');
        $q->addWhere('t.task_project = ' . (int)$project_id);
        $q->addOrder('t.task_start_date, t.task_id');
        $task = new CTask;
        $task->setAllowedSQL($AppUI->user_id, $q);
        return self::taskRows($q->loadList());
    }

    /**
     * Render a frappe-gantt chart.
     *
     * @param string $containerId  Unique HTML id for the SVG element.
     * @param array  $tasks        Rows: {id, name, start (YYYY-MM-DD), end (YYYY-MM-DD), progress, dependencies (csv)}.
     * @param string $title        Optional heading text.
     * @param string $viewMode     Initial view: Day|Week|Month.
     * @param bool   $editable     Save task bars the user drags (tasks/do_gantt_dates.php checks
     *                             each task); otherwise a dragged bar moves back.
     */
    static function render($containerId, array $tasks, $title = '', $viewMode = 'Week', $editable = false) {
        global $AppUI;
        self::assets();
        if (empty($tasks)) {
            echo '<p style="padding:10px;color:#666;">';
            if ($title) echo htmlspecialchars($title) . ': ';
            echo $AppUI->_('No items to display.') . '</p>';
            return;
        }
        // frappe-gantt writes the name into the bar label and the popup with
        // innerHTML, so every value is HTML-escaped here. Before, a task named
        // <image href=x onerror=...> ran its script when the chart was drawn.
        $rows = array();
        foreach ($tasks as $task) {
            $row = array();
            foreach ($task as $key => $value) {
                $row[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
            }
            $rows[] = $row;
        }
        $json = json_encode($rows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $cid  = htmlspecialchars($containerId, ENT_QUOTES, 'UTF-8');
        $vm   = htmlspecialchars($viewMode, ENT_QUOTES, 'UTF-8');
        ?>
<?php if ($title): ?>
<h3 style="margin:8px 0 4px;font-size:14px;"><?php echo htmlspecialchars($title); ?></h3>
<?php endif; ?>
<div style="margin:0 0 4px;font-size:12px;">
  <strong><?php echo $AppUI->_('View'); ?>:</strong>
  <?php foreach (['Day', 'Week', 'Month'] as $v): ?>
  <button type="button"<?php echo dPaction('dpGanttViewMode', array($containerId, $v)); ?> style="margin:0 2px;padding:2px 8px;cursor:pointer;"><?php echo $AppUI->_($v); ?></button>
  <?php endforeach; ?>
  &nbsp;&nbsp;<button type="button"<?php echo dPaction('window.print'); ?> style="padding:2px 8px;cursor:pointer;"><?php echo $AppUI->_('Print'); ?></button>
</div>
<div style="overflow-x:auto;background:#fff;padding:10px;border:1px solid #ccc;box-sizing:border-box;width:0;min-width:100%;">
  <svg id="<?php echo $cid; ?>"></svg>
</div>
<?php // Drawn by js/dp-gantt.js; a JSON data block is not executed, so it needs no CSP nonce. ?>
<p class="dp-gantt-status" data-for="<?php echo $cid; ?>" role="status" aria-live="polite" style="margin:4px 0;font-size:12px;min-height:1em;"></p>
<script type="application/json" class="dp-gantt-data" data-target="<?php echo $cid; ?>" data-view-mode="<?php echo $vm; ?>"<?php
        if ($editable && getPermission('tasks', 'edit')) {
            echo ' data-save-url="?m=tasks&amp;a=do_gantt_dates&amp;suppressHeaders=1"'
                . ' data-saving="' . $AppUI->_('Saving...', UI_OUTPUT_HTML) . '"'
                . ' data-saved="' . $AppUI->_('Saved.', UI_OUTPUT_HTML) . '"'
                . ' data-error="' . $AppUI->_('The change could not be saved.', UI_OUTPUT_HTML) . '"'
                . ' data-not-task="' . $AppUI->_('Only task bars can be moved here.', UI_OUTPUT_HTML) . '"'
                . ' data-undo="' . $AppUI->_('Undo', UI_OUTPUT_HTML) . '"'
                . ' data-undone="' . $AppUI->_('The change was undone.', UI_OUTPUT_HTML) . '"'
                . ' data-cancelled="' . $AppUI->_('Move cancelled.', UI_OUTPUT_HTML) . '"';
        } else {
            echo ' data-readonly="' . $AppUI->_('This chart only shows the dates.', UI_OUTPUT_HTML) . '"'
                . ' data-cancelled="' . $AppUI->_('Move cancelled.', UI_OUTPUT_HTML) . '"';
        }
        ?>><?php echo $json; ?></script>
        <?php
    }
}
