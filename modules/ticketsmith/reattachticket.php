<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

//update task
$newparent = (int) dPgetParam($_POST, 'newparent', 0);
$ticket = (int) dPgetParam($_POST, 'ticket', 0);
$dbprefix = dPgetConfig('dbprefix', '');

$sql1 = "update {$dbprefix}tickets set parent = $newparent,
  assignment = 9999,
  type = 'Client Followup'
  where ticket = $ticket";

header("Location: index.php?m=ticketsmith");
if ($newparent != 0 && $ticket != 0) {
  // error_log("Updating ticket - $sql1");
  db_exec($sql1);
  // error_log(db_error());
  $sql2 = "update {$dbprefix}tickets set activity = '" . time() . "' where ticket = $newparent";
  // error_log($sql2);
  db_exec($sql2);
  // error_log(db_error());
}
// else error_log("Ticket has not been reassigned");
?>