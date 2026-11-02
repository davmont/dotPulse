<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('tasks');

/**
 * Data for the personal dashboard (index.php): the user's open tasks, their projects,
 * the hours they logged this week and their coming events. Every list goes through the
 * same permission checks as the task and project pages.
 */
class CDashboard
{
	const MAX_TASKS = 100;
	/** Project statuses left out: Template and Archived (ProjectStatus). */
	const HIDDEN_PROJECT_STATUSES = '6, 7';

	var $user_id;
	var $now;

	function __construct($user_id, $now = null)
	{
		$this->user_id = (int)$user_id;
		$this->now = $now === null ? date('Y-m-d H:i:s') : $now;
	}

	/**
	 * Open tasks assigned to the user (active, below 100 %, not task groups), soonest end
	 * date first, each with a 'due' of overdue, week (within 7 days), later or none.
	 */
	function myTasks()
	{
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->innerJoin('user_tasks', 'ut', 'ut.task_id = t.task_id AND ut.user_id = ' . $this->user_id);
		$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
		$q->addQuery('t.task_id, t.task_name, t.task_project, t.task_owner, t.task_access, t.task_start_date, '
			. 't.task_end_date, t.task_percent_complete, t.task_priority, t.task_milestone, '
			. 'p.project_name, p.project_color_identifier');
		$q->addWhere('t.task_percent_complete < 100 AND t.task_status = 0 AND t.task_dynamic <> 1');
		$q->addWhere('p.project_status NOT IN (' . self::HIDDEN_PROJECT_STATUSES . ')');
		$this->allowTasks($q);
		$q->addOrder("CASE WHEN t.task_end_date IS NULL OR t.task_end_date = '0000-00-00 00:00:00' THEN 1 ELSE 0 END, "
			. 't.task_end_date, t.task_priority DESC, t.task_id');
		$q->setLimit(self::MAX_TASKS * 2);
		$week = date('Y-m-d H:i:s', strtotime($this->now) + 7 * 86400);
		$tasks = array();
		foreach ($q->loadList() as $row) {
			if (count($tasks) == self::MAX_TASKS || !$this->canAccessTask($row)) {
				continue;
			}
			$end = $row['task_end_date'];
			if (!$end || strpos($end, '0000-00-00') === 0) {
				$row['due'] = 'none';
			} else if ($end < $this->now) {
				$row['due'] = 'overdue';
			} else if ($end <= $week) {
				$row['due'] = 'week';
			} else {
				$row['due'] = 'later';
			}
			$tasks[] = $row;
		}
		return $tasks;
	}

	/**
	 * Projects the user owns or has open tasks in (not templates or archived), soonest end
	 * date first, with their progress, open tasks and overdue tasks.
	 */
	function myProjects()
	{
		$q = new DBQuery;
		$q->addTable('projects', 'p');
		$q->addQuery('p.project_id, p.project_name, p.project_status, p.project_end_date, p.project_owner, '
			. 'p.project_color_identifier');
		$q->addWhere('p.project_status NOT IN (' . self::HIDDEN_PROJECT_STATUSES . ')');
		$q->addWhere('(p.project_owner = ' . $this->user_id . ' OR p.project_id IN (SELECT t.task_project FROM '
			. $this->table('tasks') . ' t JOIN ' . $this->table('user_tasks') . ' ut ON ut.task_id = t.task_id'
			. ' WHERE ut.user_id = ' . $this->user_id . ' AND t.task_percent_complete < 100))');
		$project = new CProject;
		$project->setAllowedSQL($this->user_id, $q, null, 'p');
		$q->addOrder("CASE WHEN p.project_end_date IS NULL OR p.project_end_date = '0000-00-00 00:00:00' THEN 1 ELSE 0 END, "
			. 'p.project_end_date, p.project_name');
		$projects = $q->loadHashList('project_id');
		if (!$projects) {
			return array();
		}
		$ids = array_keys($projects);
		foreach (CProject::percentComplete($ids) as $id => $pc) {
			$projects[$id]['percent_complete'] = $pc;
		}
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->addQuery('t.task_project, COUNT(*) AS open_tasks, SUM(CASE WHEN t.task_end_date < '
			. $q->quote($this->now) . " AND t.task_end_date <> '0000-00-00 00:00:00' THEN 1 ELSE 0 END) AS overdue_tasks");
		$q->addWhere('t.task_project IN (' . implode(',', $ids) . ')');
		$q->addWhere('t.task_percent_complete < 100 AND t.task_status = 0 AND t.task_dynamic <> 1');
		$q->addGroup('t.task_project');
		foreach ($projects as $id => $p) {
			$projects[$id]['open_tasks'] = 0;
			$projects[$id]['overdue_tasks'] = 0;
		}
		foreach ($q->loadList() as $row) {
			$projects[$row['task_project']]['open_tasks'] = (int)$row['open_tasks'];
			$projects[$row['task_project']]['overdue_tasks'] = (int)$row['overdue_tasks'];
		}
		return array_values($projects);
	}

