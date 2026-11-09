<?php /* FORUMS $Id: do_post_aed.php 4779 2007-02-21 14:53:28Z cyberhorse $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

$del = dPgetIntParam($_POST, 'del', 0);
// Posting needs edit permission on the forum, as post_message.php checks. For an existing
// message, use the forum it is stored in rather than the one in the request.
$forum_id = (int)dPgetParam($_POST, 'message_forum', 0);
$message_id = (int)dPgetParam($_POST, 'message_id', 0);
$stored = null;
if ($message_id) {
	$stored = new CForumMessage();
	if (!$stored->load($message_id)) {
		$AppUI->setMsg('Message');
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	$forum_id = (int)$stored->message_forum;
}
$q = new DBQuery();
$q->addTable('forums');
$q->addQuery('forum_owner, forum_moderated, forum_project');
$q->addWhere('forum_id = ' . $forum_id);
$forum_row = $q->loadHash();
// An existing message: its author, the forum's owner or moderator, or an editor of the
// forum's project, as post_message.php offers the form (any forum editor could change or
// delete any message).
$may_change = !$stored || ($forum_row && ((int)$stored->message_author == (int)$AppUI->user_id
	|| (int)$forum_row['forum_owner'] == (int)$AppUI->user_id
	|| (int)$forum_row['forum_moderated'] == (int)$AppUI->user_id
	|| ($forum_row['forum_project'] && getPermission('projects', 'edit', (int)$forum_row['forum_project']))));
if (!$forum_row || !getPermission('forums', 'edit', $forum_id) || !$may_change
		|| ($del && !getPermission('forums', 'delete', $forum_id))) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

$obj = new CForumMessage();

if (($msg = $obj->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}
// The message stays in its forum and with its author; a new one is the user's.
$obj->message_forum = $forum_id;
$obj->message_author = $stored ? $stored->message_author : $AppUI->user_id;
if ($obj->message_parent > 0) {
	$parent = new CForumMessage();
	if (!$parent->load((int)$obj->message_parent) || (int)$parent->message_forum !== $forum_id) {
		$obj->message_parent = -1;
	}
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Message' );
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect();
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['message_id'];
		$AppUI->setMsg( $isNotNew ? 'updated' : 'added', UI_MSG_OK, true );
	}
	$parent = ( $obj->message_parent == -1 ) ? $obj->message_id : $obj->message_parent;
	$AppUI->redirect("m=forums&a=viewer&forum_id=$obj->message_forum&message_id=$parent");
}
?>