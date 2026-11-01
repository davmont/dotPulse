<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 * Project tips: checks of a project's plan against good practice (PMBOK), shown
 * in the header bell on project pages (modules/dotproject_plus/header_widget.php).
 * Each rule reads the tables of the modules it names and runs only when they are
 * all active. What a user has read is kept per project (feedback_read).
 */
class CProjectFeedback
{
	/**
	 * id => array(area, required modules, check). The ids are those of the
	 * dotProject+ instructional feedback the rules come from.
	 */
	static function rules()
	{
		return array(
			1 => array('Integration', array('initiating'), 'noCharter'),
			2 => array('Integration', array('initiating'), 'charterNotAuthorized'),
			3 => array('Integration', array('initiating'), 'endsAfterCharter'),
			4 => array('Scope', array('timeplanning'), 'wbsSmall'),
			5 => array('Scope', array('timeplanning'), 'wbsLarge'),
			6 => array('Scope', array('timeplanning'), 'wbsUnsized'),
			35 => array('Scope', array('timeplanning'), 'wbsSizeInTime'),
			8 => array('Schedule', array(), 'circularDependencies'),
			9 => array('Schedule', array('timeplanning'), 'workPackageWithOneActivity'),
			11 => array('Schedule', array('timeplanning'), 'longActivities'),
			38 => array('Schedule', array('timeplanning'), 'smallEfforts'),
			39 => array('Schedule', array('timeplanning'), 'largeEfforts'),
			14 => array('Cost', array('risks', 'costs'), 'risksWithoutContingencyReserve'),
			16 => array('Cost', array('costs'), 'noManagementReserve'),
			15 => array('Cost', array('monitoringandcontrol'), 'usersWithoutHourlyCost'),
			44 => array('Cost', array('monitoringandcontrol'), 'costOverrun'),
			45 => array('Schedule', array('monitoringandcontrol'), 'behindSchedule'),
			18 => array('Resources', array('timeplanning', 'human_resources'), 'nobodyAllocated'),
			20 => array('Resources', array('timeplanning', 'human_resources'), 'overlappingAllocations'),
			26 => array('Risk', array('risks'), 'eliminatedWithContingency'),
			27 => array('Risk', array('risks'), 'acceptedWithPrevention'),
			43 => array('Risk', array('risks'), 'lowPriorityWithResponses'),
		);
	}

