<?php
// $Id: authenticator.class.php 6070 2010-11-23 04:22:12Z ajdonnison $
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

	/*
	 *	Authenticator Class
	 *
	 */


	function &getAuth($auth_mode)
	{
		switch($auth_mode)
		{
			case "ldap":
				$auth = new LDAPAuthenticator();
				return $auth;
				break;
			case 'ip':
				$auth = new IPAuthenticator();
				return $auth;
				break;
			default:
				// Try loading the authenticator class
				$auth_mode = preg_replace('/[^a-z0-9_-]/i', '', $auth_mode);
				@include_once(DP_BASE_DIR.'/classes/auth.'.$auth_mode.'.class.php');
				$classname = $auth_mode . 'Authenticator';
				if (class_exists($classname)) {
					$auth = new $classname();
				} else {
					$auth = new SQLAuthenticator();
				}
				return $auth;
				break;
		}
	}

	class SQLAuthenticator
	{
		var $user_id;
		var $username;

		function authenticate($username, $password)
		{
			GLOBAL $db, $AppUI;

			$this->username = $username;

			$q  = new DBQuery;
			$q->addTable('users');
			$q->addQuery('user_id, user_password');
			$q->addWhere("user_username = " . $q->quote($username));
			if (!$rs = $q->exec()) {
				$q->clear();
				return false;
			}
			if (!$row = $q->fetchRow()) {
				$q->clear();
				return false;
			}

			$this->user_id = $row["user_id"];
			$stored = $row["user_password"];
			$q->clear();

			if (password_verify($password, $stored)) {
				return true;
			}
			// Backward compat: accept legacy MD5 hash and transparently upgrade it,
			// unless the column is still too short to store the new hash.
			if (strlen($stored) === 32 && md5($password) === $stored) {
				if (!dPpasswordColumnFitsHash()) {
					return true;
				}
				$q->addTable('users');
				$q->addUpdate('user_password', dPhashPassword($password));
				$q->addWhere('user_id = ' . intval($this->user_id));
				$q->exec();
				$q->clear();
				return true;
			}
			return false;
		}

		function userId($username = NULL)
		{
			return $this->user_id;
		}
	}	

	class LDAPAuthenticator extends SQLAuthenticator
	{
		var $ldap_host;
		var $ldap_port;
		var $ldap_version;
		var $base_dn;
		var $ldap_search_user;
		var $ldap_search_pass;	
		var $filter;

		var $user_id;
		var $username;

		function __construct(){
			GLOBAL $dPconfig;

			$this->fallback = isset($dPconfig['ldap_allow_login']) ? $dPconfig['ldap_allow_login'] : false;

			$this->ldap_host = $dPconfig["ldap_host"];
			$this->ldap_port = $dPconfig["ldap_port"];
			$this->ldap_version = $dPconfig["ldap_version"];
			$this->base_dn = $dPconfig["ldap_base_dn"];
			$this->ldap_search_user = $dPconfig["ldap_search_user"];
			$this->ldap_search_pass = $dPconfig["ldap_search_pass"];
			$this->filter = $dPconfig["ldap_user_filter"];
		}

		function authenticate($username, $password)
		{
			GLOBAL $dPconfig;
			$this->username = $username;

			if (mb_strlen($password) == 0) return false; // LDAP will succeed binding with no password on AD (defaults to anon bind)
			if ($this->fallback == true)
			{
				if (parent::authenticate($username, $password)) return true;	
			}
			// Fallback SQL authentication fails, proceed with LDAP

			if (!$rs = @ldap_connect($this->ldap_host, $this->ldap_port))
			{
				return false;
			}
			@ldap_set_option($rs, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
			@ldap_set_option($rs, LDAP_OPT_REFERRALS, 0);

			//$ldap_bind_dn = "cn=".$this->ldap_search_user.",".$this->base_dn;
			$ldap_bind_dn = empty($this->ldap_search_user) ? NULL : $this->ldap_search_user;	
			$ldap_bind_pw = empty($this->ldap_search_pass) ? NULL : $this->ldap_search_pass;

			if (!$bindok = @ldap_bind($rs, $ldap_bind_dn, $ldap_bind_pw))
			{
				// Uncomment for LDAP debugging
				/*	
				$error_msg = ldap_error($rs);
				die("Couldnt Bind Using ".$ldap_bind_dn."@".$this->ldap_host.":".$this->ldap_port." Because:".$error_msg);
				*/
				return false;
			}
			else
			{
				$filter_r = html_entity_decode(str_replace("%USERNAME%", $username, $this->filter), ENT_COMPAT, 'UTF-8');
				$result = @ldap_search($rs, $this->base_dn, $filter_r);
				if (!$result) return false; // ldap search returned nothing or error
				
				$result_user = ldap_get_entries($rs, $result);
				if ($result_user["count"] == 0) return false; // No users match the filter

				$first_user = $result_user[0];
				$ldap_user_dn = $first_user["dn"];

				// Bind with the dn of the user that matched our filter (only one user should match sAMAccountName or uid etc..)

				if (!$bind_user = @ldap_bind($rs, $ldap_user_dn, $password))
				{
					/*
					$error_msg = ldap_error($rs);
					die("Couldnt Bind Using ".$ldap_user_dn."@".$this->ldap_host.":".$this->ldap_port." Because:".$error_msg);
					*/
					return false;
				}
				else
				{
					if ($this->userExists($username))
					{
						return true;
					}
					else
					{
						$this->createsqluser($username, $password, $first_user); 
					}
					return true;
				} 
			}
		}

		function userExists($username)
		{
			GLOBAL $db;
			$q  = new DBQuery;
			$result = false;
			$q->addTable('users');
			$q->addWhere("user_username = " . $q->quote($username));
			$rs = $q->exec();
			if ($rs->RecordCount() > 0) 
			  $result = true;
			$q->clear();
			return $result;
		}

		function userId($username = NULL)
		{
			GLOBAL $db;
			if ($username) {
				$q  = new DBQuery;
				$q->addTable('users');
				$q->addWhere("user_username = " . $q->quote($username));
				$rs = $q->exec();
				$row = $rs->FetchRow();
				$q->clear();
				return $row["user_id"];
			}
			return $this->user_id;
		}

		function createsqluser($username, $password, $ldap_attribs = Array())
		{
			GLOBAL $db, $AppUI;
			$hash_pass = dPhashPassword($password);

			require_once($AppUI->getModuleClass("contacts"));
	
			if (!count($ldap_attribs) == 0)
			{
				// Contact information based on the inetOrgPerson class schema
				$c = New CContact();
				$c->contact_first_name = $ldap_attribs["givenname"][0];
				$c->contact_last_name = $ldap_attribs["sn"][0];
				$c->contact_email = $ldap_attribs["mail"][0];
				$c->contact_phone = $ldap_attribs["telephonenumber"][0];
				$c->contact_mobile = $ldap_attribs["mobile"][0];
				$c->contact_city = $ldap_attribs["l"][0];
				$c->contact_country = $ldap_attribs["country"][0];
				$c->contact_state = $ldap_attribs["st"][0];
				$c->contact_zip = $ldap_attribs["postalcode"][0];
				$c->contact_job = $ldap_attribs["title"][0];

				db_insertObject('contacts', $c, 'contact_id');
			}
			$contact_id = ($c->contact_id == NULL) ? "NULL" : $c->contact_id;

			$q  = new DBQuery;
			$q->addTable('users');
			$q->addInsert('user_username',$username);
			$q->addInsert('user_password', $hash_pass);
			$q->addInsert('user_type', '1');
			$q->addInsert('user_contact', $c->contact_id);
			$q->exec();
			$user_id = $db->Insert_ID();
			$this->user_id = $user_id;
			$q->clear();

			$acl =& $AppUI->acl();
			$acl->insertUserRole($acl->get_group_id('anon'), $this->user_id);
		}

	}

	class IPAuthenticator extends SQLAuthenticator
	{
		function authenticate($username, $password)
		{
			$ret = parent::authenticate($username, $password);
			if ($ret == false) {
				return false;
			}

			$q  = new DBQuery;
			$q->addTable('user_ip_lock');
			$q->addQuery('user_id');
			$q->addWhere("user_id = {$this->user_id}");
			$q->addWhere("user_ip = '{$_SERVER['REMOTE_ADDR']}'");
			$row = $q->loadResult();
			if ($row) {
				return false;
			}

			return true;
		}
	}