	/**
	 * Hours the user logged from Monday of this week to Sunday: day (Y-m-d) => hours,
	 * seven entries.
	 */
	function hoursThisWeek()
	{
		$today = strtotime(substr($this->now, 0, 10));
		$monday = $today - ((int)date('N', $today) - 1) * 86400;
		$days = array();
		for ($i = 0; $i < 7; $i++) {
			$days[date('Y-m-d', strtotime("+$i day", $monday))] = 0.0;
		}
		$q = new DBQuery;
		$q->addTable('task_log', 'l');
		$q->addQuery('DATE(l.task_log_date) AS day, SUM(l.task_log_hours) AS hours');
		$q->addWhere('l.task_log_creator = ' . $this->user_id);
		$q->addWhere('l.task_log_date >= ' . $q->quote(key($days) . ' 00:00:00')
			. ' AND l.task_log_date < ' . $q->quote(date('Y-m-d', strtotime('+7 day', $monday)) . ' 00:00:00'));
		$q->addGroup('DATE(l.task_log_date)');
		foreach ($q->loadList() as $row) {
			if (isset($days[$row['day']])) {
				$days[$row['day']] = round((float)$row['hours'], 2);
			}
		}
		return $days;
	}

	/** The user's events (invited or own) from now to the end of the 7th day, in order. */
	function upcomingEvents()
	{
		global $AppUI;
		require_once $AppUI->getModuleClass('calendar');
		$start = new CDate($this->now);
		$end = new CDate(date('Y-m-d 23:59:59', strtotime($this->now) + 7 * 86400));
		$events = array();
		$seen = array();
		foreach (array('my', 'own') as $filter) {
			foreach (CEvent::getEventsForPeriod($start, $end, $filter, $this->user_id) as $e) {
				$key = $e['event_id'] . '|' . $e['event_start_date'];
				if (!isset($seen[$key])) {
					$seen[$key] = true;
					$events[] = $e;
				}
			}
		}
		usort($events, function ($a, $b) {
			return strcmp($a['event_start_date'], $b['event_start_date']);
		});
		return array_slice($events, 0, 20);
	}

	// ---- permissions

	function allowTasks($q)
	{
		$project = new CProject;
		$project->setAllowedSQL($this->user_id, $q, null, 'p');
		$task = new CTask;
		foreach ($task->getAllowedSQL($this->user_id, 't.task_id') as $where) {
			$q->addWhere($where);
		}
	}

	function canAccessTask($row)
	{
		$task = new CTask;
		$task->task_id = $row['task_id'];
		$task->task_access = $row['task_access'];
		$task->task_project = $row['task_project'];
		$task->task_owner = $row['task_owner'];
		return getPermission('tasks', 'view', $row['task_id']) && $task->canAccess($this->user_id);
	}

	function table($name)
	{
		return dPgetConfig('dbprefix', '') . $name;
	}
}