	/** id => array(short text, explanation), in English (translations: locales/<locale>.php). */
	static function texts()
	{
		return array(
			1 => array('The project has no charter.',
				'No project charter is linked to this project. The charter states why the project exists, its objectives, high-level budget, milestones and success criteria, and it is what authorizes the project manager to use the organization\'s resources. Write one in the Initiating module and link it to the project.'),
			2 => array('The project charter is not authorized yet.',
				'The project has a charter, but it has not been authorized. Until the sponsor authorizes it, the project is not formally started and the project manager has no authority to commit resources. Complete the charter and have it approved and authorized.'),
			3 => array('The project ends after the date in its charter.',
				'The project\'s end date is later than the end date of its authorized charter. Either the schedule no longer meets what was agreed with the sponsor, or the charter is out of date: agree a new date and record the change, rather than letting the two drift apart.'),
			4 => array('The WBS has only a few items.',
				'The work breakdown structure has three items or fewer. A WBS this small rarely covers all of the deliverables and the project management work. Break the main deliverables down until each work package can be estimated, assigned and checked on its own.'),
			5 => array('The WBS has many items: check how it is broken down.',
				'The work breakdown structure has more than ten items. That is fine for a large project, but check that every item is a deliverable (a noun, not an action) and that work packages are not broken down into activities: activities belong in the schedule, under their work package.'),
			6 => array('Some work packages have no size estimate.',
				'Activities are planned, but some work packages have no size. Estimate the size of each work package (pages, screens, components, ...) before the effort of its activities: the size is what the effort and duration estimates are checked against.'),
			35 => array('Some work package sizes are in hours or days.',
				'Some work packages have their size in hours or days. Size measures the product (pages, features, function points, ...); hours and days measure the effort and duration of the activities. Use a unit of the product for the size.'),
			8 => array('Two activities depend on each other.',
				'There is a circular dependency: two activities are each set as the predecessor of the other, so neither can start before the other finishes and the schedule cannot be worked out. Remove one of the two dependencies.'),
			9 => array('A work package has a single activity.',
				'At least one work package is done by a single activity, so the activity and the work package are the same thing. Either list the activities needed to produce the work package, or check whether the work package is too small to be one.'),
			11 => array('Some activities last more than two weeks.',
				'At least one activity has an estimated duration of more than 14 days. Long activities are hard to monitor, because progress only shows when they finish. Split them into shorter activities, each with a result that can be checked.'),
			38 => array('Some activities take 8 hours of effort or less.',
				'At least one activity is estimated at one working day or less. Many very small activities make the schedule long and hard to keep up to date: consider merging them with related activities of the same work package.'),
			39 => array('Some activities take 80 hours of effort or more.',
				'At least one activity is estimated at 80 hours (two working weeks) or more. Estimates this large are usually less accurate and hard to track: break the activity down into smaller ones.'),
			14 => array('There are risks but no contingency reserve.',
				'The project has risks, but its budget has no contingency reserve. Set aside a reserve for the risks that you accept or that remain after their responses, based on their probability and impact (Budget tab of the project).'),
			16 => array('There is a contingency reserve but no management reserve.',
				'The budget has a contingency reserve for the known risks, but its management reserve is zero. The management reserve covers unforeseen work (the unknown risks); it is usually a percentage of the budget agreed with the sponsor.'),
			15 => array('Some people have no hourly cost.',
				'Some users of the project\'s company have no hourly cost, so the work they do is not counted in the project\'s costs. Enter their cost on the Cost tab of each user.'),
			44 => array('The work done costs more than planned (CPI below 0.9).',
				'Up to today the actual cost of the hours logged is higher than the earned value of the work done: each unit spent has produced less than 0.9 of planned work. See the trend and the activities behind it on the Cost tab (monitoring and control), then review the estimates of the remaining work or the budget.'),
			45 => array('The project is behind schedule (SPI below 0.9).',
				'Up to today the work done is worth less than 90 % of the work planned for this date (earned value against planned value). Find the late activities on the Schedule tab (monitoring and control) and update the plan, the scope or the people allocated.'),
			18 => array('Nobody is allocated to the activities.',
				'The project has activities, but no one is allocated to any of them. Without allocations there are no human resource costs and no workload to check: allocate people to the roles estimated for each activity.'),
			20 => array('Someone is allocated to activities that overlap.',
				'At least one person is allocated to two activities that take place at the same time. Unless both are part-time work, that person is over-allocated: move one of the activities, or allocate someone else.'),
			26 => array('An eliminated risk has a contingency plan.',
				'A risk with the Eliminate strategy has a contingency plan. Eliminating a risk means changing the plan so that the risk can no longer happen, so there is nothing to react to. If the risk can still happen, its strategy is Mitigate or Accept.'),
			27 => array('An accepted risk has prevention actions.',
				'A risk with the Accept strategy has prevention actions. Accepting a risk means not acting unless it happens (at most with a contingency plan). If you plan to act in advance to make it less likely or less serious, its strategy is Mitigate.'),
			43 => array('Low-priority risks have response plans.',
				'Some low-priority risks have prevention actions or a contingency plan. Responses take effort; low-priority risks are usually accepted and monitored, so the effort is better spent on the high-priority ones.'),
		);
	}

	/** The ids of the rules that fire for the project, for the modules that are active. */
	static function evaluate($project_id)
	{
		global $AppUI;
		$project_id = (int)$project_id;
		$active = $AppUI->getActiveModules();
		$fired = array();
		foreach (self::rules() as $id => $rule) {
			foreach ($rule[1] as $module) {
				if (!isset($active[$module])) {
					continue 2;
				}
			}
			if (call_user_func(array('CProjectFeedback', $rule[2]), $project_id)) {
				$fired[] = $id;
			}
		}
		return $fired;
	}

	/** feedback_id => true for the tips the user has read on this project. */
	static function readIds($user_id, $project_id)
	{
		$q = new DBQuery();
		$q->addTable('feedback_read');
		$q->addQuery('feedback_id');
		$q->addWhere('user_id = ' . (int)$user_id . ' AND project_id = ' . (int)$project_id);
		$ids = $q->loadColumn();
		return $ids ? array_fill_keys($ids, true) : array();
	}

