<?php
  require_once('api.start.php');
  
//  getimage --------------------------------------------------

  if ($_POST['cmd'] == 'getimage') {
  
    if (isset($_POST['search']) && $_POST['search'] != '' && strlen($_POST['search']) < 3) { xmlerror(331); }
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6)) { xmlerror(230); }
    
    if ((!isset($_POST['search']) || $_POST['search'] == '') && (!isset($_POST['id']))) {
      if (!isset($_POST['year']) || !is_numeric($_POST['year']) || $_POST['year'] < 2015 || $_POST['year'] > 2040) { xmlerror(327); }
      if (!isset($_POST['month']) || !is_numeric($_POST['month']) || $_POST['month'] < 1 || $_POST['month'] > 12) { xmlerror(330); }
    }

    $startdate = mktime(0, 0, 0, $_POST['month'], 1, $_POST['year']);
    $lastday = 31;
    if ($_POST['month'] == 4 || $_POST['month'] == 6 || $_POST['month'] == 9 || $_POST['month'] == 11) { $lastday = 30; }
    if ($_POST['month'] == 2 && $_POST['year'] % 4 == 0) { $lastday = 29; }
    if ($_POST['month'] == 2 && $_POST['year'] % 4 != 0) { $lastday = 28; }
    $enddate = mktime(23, 59, 59, $_POST['month'], $lastday, $_POST['year']);
    
    $filter = 'WHERE gallery.created >= '.$startdate.' AND gallery.created <= '.$enddate;
    if (isset($_POST['search']) && $_POST['search'] != '') { $filter = 'WHERE gallery.info ILIKE \'%'.$_POST['search'].'%\' OR gallery_lang.caption ILIKE \'%'.$_POST['search'].'%\''; }
    if (isset($_POST['id'])) { $filter = 'WHERE gallery.id='.$_POST['id']; }
        
    $data = pg_query($dbconn, 'SELECT
                                     gallery.id AS id,
                                     gallery.user_id AS user_id,
                                     gallery.created AS created,
                                     gallery.info AS info,
                                     gallery_lang.caption AS caption,
                                     users.name AS user_name

                               FROM general.gallery AS gallery
                               LEFT JOIN general.gallery_lang AS gallery_lang ON gallery_lang.imageid = gallery.id AND gallery_lang.language = '.$_SESSION['language'].'
                               LEFT JOIN general.users AS users ON users.id = gallery.user_id
                               '.$filter.'  
                               ORDER BY created DESC');

    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('total', pg_num_rows($data));
    $xmlWriter->addChild('offset', $offset);
        
    while ($row = pg_fetch_assoc($data)) {

          if (file_exists('../userdata/gallery/'.$row['id'].'.png')) { $filename = $row['id'].'.png'; }
          if (file_exists('../userdata/gallery/'.$row['id'].'.jpg')) { $filename = $row['id'].'.jpg'; }
          if (file_exists('../userdata/gallery/'.$row['id'].'.gif')) { $filename = $row['id'].'.gif'; }

          $xmlWriter->startElement('image');
          
          $xmlWriter->addChild('filename', $filename);
          $xmlWriter->addChild('id', $row['id']);
          $xmlWriter->addChild('user_id', stripslashes($row['user_id']));
          $xmlWriter->addChild('created', $row['created']);
          $xmlWriter->addChild('created_formatted', date('Y-m-d H:i:s', $row['created']));
          $xmlWriter->addChild('caption', stripslashes($row['caption']), true);
          $xmlWriter->addChild('user_name', stripslashes($row['user_name']), true);
          $xmlWriter->addChild('info', stripslashes($row['info']), true);

          clearstatcache();
          $filesize = filesize('../userdata/gallery/'.$filename);

          $filemeasure = 'B';
          if ($filesize > 1024) { $filesize = $filesize / 1024; $filemeasure = 'KB'; }
          if ($filesize > 1024) { $filesize = $filesize / 1024; $filemeasure = 'MB'; }
          if ($filesize > 1024) { $filesize = $filesize / 1024; $filemeasure = 'GB'; }
          
          $xmlWriter->addChild('filesize', number_format($filesize, 0, $_SESSION['language_decimalpoint'], $_SESSION['language_thousandsseparator']).' '.$filemeasure);

          if (file_exists('../userdata/gallery/'.$row['id'].'.png')) { $pic = imagecreatefrompng('../userdata/gallery/'.$row['id'].'.png'); }
          if (file_exists('../userdata/gallery/'.$row['id'].'.jpg')) { $pic = imagecreatefromjpeg('../userdata/gallery/'.$row['id'].'.jpg'); }
          if (file_exists('../userdata/gallery/'.$row['id'].'.gif')) { $pic = imagecreatefromgif('../userdata/gallery/'.$row['id'].'.gif'); }
          
          $xmlWriter->addChild('width', imagesx($pic));
          $xmlWriter->addChild('height', imagesy($pic));
          
          $xmlWriter->endElement();
          imagedestroy($pic);

    }
 }

