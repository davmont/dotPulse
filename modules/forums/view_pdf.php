<?php  /* FORUMS $Id: view_pdf.php 4779 2007-02-21 14:53:28Z cyberhorse $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

$AppUI->savePlace();
$sort = dPvalidateOrder(mb_strtolower(dPgetParam($_REQUEST, 'sort', 'asc')), array('asc', 'desc'), 'asc');
$forum_id = dPgetIntParam($_REQUEST, 'forum_id', 0);
$message_id = dPgetIntParam($_REQUEST, 'message_id', 0);
$perms =& $AppUI->acl();

// The permission is on the forum; the messages are limited to that forum below.
if ( ! $perms->checkModuleItem('forums', 'view', $forum_id))
	$AppUI->redirect("m=public&a=access_denied");

$q  = new DBQuery;
$q->addTable('forums', 'f');
$q->leftJoin('projects', 'p', 'f.forum_project = p.project_id');
$q->addQuery('f.forum_name, p.project_name');
$q->addWhere('f.forum_id = ' . (int)$forum_id);
$forum = $q->loadHash();
if (!$forum) {
	$AppUI->redirect("m=public&a=access_denied");
}

$q  = new DBQuery;
$q->addTable('forums');
$q->addTable('forum_messages', 'forum_messages');
$q->addQuery('forum_messages.*,	contact_first_name, contact_last_name, contact_email, user_username,
		forum_moderated, visit_user');
$q->addJoin('forum_visits', 'v', "visit_user = {$AppUI->user_id} AND visit_forum = $forum_id AND visit_message = 				forum_messages.message_id");
$q->addJoin('users', 'u', 'message_author = u.user_id');
$q->addJoin('contacts', 'con', 'contact_id = user_contact');
$q->addWhere("forum_id = message_forum AND message_forum = $forum_id AND (message_id = $message_id OR message_parent = $message_id)");
if (dPgetConfig('forum_descendent_order') || dPgetParam($_REQUEST, 'sort', 0)) { 
	$q->addOrder("message_date $sort"); 
}

$messages = $q->loadList();

$x = false;

$date = new CDate();
$pdfdata = array();
$pdfhead = array('Date', 'User', 'Message');

$new_messages = array();
$topic = '';

foreach ($messages as $row) {
        // Find the parent message - the topic.
        if ($row['message_id'] == $message_id)
                $topic = $row['message_title'];
		
	$q  = new DBQuery;
	$q->addTable('forum_messages');
	$q->addTable('users', 'users');
	$q->addQuery('DISTINCT contact_email, contact_first_name, contact_last_name, user_username');
	$q->addJoin('contacts', 'con', 'contact_id = user_contact');
	$q->addWhere('users.user_id = ' . (int)$row["message_editor"]);
	$editor = $q->loadList();

	$date = intval( $row["message_date"] ) ? new CDate( $row["message_date"] ) : null;

	$pdfdata[] = array($row['message_date'],
		$row['contact_first_name'] . ' ' . $row['contact_last_name'],
		'<b>' . $row['message_title'] . '</b>
		' . $row['message_body']);
}

$font_dir = DP_BASE_DIR . '/lib/ezpdf/fonts';
$temp_dir = DP_BASE_DIR . '/files/temp';
$base_url  = DP_BASE_URL;
require_once DP_BASE_DIR . '/classes/dpdf.class.php';

$pdf = new DotPdf($paper='A4', $orientation='portrait');
$pdf->ezSetCmMargins(1, 2, 1.5, 1.5);
$pdf->selectFont($font_dir . '/Helvetica.afm');
$pdf->ezText('Project: ' . $forum['project_name']. '   Forum: '.$forum['forum_name'] );
$pdf->ezText('Topic: ' . $topic);
$pdf->ezText('');
                $options = array(
                        'showLines' => 1,
                        'showHeadings' => 1,
                        'fontSize' => 8,
                        'rowGap' => 2,
                        'colGap' => 5,
                        'xPos' => 50,
                        'xOrientation' => 'right',
                        'width'=>'500'
                );

$pdf->ezTable( $pdfdata, $pdfhead, NULL, $options );

$pdf->ezStream();
?>