	static function setRead($user_id, $project_id, $feedback_id, $read)
	{
		$q = new DBQuery();
		$q->setDelete('feedback_read');
		$q->addWhere('user_id = ' . (int)$user_id . ' AND project_id = ' . (int)$project_id
			. ' AND feedback_id = ' . (int)$feedback_id);
		$q->exec();
		$q->clear();
		if ($read) {
			$q->addTable('feedback_read');
			$q->addInsert('user_id', (int)$user_id);
			$q->addInsert('project_id', (int)$project_id);
			$q->addInsert('feedback_id', (int)$feedback_id);
			$q->addInsert('read_on', date('Y-m-d H:i:s'));
			$q->exec();
		}
	}

	/** The translations for the user's locale, merged in without replacing loaded strings. */
	static function loadTranslations()
	{
		global $AppUI;
		$file = DP_BASE_DIR . '/modules/dotproject_plus/feedback/locales/'
			. $AppUI->checkFileName($AppUI->user_locale) . '.php';
		if (is_file($file)) {
			$strings = include $file;
			if (is_array($strings)) {
				$GLOBALS['translate'] = (isset($GLOBALS['translate']) ? $GLOBALS['translate'] : array()) + $strings;
			}
		}
	}

	// The checks. Each returns true when the tip applies.

	private static function count($q)
	{
		$rows = $q->loadList();
		return is_array($rows) ? count($rows) : 0;
	}

	private static function activityCount($project_id)
	{
		$q = new DBQuery();
		$q->addTable('tasks');
		$q->addQuery('COUNT(*)');
		$q->addWhere('task_project = ' . (int)$project_id);
		return (int)$q->loadResult();
	}

	private static function wbsCount($project_id)
	{
		$q = new DBQuery();
		$q->addTable('project_eap_items');
		$q->addQuery('COUNT(*)');
		$q->addWhere('project_id = ' . (int)$project_id);
		return (int)$q->loadResult();
	}

	static function wbsSmall($project_id)
	{
		$n = self::wbsCount($project_id);
		return $n > 0 && $n <= 3;
	}

	static function wbsLarge($project_id)
	{
		return self::wbsCount($project_id) > 10;
	}

	static function wbsUnsized($project_id)
	{
		$q = new DBQuery();
		$q->addTable('project_eap_items', 'w');
		$q->addQuery('w.id');
		$q->addJoin('eap_item_estimations', 'e', 'e.eap_item_id = w.id');
		$q->addWhere('w.project_id = ' . (int)$project_id . ' AND w.is_leaf = 1 AND (e.size IS NULL OR e.size = 0)');
		return self::count($q) > 0 && self::activityCount($project_id) > 0;
	}

	static function wbsSizeInTime($project_id)
	{
		$q = new DBQuery();
		$q->addTable('project_eap_items', 'w');
		$q->addQuery('w.id');
		$q->addJoin('eap_item_estimations', 'e', 'e.eap_item_id = w.id', 'inner');
		$q->addWhere('w.project_id = ' . (int)$project_id
			. " AND UPPER(TRIM(e.size_unit)) IN ('HOUR', 'HOURS', 'DAY', 'DAYS', 'HORA', 'HORAS', 'DIA', 'DIAS')");
		return self::count($q) > 0;
	}

	static function circularDependencies($project_id)
	{
		$q = new DBQuery();
		$q->addTable('task_dependencies', 'd1');
		$q->addQuery('d1.dependencies_task_id');
		$q->addJoin('task_dependencies', 'd2', 'd2.dependencies_task_id = d1.dependencies_req_task_id'
			. ' AND d2.dependencies_req_task_id = d1.dependencies_task_id', 'inner');
		$q->addJoin('tasks', 't', 't.task_id = d1.dependencies_task_id', 'inner');
		$q->addWhere('t.task_project = ' . (int)$project_id);
		return self::count($q) > 0;
	}

	static function workPackageWithOneActivity($project_id)
	{
		$q = new DBQuery();
		$q->addTable('project_eap_items', 'w');
		$q->addQuery('w.id, COUNT(tw.task_id) AS activities');
		$q->addJoin('tasks_workpackages', 'tw', 'tw.eap_item_id = w.id', 'inner');
		$q->addWhere('w.project_id = ' . (int)$project_id);
		$q->addGroup('w.id');
		foreach ($q->loadList() as $row) {
			if ($row['activities'] == 1) {
				return true;
			}
		}
		return false;
	}

