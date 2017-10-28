<?php
  require_once('api.start.php');
  
//  getfile --------------------------------------------------

  if ($_POST['cmd'] == 'getfile') {
  
    if (isset($_POST['search']) && $_POST['search'] != '' && strlen($_POST['search']) < 3) { xmlerror(51); }
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6)) { xmlerror(59); }
    
    if ((!isset($_POST['search']) || $_POST['search'] == '') && (!isset($_POST['id']))) {
      if (!isset($_POST['year']) || !is_numeric($_POST['year']) || $_POST['year'] < 2015 || $_POST['year'] > 2040) { xmlerror(60); }
      if (!isset($_POST['month']) || !is_numeric($_POST['month']) || $_POST['month'] < 1 || $_POST['month'] > 12) { xmlerror(61); }
    }

    $startdate = mktime(0, 0, 0, $_POST['month'], 1, $_POST['year']);
    $lastday = 31;
    if ($_POST['month'] == 4 || $_POST['month'] == 6 || $_POST['month'] == 9 || $_POST['month'] == 11) { $lastday = 30; }
    if ($_POST['month'] == 2 && $_POST['year'] % 4 == 0) { $lastday = 29; }
    if ($_POST['month'] == 2 && $_POST['year'] % 4 != 0) { $lastday = 28; }
    $enddate = mktime(23, 59, 59, $_POST['month'], $lastday, $_POST['year']);
    
    $filter = 'WHERE filestorage.created >= '.$startdate.' AND filestorage.created <= '.$enddate;
    if (isset($_POST['search']) && $_POST['search'] != '') { $filter = 'WHERE filestorage.info ILIKE \'%'.$_POST['search'].'%\' OR filestorage.filename ILIKE \'%'.$_POST['search'].'%\''; }
    if (isset($_POST['id'])) { $filter = 'WHERE filestorage.id='.$_POST['id']; }
        
    $data = pg_query($dbconn, 'SELECT
                                     filestorage.*,
                                     users.name AS user_name

                               FROM general.filestorage AS filestorage
                               LEFT JOIN general.users AS users ON users.id = filestorage.user_id
                               '.$filter.'
                               ORDER BY created DESC');

    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('total', pg_num_rows($data));
    $xmlWriter->addChild('offset', $offset);
        
    while ($row = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('file');
          
          $xmlWriter->addChild('id', $row['id']);
          $xmlWriter->addChild('filename', stripslashes($row['filename']));
          $xmlWriter->addChild('user_id', stripslashes($row['user_id']));
          $xmlWriter->addChild('created', $row['created']);
          $xmlWriter->addChild('created_formatted', date('Y-m-d H:i:s', $row['created']));
          $xmlWriter->addChild('mime', stripslashes($row['mime']));
          $xmlWriter->addChild('user_name', stripslashes($row['user_name']), true);
          $xmlWriter->addChild('info', stripslashes($row['info']), true);
          $xmlWriter->addChild('protected', $row['protected']);
          $xmlWriter->addChild('accessed', $row['accessed']);

          clearstatcache();
          $filesize = filesize('../userdata/filestorage/'.$row['filename']);

          $filemeasure = 'B';
          if ($filesize > 1024) { $filesize = $filesize / 1024; $filemeasure = 'KB'; }
          if ($filesize > 1024) { $filesize = $filesize / 1024; $filemeasure = 'MB'; }
          if ($filesize > 1024) { $filesize = $filesize / 1024; $filemeasure = 'GB'; }
          
          $xmlWriter->addChild('filesize', number_format($filesize, 0, $_SESSION['language_decimalpoint'], $_SESSION['language_thousandsseparator']).' '.$filemeasure);
          
          $xmlWriter->endElement();

    }
 }

//  deletefile --------------------------------------------------

  if ($_POST['cmd'] == 'deletefile') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(62); }
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) && $error == 0) { xmlerror(63); }
    
    if ($error == 0) {

      $data = pg_query($dbconn, 'SELECT * FROM general.filestorage WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(63); }
        $row = pg_fetch_assoc($data);
        unlink('../userdata/filestorage/'.$row['filename']);
        $data = pg_query($dbconn, 'DELETE FROM general.filestorage WHERE id='.$_POST['id']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn));  }
          
        $xmlWriter->addChild('id', $_POST['id']);
   }
 }
  
//  addfile --------------------------------------------------

  if ($_POST['cmd'] == 'addfile') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(64); }
      if (!isset($_POST['info']) || strlen($_POST['info']) < 2) { xmlerror(65); }
      if (!isset($_POST['protected']) || !is_numeric($_POST['protected']) || $_POST['protected'] < 0 || $_POST['protected'] > 2) { xmlerror(77); }
      
      if ($_POST['file'] != '') {
        $file = '';
        $data = explode(',', $_POST['file']);
        $file = base64_decode($data[1]);
     } else {
        $file = null;
     }
     
     $mime = substr($data[0], 5, strripos($data[0], ';')-5);
     if ($mime == '') { $mime = 'application/octet-stream'; }

     $_POST['filename'] = sanitize(substr($_POST['filename'], 0, strripos($_POST['filename'], '.'))).'.'.sanitize(substr($_POST['filename'], strripos($_POST['filename'], '.')+1));

  }

//  addfile - new file --------------------------------------------------

  if ($_POST['cmd'] == 'addfile' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {
      
      $data = pg_query($dbconn, 'INSERT INTO general.filestorage (
                                    filename,
                                    accessed,
                                    created,
                                    user_id,
                                    protected,
                                    mime,
                                    info
                                 ) VALUES (
                                    \''.pg_escape_string($_POST['filename']).'\',
                                    0,
                                    '.date('U').',
                                    '.$_SESSION['user_id'].',
                                    '.$_POST['protected'].',
                                    \''.$mime.'\',
                                    \''.pg_escape_string($_POST['info']).'\'
                                 ) RETURNING id');
                                 
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      $row = pg_fetch_assoc($data);
      
      if ($file == null || !file_put_contents('../userdata/filestorage/'.$_POST['filename'], $file)) {
          $data = pg_query($dbconn, 'DELETE FROM general.filestorage WHERE id='.$row['id']);
          xmlerror(235, '%filename%|'.$_POST['filename']);
      }

      $xmlWriter->addChild('id', $row['id']);

  }

//  addfile - modify file --------------------------------------------------

  if ($_POST['cmd'] == 'addfile' && isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] != 0) {

    if (strlen($_POST['id']) > 6) { xmlerror(79); }
    
      $data = pg_query($dbconn, 'SELECT * FROM general.filestorage WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      $row = pg_fetch_assoc($data);
    
      $data = pg_query($dbconn, 'UPDATE general.filestorage SET
                                  info = \''.pg_escape_string($_POST['info']).'\',
                                  filename = \''.pg_escape_string($_POST['filename']).'\',
                                  protected = \''.pg_escape_string($_POST['protected']).'\'
                                 WHERE id='.$_POST['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      if ($file != null) {
        @unlink('../userdata/filestorage/'.stripslashes($row['filename']));
      }

      if ($file != null && !file_put_contents('../userdata/filestorage/'.$_POST['filename'], $file)) {
          $data = pg_query($dbconn, 'DELETE FROM general.filestorage WHERE id='.$_POST['id']);  
          xmlerror(78, '%filename%|'.$_POST['filename']);
      }

      $xmlWriter->addChild('id', $_POST['id']);
 }

require('api.end.php');

?>
