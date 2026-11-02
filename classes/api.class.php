<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once DP_BASE_DIR . '/classes/apitoken.class.php';

/**
 * The REST API behind api.php (docs/api.md).
 *
 * Every request is authenticated with a personal access token (CApiToken) and runs as
 * that token's user: module and record permissions are the ones the web pages check
 * (getPermission, CProject/CTask allowed SQL, CTask::canAccess). Read tokens can only
 * use GET. Lists take limit (1-200, default 50) and offset and return
 * {"data": [...], "meta": {"limit", "offset", "next_offset"}}; next_offset is null on the
 * last page. Errors are {"error": {"code", "message"}} with the matching HTTP status.
 */
class CApi
{
	const MAX_LIMIT = 200;
	const BODY_LIMIT = 65536;

	var $AppUI;
	var $user_id = 0;
	var $username = '';
	var $scope = 'read';

	function __construct(&$AppUI)
	{
		$this->AppUI =& $AppUI;
	}

	/**
	 * The bearer token from the Authorization header. Apache with mod_php only shows the
	 * header through getallheaders(); with CGI/FPM the .htaccess rule copies it to
	 * HTTP_AUTHORIZATION (REDIRECT_HTTP_AUTHORIZATION after a rewrite).
	 */
	static function bearerToken($server, $headers = null)
	{
		$values = array($server['HTTP_AUTHORIZATION'] ?? '', $server['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
		if ($headers === null && function_exists('getallheaders')) {
			$headers = getallheaders();
		}
		foreach ((array)$headers as $name => $value) {
			if (strcasecmp($name, 'Authorization') == 0) {
				$values[] = $value;
			}
		}
		foreach ($values as $value) {
			if (is_string($value) && preg_match('/^Bearer\s+(\S+)$/i', trim($value), $m)) {
				return $m[1];
			}
		}
		return '';
	}

	function run($method, $route, $token)
	{
		try {
			if (!dPgetConfig('api_enabled', false)) {
				$this->fail(404, 'api_disabled', 'The API is turned off.');
			}
			$this->authenticate($token);
			$this->dispatch(strtoupper($method), trim($route, '/'));
		} catch (CApiError $e) {
			http_response_code($e->status);
			if ($e->status == 401) {
				header('WWW-Authenticate: Bearer realm="dotPulse"');
			}
			if ($e->allow) {
				header('Allow: ' . $e->allow);
			}
			echo $this->json(array('error' => array('code' => $e->code_name, 'message' => $e->getMessage())));
		} catch (Throwable $e) {
			error_log('dotPulse API: ' . get_class($e) . ': ' . $e->getMessage() . ' in '
				. $e->getFile() . ':' . $e->getLine());
			http_response_code(500);
			echo $this->json(array('error' => array('code' => 'server_error', 'message' => 'Internal error.')));
		}
	}

	function authenticate($token)
	{
		require_once DP_BASE_DIR . '/classes/ratelimiter.class.php';
		$limiter = new RateLimiter('api', 20, 600);
		if (!$limiter->isAllowed()) {
			$this->fail(429, 'too_many_attempts', 'Too many failed attempts. Try again later.');
		}
		$row = $token === '' ? false : CApiToken::find($token);
		if (!$row) {
			if ($token !== '') {
				$limiter->recordAttempt();
				if (!$limiter->isAllowed()) {
					dPaudit('api.blocked', 'this address must wait before using the API again', '', 0, 0, '');
				}
			}
			$this->fail(401, 'unauthorized', 'A valid API token is required.');
		}
		$acl =& $this->AppUI->acl();
		if (!$acl->checkLogin($row['token_user'])) {
			$this->fail(401, 'unauthorized', 'This user may not log in.');
		}
		$q = new DBQuery;
		$q->addTable('users');
		$q->addQuery('user_id, user_username, contact_first_name as user_first_name, '
			. 'contact_last_name as user_last_name, contact_company as user_company, '
			. 'contact_department as user_department, contact_email as user_email, user_type');
		$q->addJoin('contacts', 'con', 'contact_id = user_contact');
		$q->addWhere('user_id = ' . (int)$row['token_user']);
		$user = $q->loadHash();
		if (!$user) {
			$this->fail(401, 'unauthorized', 'A valid API token is required.');
		}
		foreach ($user as $k => $v) {
			if ($k != 'user_username') {
				$this->AppUI->$k = $v;
			}
		}
		$this->AppUI->loadPrefs($this->AppUI->user_id);
		$this->AppUI->setUserLocale();
		$this->user_id = (int)$this->AppUI->user_id;
		$this->username = $user['user_username'];
		$this->scope = $row['token_scope'];
	}

	function dispatch($method, $route)
	{
		$routes = array(
			'#^v1/me$#' => array('GET' => 'getMe'),
			'#^v1/projects$#' => array('GET' => 'listProjects'),
			'#^v1/projects/(\d+)$#' => array('GET' => 'getProject'),
			'#^v1/projects/(\d+)/tasks$#' => array('GET' => 'listProjectTasks'),
			'#^v1/tasks$#' => array('GET' => 'listTasks'),
			'#^v1/tasks/(\d+)$#' => array('GET' => 'getTask', 'PATCH' => 'updateTask'),
			'#^v1/tasks/(\d+)/logs$#' => array('GET' => 'listTaskLogs', 'POST' => 'addTaskLog'),
		);
		foreach ($routes as $pattern => $methods) {
			if (preg_match($pattern, $route, $m)) {
				if (!isset($methods[$method])) {
					$this->fail(405, 'method_not_allowed', 'Method not allowed.', implode(', ', array_keys($methods)));
				}
				if ($method != 'GET' && $this->scope != 'write') {
					$this->fail(403, 'read_only_token', 'This token can only read.');
				}
				$handler = $methods[$method];
				$result = call_user_func_array(array($this, $handler), array_map('intval', array_slice($m, 1)));
				if ($method == 'POST') {
					http_response_code(201);
				}
				echo $this->json($result);
				return;
			}
		}
		$this->fail(404, 'not_found', 'No such endpoint.');
	}

	// ---- endpoints

	function getMe()
	{
		return array('data' => array(
			'id' => $this->user_id,
			'username' => $this->username,
			'first_name' => $this->AppUI->user_first_name,
			'last_name' => $this->AppUI->user_last_name,
			'email' => $this->AppUI->user_email,
			'company_id' => (int)$this->AppUI->user_company,
			'locale' => $this->AppUI->user_locale,
			'token_scope' => $this->scope,
		));
	}

	function listProjects()
	{
		list($limit, $offset) = $this->paging();
		$q = $this->projectQuery();
		$status = $this->intParam('status');
		if ($status !== null) {
			$q->addWhere('p.project_status = ' . $status);
		}
		$company = $this->intParam('company');
		if ($company !== null) {
			$q->addWhere('p.project_company = ' . $company);
		}
		$q->addOrder('p.project_name, p.project_id');
		$q->setLimit($limit + 1, $offset);
		$rows = $this->withProgress($q->loadList());
		return $this->page(array_map(array($this, 'project'), $rows), $limit, $offset);
	}

	function getProject($id)
	{
		if (!getPermission('projects', 'view', $id)) {
			$this->fail(404, 'not_found', 'No such project.');
		}
		$q = $this->projectQuery();
		$q->addWhere('p.project_id = ' . (int)$id);
		$row = $q->loadHash();
		if (!$row) {
			$this->fail(404, 'not_found', 'No such project.');
		}
		list($row) = $this->withProgress(array($row));
		return array('data' => $this->project($row));
	}

	function listProjectTasks($id)
	{
		$this->getProject($id);
		return $this->taskList(array('t.task_project = ' . (int)$id));
	}

	function listTasks()
	{
		$where = array();
		$joins = array();
		$project = $this->intParam('project');
		if ($project !== null) {
			$where[] = 't.task_project = ' . $project;
		}
		$assigned = dPgetParam($_GET, 'assigned');
		if ($assigned !== null) {
			$uid = $assigned === 'me' ? $this->user_id : (ctype_digit((string)$assigned) ? (int)$assigned : -1);
			if ($uid < 0) {
				$this->fail(400, 'invalid_parameter', 'assigned must be "me" or a user id.');
			}
			$joins[] = array('user_tasks', 'ut', 'ut.task_id = t.task_id AND ut.user_id = ' . $uid);
		}
		$open = dPgetParam($_GET, 'open');
		if ($open !== null) {
			$where[] = $open ? 't.task_percent_complete < 100' : 't.task_percent_complete >= 100';
		}
		return $this->taskList($where, $joins);
	}

	function getTask($id)
	{
		$task = $this->loadTask($id);
		$data = $this->task($this->taskRow($id));
		$data['description'] = $task->task_description;
		return array('data' => $data);
	}

	function updateTask($id)
	{
		$task = $this->loadTask($id);
		if (!getPermission('tasks', 'edit', $id)) {
			$this->fail(403, 'forbidden', 'You may not edit this task.');
		}
		$body = $this->body(array('percent_complete', 'priority'));
		if (!$body) {
			$this->fail(400, 'invalid_body', 'Send percent_complete and/or priority.');
		}
		if (isset($body['percent_complete'])) {
			$pc = $this->intValue($body['percent_complete'], 0, 100, 'percent_complete');
			$task->task_percent_complete = $pc;
			if ($pc >= 100 && (!$task->task_end_date || $task->task_end_date == '0000-00-00 00:00:00')) {
				$task->task_end_date = date('Y-m-d H:i:s');
			}
		}
		if (isset($body['priority'])) {
			$task->task_priority = $this->intValue($body['priority'], -1, 1, 'priority');
		}
		$task->htmlDecode();
		if (($msg = $task->store())) {
			$this->fail(422, 'not_saved', is_array($msg) ? implode(' ', $msg) : (string)$msg);
		}
		return $this->getTask($id);
	}

	function listTaskLogs($id)
	{
		$this->loadTask($id);
		if (!getPermission('task_log', 'view', $id)) {
			$this->fail(403, 'forbidden', 'You may not read the logs of this task.');
		}
		list($limit, $offset) = $this->paging();
		$q = new DBQuery;
		$q->addTable('task_log');
		$q->addQuery('task_log_id, task_log_task, task_log_creator, task_log_name, task_log_description, '
			. 'task_log_hours, task_log_date, task_log_costcode, task_log_problem');
		$q->addWhere('task_log_task = ' . (int)$id);
		$q->addOrder('task_log_date DESC, task_log_id DESC');
		$q->setLimit($limit + 1, $offset);
		return $this->page(array_map(array($this, 'taskLog'), $q->loadList()), $limit, $offset);
	}

	function addTaskLog($id)
	{
		$task = $this->loadTask($id);
		if (!getPermission('tasks', 'edit', $id) || !getPermission('task_log', 'add', $id)) {
			$this->fail(403, 'forbidden', 'You may not log work on this task.');
		}
		$body = $this->body(array('hours', 'date', 'summary', 'description', 'costcode', 'problem', 'percent_complete'));
		if (!isset($body['hours']) || !is_numeric($body['hours']) || $body['hours'] < 0 || $body['hours'] > 999) {
			$this->fail(400, 'invalid_parameter', 'hours must be a number between 0 and 999.');
		}
		$date = date('Y-m-d H:i:s');
		if (isset($body['date'])) {
			$ts = is_string($body['date']) && preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?)?$/', $body['date'])
				? strtotime($body['date']) : false;
			if ($ts === false) {
				$this->fail(400, 'invalid_parameter', 'date must be YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS.');
			}
			$date = date('Y-m-d H:i:s', $ts);
		}
		$log = new CTaskLog;
		$log->task_log_task = (int)$id;
		$log->task_log_creator = $this->user_id;
		$log->task_log_hours = round((float)$body['hours'], 3);
		$log->task_log_date = $date;
		$log->task_log_name = mb_substr($this->text($body, 'summary'), 0, 255);
		$log->task_log_description = $this->text($body, 'description');
		$log->task_log_costcode = mb_substr($this->text($body, 'costcode'), 0, 8);
		$log->task_log_problem = empty($body['problem']) ? 0 : 1;
		if (($msg = $log->store())) {
			$this->fail(422, 'not_saved', is_array($msg) ? implode(' ', $msg) : (string)$msg);
		}
		if (isset($body['percent_complete'])) {
			$task->task_percent_complete = $this->intValue($body['percent_complete'], 0, 100, 'percent_complete');
			if ($task->task_percent_complete >= 100
				&& (!$task->task_end_date || $task->task_end_date == '0000-00-00 00:00:00')) {
				$task->task_end_date = $date;
			}
			$task->htmlDecode();
			$task->store();
		}
		$q = new DBQuery;
		$q->addTable('task_log');
		$q->addQuery('*');
		$q->addWhere('task_log_id = ' . (int)$log->task_log_id);
		return array('data' => $this->taskLog($q->loadHash()));
	}

	// ---- queries and permissions

	function projectQuery()
	{
		$q = new DBQuery;
		$q->addTable('projects', 'p');
		$q->addJoin('companies', 'c', 'c.company_id = p.project_company');
		$q->addQuery('p.project_id, p.project_name, p.project_short_name, p.project_company, c.company_name, '
			. 'p.project_status, p.project_priority, p.project_owner, p.project_start_date, p.project_end_date, '
			. 'p.project_percent_complete, p.project_color_identifier, p.project_description');
		$proj = new CProject;
		$proj->setAllowedSQL($this->user_id, $q, null, 'p');
		return $q;
	}

	/** Sets project_percent_complete from the tasks (the stored column is not kept up to date). */
	function withProgress($rows)
	{
		$pc = CProject::percentComplete(array_column($rows, 'project_id'));
		foreach ($rows as $i => $row) {
			$rows[$i]['project_percent_complete'] = $pc[(int)$row['project_id']] ?? 0;
		}
		return $rows;
	}

	/** Tasks of allowed projects that the user may view, in pages (see the class comment). */
	function taskList($where, $joins = array())
	{
		list($limit, $offset) = $this->paging();
		$q = $this->taskQuery();
		foreach ($joins as $j) {
			$q->innerJoin($j[0], $j[1], $j[2]);
		}
		foreach ($where as $w) {
			$q->addWhere($w);
		}
		$q->addOrder('t.task_project, t.task_start_date, t.task_id');
		// Rows the user may not access are skipped, so read in batches until the page is full.
		$data = array();
		$pos = $offset;
		$next = null;
		while (true) {
			$batch = clone $q;
			$batch->setLimit(self::MAX_LIMIT, $pos);
			$rows = $batch->loadList();
			foreach ($rows as $row) {
				$pos++;
				if (!$this->canAccessTask($row)) {
					continue;
				}
				if (count($data) == $limit) {
					$next = $pos - 1;
					break 2;
				}
				$data[] = $this->task($row);
			}
			if (count($rows) < self::MAX_LIMIT) {
				break;
			}
		}
		return array('data' => $data, 'meta' => array('limit' => $limit, 'offset' => $offset, 'next_offset' => $next));
	}

	function taskQuery()
	{
		$q = new DBQuery;
		$q->addTable('tasks', 't');
		$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
		$q->addQuery('t.task_id, t.task_name, t.task_project, t.task_parent, t.task_milestone, t.task_owner, '
			. 't.task_start_date, t.task_end_date, t.task_duration, t.task_duration_type, t.task_status, '
			. 't.task_priority, t.task_percent_complete, t.task_dynamic, t.task_access, t.task_hours_worked');
		$proj = new CProject;
		$proj->setAllowedSQL($this->user_id, $q, null, 'p');
		$task = new CTask;
		foreach ($task->getAllowedSQL($this->user_id, 't.task_id') as $w) {
			$q->addWhere($w);
		}
		return $q;
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

	function taskRow($id)
	{
		$q = $this->taskQuery();
		$q->addWhere('t.task_id = ' . (int)$id);
		return $q->loadHash();
	}

	/** The task when the user may view it (404 otherwise, so ids do not leak). */
	function loadTask($id)
	{
		$row = $this->taskRow($id);
		if (!$row || !$this->canAccessTask($row)) {
			$this->fail(404, 'not_found', 'No such task.');
		}
		$task = new CTask;
		$task->load((int)$id);
		return $task;
	}

	// ---- representations

	function project($r)
	{
		static $statuses = null;
		if ($statuses === null) {
			$statuses = dPgetSysVal('ProjectStatus');
		}
		return array(
			'id' => (int)$r['project_id'],
			'name' => $r['project_name'],
			'short_name' => $r['project_short_name'],
			'company' => array('id' => (int)$r['project_company'], 'name' => $r['company_name']),
			'status' => (int)$r['project_status'],
			'status_name' => $statuses[$r['project_status']] ?? null,
			'priority' => (int)$r['project_priority'],
			'owner_id' => (int)$r['project_owner'],
			'start_date' => $this->date($r['project_start_date']),
			'end_date' => $this->date($r['project_end_date']),
			'percent_complete' => (float)$r['project_percent_complete'],
			'color' => '#' . $r['project_color_identifier'],
			'description' => $r['project_description'],
		);
	}

	function task($r)
	{
		$q = new DBQuery;
		$q->addTable('user_tasks');
		$q->addQuery('user_id, perc_assignment');
		$q->addWhere('task_id = ' . (int)$r['task_id']);
		$assignees = array();
		foreach ($q->loadList() as $a) {
			$assignees[] = array('user_id' => (int)$a['user_id'], 'percent' => (int)$a['perc_assignment']);
		}
		return array(
			'id' => (int)$r['task_id'],
			'name' => $r['task_name'],
			'project_id' => (int)$r['task_project'],
			'parent_id' => (int)$r['task_parent'] == (int)$r['task_id'] ? null : (int)$r['task_parent'],
			'milestone' => (bool)$r['task_milestone'],
			'dynamic' => (bool)$r['task_dynamic'],
			'owner_id' => (int)$r['task_owner'],
			'start_date' => $this->date($r['task_start_date']),
			'end_date' => $this->date($r['task_end_date']),
			'duration' => (float)$r['task_duration'],
			'duration_unit' => (int)$r['task_duration_type'] == 24 ? 'days' : 'hours',
			'status' => (int)$r['task_status'],
			'priority' => (int)$r['task_priority'],
			'percent_complete' => (int)$r['task_percent_complete'],
			'hours_worked' => (float)$r['task_hours_worked'],
			'assignees' => $assignees,
		);
	}

	function taskLog($r)
	{
		return array(
			'id' => (int)$r['task_log_id'],
			'task_id' => (int)$r['task_log_task'],
			'creator_id' => (int)$r['task_log_creator'],
			'date' => $this->date($r['task_log_date']),
			'hours' => (float)$r['task_log_hours'],
			'summary' => $r['task_log_name'],
			'description' => $r['task_log_description'],
			'costcode' => $r['task_log_costcode'],
			'problem' => (bool)$r['task_log_problem'],
		);
	}

	/** Server local time as YYYY-MM-DDTHH:MM:SS; null for empty dates. */
	function date($value)
	{
		if (!$value || strpos($value, '0000-00-00') === 0) {
			return null;
		}
		return str_replace(' ', 'T', $value);
	}

	// ---- input

	function paging()
	{
		$limit = $this->intParam('limit');
		$offset = $this->intParam('offset');
		$limit = $limit === null ? 50 : $limit;
		if ($limit < 1 || $limit > self::MAX_LIMIT) {
			$this->fail(400, 'invalid_parameter', 'limit must be between 1 and ' . self::MAX_LIMIT . '.');
		}
		return array($limit, $offset === null ? 0 : $offset);
	}

	/** A list page fetched with limit + 1 rows: the extra row only tells there is more. */
	function page($items, $limit, $offset)
	{
		$more = count($items) > $limit;
		return array(
			'data' => array_slice($items, 0, $limit),
			'meta' => array('limit' => $limit, 'offset' => $offset, 'next_offset' => $more ? $offset + $limit : null),
		);
	}

	/** A non-negative integer query parameter, null when absent. */
	function intParam($name)
	{
		$value = dPgetParam($_GET, $name);
		if ($value === null || $value === '') {
			return null;
		}
		if (!is_string($value) || !ctype_digit($value) || strlen($value) > 9) {
			$this->fail(400, 'invalid_parameter', $name . ' must be a non-negative integer.');
		}
		return (int)$value;
	}

	function intValue($value, $min, $max, $name)
	{
		if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d+$/', $value))) {
			$this->fail(400, 'invalid_parameter', $name . ' must be an integer.');
		}
		$value = (int)$value;
		if ($value < $min || $value > $max) {
			$this->fail(400, 'invalid_parameter', $name . " must be between $min and $max.");
		}
		return $value;
	}

	function text($body, $key)
	{
		if (!isset($body[$key])) {
			return '';
		}
		if (!is_string($body[$key])) {
			$this->fail(400, 'invalid_parameter', $key . ' must be a string.');
		}
		return $body[$key];
	}

	/** The JSON request body, keeping only the allowed keys. */
	function body($allowed)
	{
		$type = $_SERVER['CONTENT_TYPE'] ?? '';
		if (stripos($type, 'application/json') !== 0) {
			$this->fail(415, 'unsupported_media_type', 'Send the body as application/json.');
		}
		$raw = $this->rawBody();
		if (strlen($raw) > self::BODY_LIMIT) {
			$this->fail(413, 'body_too_large', 'The body is too large.');
		}
		$data = json_decode($raw, true);
		if (!is_array($data)) {
			$this->fail(400, 'invalid_body', 'The body must be a JSON object.');
		}
		return array_intersect_key($data, array_flip($allowed));
	}

	function rawBody()
	{
		return (string)file_get_contents('php://input', false, null, 0, self::BODY_LIMIT + 1);
	}

	function json($value)
	{
		return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	}

	function fail($status, $code, $message, $allow = '')
	{
		throw new CApiError($status, $code, $message, $allow);
	}
}

class CApiError extends Exception
{
	var $status;
	var $code_name;
	var $allow;

	function __construct($status, $code, $message, $allow = '')
	{
		parent::__construct($message);
		$this->status = $status;
		$this->code_name = $code;
		$this->allow = $allow;
	}
}