	/** Activities' estimations matching $where (effort_unit 0 = hours, 1 = minutes). */
	private static function estimations($project_id, $where)
	{
		$q = new DBQuery();
		$q->addTable('project_tasks_estimations', 'te');
		$q->addQuery('te.task_id');
		$q->addJoin('tasks', 't', 't.task_id = te.task_id', 'inner');
		$q->addWhere('t.task_project = ' . (int)$project_id . ' AND ' . $where);
		return self::count($q) > 0;
	}

	static function longActivities($project_id)
	{
		return self::estimations($project_id, 'te.duration > 14');
	}

	static function smallEfforts($project_id)
	{
		return self::estimations($project_id, 'te.effort > 0 AND ((te.effort_unit = 0 AND te.effort <= 8) OR (te.effort_unit = 1 AND te.effort <= 480))');
	}

	static function largeEfforts($project_id)
	{
		return self::estimations($project_id, '((te.effort_unit = 0 AND te.effort >= 80) OR (te.effort_unit = 1 AND te.effort >= 4800))');
	}

	private static function riskCount($project_id, $where = '1 = 1')
	{
		$q = new DBQuery();
		$q->addTable('risks');
		$q->addQuery('risk_id');
		// risk_active 0 is "yes" (sysval RiskActive).
		$q->addWhere('risk_project = ' . (int)$project_id . ' AND risk_active = 0 AND ' . $where);
		return self::count($q);
	}

	private static function contingencyReserveCount($project_id)
	{
		$q = new DBQuery();
		$q->addTable('budget_reserve');
		$q->addQuery('budget_reserve_id');
		$q->addWhere('budget_reserve_project_id = ' . (int)$project_id);
		return self::count($q);
	}

	static function risksWithoutContingencyReserve($project_id)
	{
		return self::riskCount($project_id) > 0 && self::contingencyReserveCount($project_id) == 0;
	}

	static function noManagementReserve($project_id)
	{
		if (self::contingencyReserveCount($project_id) == 0) {
			return false;
		}
		$q = new DBQuery();
		$q->addTable('costs_budget');
		$q->addQuery('budget_id');
		$q->addWhere('budget_project_id = ' . (int)$project_id . ' AND budget_reserve_management > 0');
		return self::count($q) == 0;
	}

	static function usersWithoutHourlyCost($project_id)
	{
		$q = new DBQuery();
		$q->addTable('users', 'u');
		$q->addQuery('u.user_id');
		$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact', 'inner');
		$q->addJoin('projects', 'p', 'p.project_company = c.contact_company', 'inner');
		$q->addJoin('monitoring_user_cost', 'uc', 'uc.user_id = u.user_id');
		$q->addWhere('p.project_id = ' . (int)$project_id . ' AND uc.cost_id IS NULL');
		return self::count($q) > 0;
	}

	/** Below this cost or schedule performance index the project gets a tip (PMBOK: < 1 is worse than plan). */
	const EV_THRESHOLD = 0.9;

	/**
	 * Earned value figures of the project up to today, from monitoringandcontrol (no baseline):
	 * array(planned value, earned value, actual cost). Kept for the request (two rules use it).
	 */
	static function earnedValue($project_id)
	{
		static $cache = array();
		if (!isset($cache[$project_id])) {
			require_once DP_BASE_DIR . '/modules/monitoringandcontrol/control/controller_earn_value.class.php';
			$ev = new ControllerEarnValue();
			$today = date('d/m/Y');
			$cache[$project_id] = array(
				(float)$ev->calculaValorPlanejado($project_id, $today),
				(float)$ev->calculaValorAgregado($project_id, $today),
				(float)$ev->calculaValorReal($project_id, $today),
			);
		}
		return $cache[$project_id];
	}

	/** CPI = EV / AC below the threshold, once some cost has been logged. */
	static function costOverrun($project_id)
	{
		list(, $ev, $ac) = self::earnedValue($project_id);
		return $ac > 0 && $ev / $ac < self::EV_THRESHOLD;
	}

