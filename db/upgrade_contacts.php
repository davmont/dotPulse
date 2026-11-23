<?php

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly. Instead, run the Installer in install/index.php.');
}

dPmsg('Converting users to contacts');
$q = New DBQuery;
$q->addTable( "users" );
$q->addQuery( "*" );
$users = db_loadList( $q->prepare(true) );
$flds = array(
	'contact_first_name',
	'contact_last_name',
	'contact_birthday',
	'contact_company',
	'contact_department',
	'contact_email',
	'contact_phone',
	'contact_phone2',
	'contact_mobile',
	'contact_address1',
	'contact_address2',
	'contact_city',
	'contact_state',
	'contact_zip',
	'contact_country',
	'contact_icq',
	'contact_icon',
	'contact_owner'
	);
foreach ($users as $user) {
        $vals = array(
                $user['user_first_name'], 
                $user['user_last_name'],
                $user['user_birthday'],
                (int)dPgetParam($user, 'user_company', 0),
                (int)dPgetParam($user, 'user_department', 0),
                $user['user_email'],
                $user['user_phone'],
                $user['user_home_phone'],
                $user['user_mobile'],
                $user['user_address1'],
                $user['user_address2'],
                $user['user_city'],
                $user['user_state'],
                $user['user_zip'],
                $user['user_country'],
                $user['user_icq'],
                $user['user_pic'],
                $user['user_owner'],
                );

	// One contact per user, linked to it. (The queries used to run without their
	// values, and LAST_INSERT_ID() was the preference row's, not the contact's.)
	$q = new DBQuery;
	$q->addTable('contacts');
	$q->addInsert($flds, $vals, true);
	if (!$q->exec()) {
		dPmsg(db_error());
		continue;
	}
	$contact_id = (int)db_insert_id();
	$q = new DBQuery;
	$q->addTable('user_preferences');
	$q->addInsert(array('pref_user', 'pref_name', 'pref_value'), array($user['user_id'], 'USERFORMAT', 'user'), true);
	if (!$q->exec()) {
		dPmsg(db_error());
	}
	$q = new DBQuery;
	$q->addTable('users');
	$q->addUpdate('user_contact', $contact_id);
	$q->addWhere('user_id = ' . (int)$user['user_id']);
	if (!$q->exec()) {
		dPmsg(db_error());
	}
}

?>