//  addimage --------------------------------------------------

  if ($_POST['cmd'] == 'addimage') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(231); }
      if (!isset($_POST['info']) || strlen($_POST['info']) < 2) { xmlerror(232); }

      if ($_POST['image'] != '' &&
         substr($_POST['image'], 0, 22) != 'data:image/png;base64,' &&
         substr($_POST['image'], 0, 23) != 'data:image/jpeg;base64,' &&
         substr($_POST['image'], 0, 22) != 'data:image/gif;base64,') { xmlerror(234); }
      
      if ($_POST['image'] != '') {
        $pic = '';
        $data = explode(',', $_POST['image']);
        $pic = base64_decode($data[1]);
        if (strlen($pic) < 1024) { xmlerror(234); }
        
        if ($data[0] == 'data:image/png;base64') { $ext = 'png'; }
        if ($data[0] == 'data:image/jpeg;base64') { $ext = 'jpg'; }
        if ($data[0] == 'data:image/gif;base64') { $ext = 'gif'; }
     } else {
        $pic = null;
     }

  }

//  addimage - new image --------------------------------------------------

  if ($_POST['cmd'] == 'addimage' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {
      
      $data = pg_query($dbconn, 'INSERT INTO general.gallery (
                                    user_id,
                                    created,
                                    info
                                 ) VALUES (
                                    '.$_SESSION['user_id'].',
                                    '.date('U').',
                                    \''.pg_escape_string($_POST['info']).'\'
                                 ) RETURNING id');
                                 
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      $row = pg_fetch_assoc($data);
      
      if ($pic == null || !file_put_contents('../userdata/gallery/'.$row['id'].'.'.$ext, $pic)) {
          $data = pg_query($dbconn, 'DELETE FROM general.gallery WHERE id='.$row['id']);  
          xmlerror(235, '%filename%|'.$row['id'].'.'.$ext);
      }

      if ($_POST['caption'] != '') {
        $data = pg_query($dbconn, 'INSERT INTO general.gallery_lang (
                                        imageid,
                                        language,
                                        caption
                                    ) VALUES (
                                        '.$row['id'].',
                                        '.$_SESSION['language'].',
                                        \''.pg_escape_string($_POST['caption']).'\'
                                    )');

         if (!$data) {
            $error = pg_last_error($dbconn);
            unlink('../userdata/gallery/'.$row['id'].'.'.$ext);
            $data = pg_query($dbconn, 'DELETE FROM general.gallery WHERE id='.$row['id']);  
            xmlerror(1000, '%errormsg%|'.$error);
         }
       }

       $xmlWriter->startElement('addimage');
       $xmlWriter->addChild('id', $row['id']);
       $xmlWriter->addChild('ext', $ext);
       $xmlWriter->endElement();

  }

//  addimage - modify image --------------------------------------------------

  if ($_POST['cmd'] == 'addimage' && isset($_POST['id']) && is_numeric($_POST['id']) && $_POST['id'] != 0) {

    if (strlen($_POST['id']) > 6) { xmlerror(236); }
    
      $data = pg_query($dbconn, 'UPDATE general.gallery SET
                                  info = \''.pg_escape_string($_POST['info']).'\'
                                 WHERE id='.$_POST['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      if ($pic != null && !file_put_contents('../userdata/gallery/'.$_POST['id'].'.'.$ext, $pic)) {
          $data = pg_query($dbconn, 'DELETE FROM general.gallery WHERE id='.$row['id']);  
          xmlerror(235, '%filename%|'.$row['id'].'.'.$ext);
      }

      $data = pg_query($dbconn, 'UPDATE general.gallery_lang SET caption = \''.pg_escape_string($_POST['caption']).'\'
                                 WHERE imageid='.$_POST['id'].' AND language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      else {
        $xmlWriter->startElement('addimage');
        $xmlWriter->addChild('id', $_POST['id']);
        $xmlWriter->addChild('ext', $ext);
        $xmlWriter->endElement();
     }
 }

//  deleteimage --------------------------------------------------

  if ($_POST['cmd'] == 'deleteimage') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(237); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) { xmlerror(236); }
    
      $data = pg_query($dbconn, 'SELECT * FROM general.gallery WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(236); }
        $row = pg_fetch_assoc($data);
        if (file_exists('../userdata/gallery/'.$_POST['id'].'.png')) { unlink('../userdata/gallery/'.$_POST['id'].'.png'); }
        if (file_exists('../userdata/gallery/'.$_POST['id'].'.gif')) { unlink('../userdata/gallery/'.$_POST['id'].'.gif'); }
        if (file_exists('../userdata/gallery/'.$_POST['id'].'.jpg')) { unlink('../userdata/gallery/'.$_POST['id'].'.jpg'); }
        $data = pg_query($dbconn, 'DELETE FROM general.gallery WHERE id='.$_POST['id']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn));  }
        $data = pg_query($dbconn, 'DELETE FROM general.gallery_lang WHERE imageid='.$_POST['id']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
          
        $xmlWriter->addChild('id', $_POST['id']);
 }

//  updatecaption --------------------------------------------------

  if ($_POST['cmd'] == 'updatecaption') {
      
    if ($_SESSION['user_admin'] != 1) { xmlerror(238); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) { xmlerror(239); }
    if (!isset($_POST['caption']) || strlen($_POST['caption']) == '') { xmlerror(240); }

    $data = pg_query($dbconn, 'SELECT * FROM general.gallery_lang WHERE imageid='.$_POST['id'].' AND language='.$_SESSION['language']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) > 0) {
      $data = pg_query($dbconn, 'UPDATE general.gallery_lang SET caption=\''.pg_escape_string($_POST['caption']).'\' WHERE imageid='.$_POST['id'].' AND language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    } else {
      $data = pg_query($dbconn, 'INSERT INTO general.gallery_lang (
                                        imageid,
                                        language,
                                        caption
                                    ) VALUES (
                                        '.$_POST['id'].',
                                        '.$_SESSION['language'].',
                                        \''.pg_escape_string($_POST['caption']).'\'
                                    )');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    }
  }

//  resizeimage --------------------------------------------------

  if ($_POST['cmd'] == 'resizeimage') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(335); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) { xmlerror(336); }
    if (!isset($_POST['rotate']) || !is_numeric($_POST['rotate']) || $_POST['rotate'] < 0 || $_POST['rotate'] > 3) { xmlerror(337); }
    if (!isset($_POST['flip']) || !is_numeric($_POST['flip']) || $_POST['flip'] < 0 || $_POST['flip'] > 2) { xmlerror(338); }
    if (!isset($_POST['resize']) || !is_numeric($_POST['resize']) || $_POST['resize'] < 0 || $_POST['resize'] > 1) { xmlerror(339); }
    if ($_POST['resize'] == 1 && (!isset($_POST['resize_x']) || !is_numeric($_POST['resize_x']) || $_POST['resize_x'] < 10 || $_POST['resize_x'] > 4096)) { xmlerror(340); }
    if ($_POST['resize'] == 1 && (!isset($_POST['resize_y']) || !is_numeric($_POST['resize_y']) || $_POST['resize_y'] < 10 || $_POST['resize_y'] > 4096)) { xmlerror(341); }
    if (!file_exists('../userdata/gallery/'.$_POST['id'].'.png')) { xmlerror(336); }
    if ($_POST['resize'] == 0 && $_POST['flip'] == 0 && $_POST['rotate'] == 0) { xmlerror(342); }
    if ($_POST['letterbox'] != 'true') { $_POST['letterbox'] = 'false'; }
    
    if (file_exists('../userdata/gallery/'.$_POST['id'].'.png')) { $source = imagecreatefrompng('../userdata/gallery/'.$_POST['id'].'.png'); }
    if (file_exists('../userdata/gallery/'.$_POST['id'].'.jpg')) { $source = imagecreatefrompng('../userdata/gallery/'.$_POST['id'].'.jpg'); }
    if (file_exists('../userdata/gallery/'.$_POST['id'].'.gif')) { $source = imagecreatefrompng('../userdata/gallery/'.$_POST['id'].'.gif'); }
    
    if (!$source) { xmlerror(336); }

    if ($_POST['resize'] == 1) { 
      $result = resizeimage($source, (integer)$_POST['resize_x'], (integer)$_POST['resize_y'], 0, 0);
    } else {
      $result = $source;
    }
    
    if ($_POST['flip'] == 1) { imageflip($result, IMG_FLIP_HORIZONTAL); }
    if ($_POST['flip'] == 2) { imageflip($result, IMG_FLIP_VERTICAL); }

    if ($_POST['rotate'] == 1) { $result = imagerotate($result, 270, 0); }
    if ($_POST['rotate'] == 2) { $result = imagerotate($result, 90, 0); }
    if ($_POST['rotate'] == 3) { $result = imagerotate($result, 180, 0); }
    
    $xmlWriter->addChild('width', imagesx($result));
    $xmlWriter->addChild('height', imagesy($result));

    if (file_exists('../userdata/gallery/'.$_POST['id'].'.png') && !imagepng($result, '../userdata/gallery/'.$_POST['id'].'.png', (integer)getsetting('PNG_QUALITY'), PNG_NO_FILTER)) { xmlerror(343); }
    if (file_exists('../userdata/gallery/'.$_POST['id'].'.jpg') && !imagejpeg($result, '../userdata/gallery/'.$_POST['id'].'.jpg', (integer)getsetting('JPEG_QUALITY'))) { xmlerror(343); }
    if (file_exists('../userdata/gallery/'.$_POST['id'].'.gif') && !imagegif($result, '../userdata/gallery/'.$_POST['id'].'.gif')) { xmlerror(343); }

    imagedestroy($source);
    imagedestroy($result);

  }


require('api.end.php');

?>