	/** SPI = EV / PV below the threshold, once some work was planned to be done by today. */
	static function behindSchedule($project_id)
	{
		list($pv, $ev) = self::earnedValue($project_id);
		return $pv > 0 && $ev / $pv < self::EV_THRESHOLD;
	}

	static function nobodyAllocated($project_id)
	{
		if (self::activityCount($project_id) == 0) {
			return false;
		}
		$q = new DBQuery();
		$q->addTable('human_resource_allocation', 'a');
		$q->addQuery('a.human_resource_allocation_id');
		$q->addJoin('project_tasks_estimated_roles', 'r', 'r.id = a.project_tasks_estimated_roles_id', 'inner');
		$q->addJoin('tasks', 't', 't.task_id = r.task_id', 'inner');
		$q->addWhere('t.task_project = ' . (int)$project_id);
		$q->setLimit(1);
		return self::count($q) == 0;
	}

	static function overlappingAllocations($project_id)
	{
		$project_id = (int)$project_id;
		$q = new DBQuery();
		$q->addTable('human_resource_allocation', 'a1');
		$q->addQuery('a1.human_resource_id');
		$q->addJoin('project_tasks_estimated_roles', 'r1', 'r1.id = a1.project_tasks_estimated_roles_id', 'inner');
		$q->addJoin('tasks', 't1', 't1.task_id = r1.task_id', 'inner');
		$q->addJoin('human_resource_allocation', 'a2', 'a2.human_resource_id = a1.human_resource_id', 'inner');
		$q->addJoin('project_tasks_estimated_roles', 'r2', 'r2.id = a2.project_tasks_estimated_roles_id', 'inner');
		$q->addJoin('tasks', 't2', 't2.task_id = r2.task_id', 'inner');
		// Parent tasks span their children and milestones take no time: only leaf activities.
		$q->addWhere("t1.task_project = $project_id AND t2.task_project = $project_id AND t1.task_id < t2.task_id"
			. ' AND t1.task_dynamic <> 1 AND t2.task_dynamic <> 1 AND t1.task_milestone = 0 AND t2.task_milestone = 0'
			. ' AND t1.task_start_date < t2.task_end_date AND t2.task_start_date < t1.task_end_date');
		$q->setLimit(1);
		return self::count($q) > 0;
	}

	/** The project's charters (Initiating module). */
	private static function charters($project_id, $where = '1 = 1')
	{
		$q = new DBQuery();
		$q->addTable('initiating');
		$q->addQuery('initiating_id, initiating_end_date');
		$q->addWhere('initiating_project = ' . (int)$project_id . ' AND ' . $where);
		$rows = $q->loadList();
		return $rows ? $rows : array();
	}

	static function noCharter($project_id)
	{
		return count(self::charters($project_id)) == 0;
	}

	static function charterNotAuthorized($project_id)
	{
		return count(self::charters($project_id)) > 0
			&& count(self::charters($project_id, 'initiating_authorized = 1')) == 0;
	}

	static function endsAfterCharter($project_id)
	{
		$q = new DBQuery();
		$q->addTable('projects');
		$q->addQuery('project_end_date');
		$q->addWhere('project_id = ' . (int)$project_id);
		$end = $q->loadResult();
		if (!$end || substr($end, 0, 10) == '0000-00-00') {
			return false;
		}
		$latest = '';
		foreach (self::charters($project_id, 'initiating_authorized = 1') as $row) {
			$date = substr((string)$row['initiating_end_date'], 0, 10);
			if ($date && $date != '0000-00-00' && $date > $latest) {
				$latest = $date;
			}
		}
		return $latest !== '' && substr($end, 0, 10) > $latest;
	}

	// risk_strategy: 0 accept, 1 eliminate, 2 mitigate, 3 transfer; risk_priority: 0 low (sysvals).

	static function eliminatedWithContingency($project_id)
	{
		return self::riskCount($project_id, "risk_strategy = 1 AND risk_contingency_plan <> ''") > 0;
	}

	static function acceptedWithPrevention($project_id)
	{
		return self::riskCount($project_id, "risk_strategy = 0 AND risk_prevention_actions <> ''") > 0;
	}

	static function lowPriorityWithResponses($project_id)
	{
		return self::riskCount($project_id, "risk_priority = 0 AND (risk_prevention_actions <> '' OR risk_contingency_plan <> '')") > 0;
	}

}
