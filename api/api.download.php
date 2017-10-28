<?php
  session_start();
  session_set_cookie_params(1200);

  if ((!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] <= 0 || strlen($_GET['id']) > 5)) { die('Invalid file ID.'); }

  require_once('generalroutines.php');

  $dbconn = connectdatabase();
  $data = pg_query($dbconn, 'SELECT * FROM general.filestorage WHERE id='.$_GET['id'].' LIMIT 1');
  if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn));  }
  if (pg_num_rows($data) <= 0) { xmlerror(64); }
  $row = pg_fetch_assoc($data);
  pg_close($dbconn);
  
  $ok = false;
  if ($row['protected'] == 0) { $ok = true; }
  if ($row['protected'] == 1 && isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0) { $ok = true; }
  if ($row['protected'] == 2 && isset($_SESSION['user_admin']) && $_SESSION['user_admin'] == 1) { $ok = true; }
  if (!$ok) { die('Access denied.'); }
    
  if (!file_exists('../userdata/filestorage/'.$row['filename'])) { die('File not found.'); }

  $dbconn = connectdatabase();
  $data = pg_query($dbconn, 'UPDATE general.filestorage SET accessed='.($row['accessed']+1).' WHERE id='.$row['id']);
  if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn));  }
  pg_close($dbconn);
    
  header('Content-Description: File Transfer');
  header('Content-Type: '.stripslashes($row['mime']), true);
  header('Content-Disposition: attachment; filename="'.$row['filename'].'"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize('../userdata/filestorage/'.$row['filename']));
  readfile('../userdata/filestorage/'.$row['filename']);

?>
