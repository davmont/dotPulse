<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* FILES $Id: do_file_aed.php,v 1.1 2004/08/30 19:02:41 jcgonz Exp $ */

require_once("modules/mngdocument/files.class.php");

$file_id = intval( dPgetParam( $_POST, 'file_id', 0 ) );
$del = intval( dPgetParam( $_POST, 'del', 0 ) );
$padre = intval( dPgetParam( $_POST, 'padre', 0 ) );
$descripcion = dPgetCleanParam( $_POST, 'descripcion', '' );
$dirname = dPgetCleanParam( $_POST, 'dirname', '' );
$accion = dPvalidateOrder( dPgetParam( $_POST, 'accion', '' ), array('newdocument', 'newdirectory', 'deldirectory', 'deldocument'), '' );
$actual = dPgetIntParam( $_POST, 'actual', 0 );
$delall = dPgetParam( $_POST, 'delall', '' );

// The form creates new records or deletes one; it never edits a record, so nothing is
// bound from the request: the fields are set below for the action.
if ($del) {
	$accion = 'deldocument';
	$actual = $file_id;
	$delall = 1;
}
if ($accion === '') {
	$AppUI->redirect();
}
if ($accion == 'deldirectory' || $accion == 'deldocument') {
	dPrequireWritePermission('mngdocument', $actual, true);
	// The record must exist and be of the kind the action deletes.
	$q = new DBQuery();
	$q->addTable('SGD');
	$q->addQuery('SGD_type');
	$q->addWhere('SGD_id = ' . (int)$actual);
	$type = $q->loadResult();
	if ($type === null || $type === false || (int)$type != ($accion == 'deldirectory' ? 1 : 0)) {
		$AppUI->setMsg( 'File' );
		$AppUI->setMsg( 'invalidID', UI_MSG_ERROR, true );
		$AppUI->redirect();
	}
} else {
	dPrequireWritePermission('mngdocument', 0, false);
	// A new record goes into an existing directory the user may edit, or the top level.
	if ($padre) {
		$q = new DBQuery();
		$q->addTable('SGD');
		$q->addQuery('SGD_type');
		$q->addWhere('SGD_id = ' . (int)$padre);
		if ((int)$q->loadResult() !== 1 || !getPermission('mngdocument', 'edit', $padre)) {
			$AppUI->setMsg( 'Access denied.', UI_MSG_ERROR );
			$AppUI->redirect( 'm=public&a=access_denied' );
		}
	}
}

$obj = new CFile("SGD","SGD_id");

$log = new CFileLog ("SGD_Logs","SGD_Log_id");

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'File' );

set_time_limit( 600 );
ignore_user_abort( 1 );

$log->SGD_Logs_user_id = $AppUI->user_id;
$log->SGD_Logs_date = Date("d/m/Y H:i");
$log->SGD_Logs_action = $accion;

switch ($accion)
{
 case "newdocument":
  $upload = null;
  if (isset( $_FILES['formfile'] )) 
  {
   $upload = $_FILES['formfile'];
   if ($upload['size'] < 1)
   {
    if (!$file_id)
    {
     $AppUI->setMsg( 'Upload file size is zero. Process aborted.', UI_MSG_ERROR );
     $AppUI->redirect();
    }
   }
   else
   {
 // the name is only shown; the file is moved under the record id once it is stored
    $obj->SGD_id = 0;
    $obj->SGD_name = $upload['name'];
    $obj->SGD_type = 0;
    $obj->SGD_date= db_unix2dateTime( time() );
    $obj->SGD_parent = $padre;
    $obj->SGD_description = $descripcion;
   }
  }
 break;
 case "newdirectory":
  if (Trim($dirname)=="")
  {
   $AppUI->setMsg( "Debe de introducir un nombre para el directorio a crear.", UI_MSG_ERROR );
   $AppUI->redirect();
  }

  $obj->SGD_id = 0;
  $obj->SGD_name = $dirname;
  $obj->SGD_type = 1;
  $obj->SGD_date= db_unix2dateTime( time() );
  $obj->SGD_parent = $padre;
  $obj->SGD_description = $descripcion;
 break;
 case "deldirectory":
  $sql="select SGD_id,SGD_type,SGD_name from " . dPgetConfig('dbprefix', '') . "SGD where SGD_parent=" . (int)$actual;
  $resultado=db_exec($sql);
  if ($delall<>1 and db_num_rows($resultado)>0)
    $AppUI->setMsg( "El directorio contiene ficheros y/o subdirectorios. Imposible eliminar.", UI_MSG_ERROR );
  else
    BorrarDirectorio($actual,$log);
  $AppUI->redirect();
 break;
 case "deldocument":
  if ($delall==1)
  {
   $sql="select SGD_name from " . dPgetConfig('dbprefix', '') . "SGD where SGD_id=$actual";
   $resultado=db_exec($sql);
   $row=db_fetch_row($resultado);
   $obj->SGD_name=$row[0];
   $obj->SGD_id=$actual;
   $obj->deletefile();
   $log->SGD_Logs_document_name=$row[0];
   if (($msg = $log->store()))
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
  }
  $AppUI->redirect();
 break;
}

function BorrarDirectorio($actual,$log)
{
 global $AppUI;

 $obj = new CFile();

 $sql="select SGD_id,SGD_type,SGD_name from " . dPgetConfig('dbprefix', '') . "SGD where SGD_parent=$actual";
 $resultado=db_exec($sql);
 while ($row=db_fetch_row($resultado))
  BorrarDirectorio($row[0],$log);
 
 $obj->SGD_id=$actual;
 $sql="select SGD_type,SGD_name from " . dPgetConfig('dbprefix', '') . "SGD where SGD_id=$actual";
 $resultado=db_exec($sql);
 $row=db_fetch_row($resultado);
 $log->SGD_Logs_document_name=$row[1];
 if ($row[0]==0)
 {
  $obj->SGD_name=$row[1];
  $obj->deletefile();
 }
 else
  $obj->deletedirectory();
 if (($msg = $log->store()))
   $AppUI->setMsg( $msg, UI_MSG_ERROR );
}

if (!$obj->SGD_name || (int)$obj->SGD_id !== 0) {
	// Nothing to add (no upload, for example).
	$AppUI->redirect();
}
if (($msg = $obj->store())) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
} else {
	if ($accion == 'newdocument' && $upload && $upload['size'] > 0 && !$obj->moveTemp( $upload )) {
		$obj->deletefile();
		$AppUI->setMsg( 'File could not be written', UI_MSG_ERROR );
		$AppUI->redirect();
	}
	$obj->load($obj->SGD_id);
	$AppUI->setMsg( $file_id ? 'updated' : 'added', UI_MSG_OK, true );
        $log->SGD_Logs_document_name = $obj->SGD_name;
        $log->store();
}
$AppUI->redirect();
?>
