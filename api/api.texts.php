<?php
  require_once('api.start.php');
  $chunksize = (integer)getsetting('XML_NODE_CHUNKSIZE');

//  addtext --------------------------------------------------

  if ($_POST['cmd'] == 'addtext') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(15); }
    if ((!isset($_POST['token']) || strlen($_POST['token']) < 3 || strlen($_POST['token']) > 255)) { xmlerror(16); }
    if ((!isset($_POST['groupid']) || !is_numeric($_POST['groupid']) || $_POST['groupid'] <= 0 || strlen($_POST['groupid']) > 5)) { xmlerror(17); }
    
    $_POST['token'] = strtolower(sanitize($_POST['token']));
 }

//  addtext - new text --------------------------------------------------

  if ($_POST['cmd'] == 'addtext' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {
    
//  availability check

    $data = pg_query($dbconn, 'SELECT * FROM general.texts WHERE token ILIKE \''.$_POST['token'].'\' AND texts.groupid = '.$_POST['groupid'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    else if (pg_num_rows($data) > 0) { xmlerror(18); }

      $data = pg_query($dbconn, 'INSERT INTO general.texts (
                                    token,
                                    groupid
                                 ) VALUES (
                                    \''.pg_escape_string($_POST['token']).'\',
                                    \''.pg_escape_string($_POST['groupid']).'\'
                                 ) RETURNING id');

      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      else {
      
        $row = pg_fetch_assoc($data);
        $data = pg_query($dbconn, 'INSERT INTO general.texts_lang (
                                      text_id,
                                      language,
                                      user_id,
                                      text
                                  ) VALUES (
                                      '.$row['id'].',
                                      '.$_SESSION['language'].',
                                      '.$_SESSION['user_id'].',
                                      \''.pg_escape_string($_POST['text']).'\'
                                  ) RETURNING id');

        if (!$data) { 
          $data = pg_query($dbconn, 'DELETE FROM general.texts WHERE token ILIKE \''.$_POST['token'].'\' AND groupid = '.$_POST['groupid']);  
          xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn));
       }
        else {

//  response

          $row = pg_fetch_assoc($data);
          $xmlWriter->addChild('id', $row['id']);

       }
     }
 }

//  addtext - modify text --------------------------------------------------

  if ($_POST['cmd'] == 'addtext' && is_numeric($_POST['id']) && $_POST['id'] != 0) {

    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6) { xmlerror(197); }
    if (!isset($_POST['groupid']) || !is_numeric($_POST['groupid']) || $_POST['groupid'] == '' || strlen($_POST['groupid']) > 6) { xmlerror(196); }
    if (!isset($_POST['token']) || $_POST['token'] == '' || strlen($_POST['token']) < 4) { xmlerror(195); }
    if (!isset($_POST['text']) || $_POST['text'] == '') { xmlerror(194); }
    if ($_SESSION['user_admin'] != 1) { xmlerror(193); }
    
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.text_groups WHERE id='.$_POST['groupid'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      else if (pg_num_rows($data) <= 0) { xmlerror(9); $error = 9; }
      else $group = pg_fetch_assoc($data);
   }
    
    if ($error == 0) {
            
      $data = pg_query($dbconn, 'UPDATE general.texts SET
                          token = \''.pg_escape_string($_POST['token']).'\',
                          groupid = \''.pg_escape_string($_POST['groupid']).'\'

                        WHERE id='.$_POST['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            
      $data = pg_query($dbconn, 'UPDATE general.texts_lang SET 
                                            text = \''.pg_escape_string($_POST['text']).'\',
                                            user_id = '.$_SESSION['user_id'].'
                                 WHERE text_id='.$_POST['id'].' AND language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      else {
        $xmlWriter->startElement('addtext');
        $xmlWriter->addChild('id', $_POST['id']);
        $xmlWriter->addChild('token', $_POST['token']);
        $xmlWriter->endElement();
     }
   }
 }

//  gettext --------------------------------------------------

  if ($_POST['cmd'] == 'gettext') {
  
    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }
    if ($_POST['search'] == 'Keresés...') { $_POST['search'] = null; }
    
    $filter = 'WHERE ';
    
    if (isset($_POST['group']) && $_POST['group'] != '') { $filter .= 'text_groups.token=\''.pg_escape_string($_POST['group']).'\''; }

    if (isset($_POST['id']) && $_POST['id'] != '') { 
      if ($filter != 'WHERE ') { $filter .= ' AND '; }
      $filter .= 'texts.id='.$_POST['id']; 
    }

    if (isset($_POST['search']) && $_POST['search'] != '' && strlen($_POST['search']) > 2) { 
      if ($filter != 'WHERE ') { $filter .= ' AND '; }
      $filter .= '(texts.token ILIKE \'%'.$_POST['search'].'%\' OR text ILIKE \'%'.$_POST['search'].'%\') AND texts_lang.language='.$_SESSION['language'];
    }

    if ($filter != 'WHERE ') { $filter .= ' AND '; }
    $filter .= 'texts_lang.language = '.$_SESSION['language'];
            
    if (!isset($_POST['nolimit'])) { $maxcount = getsetting('LIST_COUNT'); }
    else { $maxcount = '2^62'; }
    $xmlWriter->addChild('maxcount', $maxcount);
    
    $data = pg_query($dbconn, 'SELECT
                                        texts.id AS id,
                                        texts.token AS token,
                                        texts.groupid AS groupid,
                                        texts_lang.language AS language,
                                        texts_lang.user_id AS user_id,
                                        text_groups.token AS group_token
                                        
                                   FROM general.texts AS texts
                                   LEFT JOIN general.texts_lang AS texts_lang ON texts_lang.text_id = texts.id
                                   LEFT JOIN general.text_groups AS text_groups ON text_groups.id = texts.groupid
                                   '.$filter);
                                   
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $xmlWriter->addChild('total', pg_num_rows($data));

    $data = pg_query($dbconn, 'SELECT
                                        texts.id AS id,
                                        texts.token AS token,
                                        texts.groupid AS groupid,
                                        texts_lang.language AS language,
                                        texts_lang.text AS text,
                                        texts_lang.user_id AS user_id,
                                        users.name AS user_name,
                                        text_groups.token AS group_token,
                                        text_groups.info AS group_info
                                        
                                   FROM general.texts AS texts
                                   LEFT JOIN general.texts_lang AS texts_lang ON texts_lang.text_id = texts.id
                                   LEFT JOIN general.users AS users ON users.id = texts_lang.user_id
                                   LEFT JOIN general.text_groups AS text_groups ON text_groups.id = texts.groupid
                                   '.$filter.' 
                                   ORDER BY group_token, texts.token 
                                   OFFSET '.$offset.' 
                                   LIMIT '.$maxcount);

    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('offset', $offset);
        
    while ($row = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('text');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('token', stripslashes($row['token']));
          $xmlWriter->addChild('language', stripslashes($row['language']));
          $xmlWriter->addChild('user_id', stripslashes($row['user_id']));
          $xmlWriter->addChild('group_id', stripslashes($row['groupid']));
          $xmlWriter->addChild('group_token', stripslashes($row['group_token']));

          $xmlWriter->addChild('text', stripslashes($row['text']), true);
          
          if ($row['user_id'] != 0) { $xmlWriter->addChild('title', stripslashes($row['user_name']), true); } 
          else { $xmlWriter->addChild('title', getlocalmsg(319), true); }
          
          $xmlWriter->addChild('group_info', stripslashes($row['group_info']), true);

          $languages = simplexml_load_file('../xmldata/languages.xml');
          foreach($languages as $language) {
            if ((string)$language['id'] == $row['language']) {
              $xmlWriter->addChild('language_name', $language->name);
              $xmlWriter->addChild('language_nameeng', $language->nameeng);
            }
          }
          
          $xmlWriter->endElement();
   }
 }

//  deletetext --------------------------------------------------

  if ($_POST['cmd'] == 'deletetext') {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { xmlerror(191); $error = 191; }
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.texts WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      else if (pg_num_rows($data) <= 0) { xmlerror(191); $error = 191; }
      else {
        $row = pg_fetch_assoc($data);
        if ($_SESSION['user_admin'] != 1) { xmlerror(192); $error = 192; }
        else {
        
          if ($_POST['deleteall'] == 'true') {
            $data = pg_query($dbconn, 'DELETE FROM general.texts WHERE id='.$_POST['id']);
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            $data = pg_query($dbconn, 'DELETE FROM general.texts_lang WHERE text_id='.$_POST['id']);
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          } else {
            $data = pg_query($dbconn, 'DELETE FROM general.texts_lang WHERE text_id='.$_POST['id'].' AND language='.$_SESSION['language']);
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          }
          
          $xmlWriter->addChild('id', $_POST['id']);
       }
     }
   }
 }

//  getgroup --------------------------------------------------

  if ($_POST['cmd'] == 'getgroup') {

    if (isset($_POST['id']) && $_POST['id'] != '' && (!is_numeric($_POST['id']) || $_POST['id'] < 0 || strlen($_POST['id']) > 5)) { xmlerror(1); $error = 1; }

    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }
    
    $filter = '';
    if (isset($_POST['id']) && $_POST['id'] != '') { $filter = 'WHERE id='.$_POST['id']; }
        
    if ($error == 0) {

        $maxcount = getsetting('LIST_COUNT');
        $xmlWriter->addChild('maxcount', $maxcount);
    
        $data = pg_query($dbconn, 'SELECT * FROM general.text_groups '.$filter);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        $xmlWriter->addChild('total', pg_num_rows($data));
        $xmlWriter->addChild('offset', $offset);

        $data = pg_query($dbconn, 'SELECT * FROM general.text_groups '.$filter.' ORDER BY token OFFSET '.$offset.' LIMIT '.$maxcount);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        else while ($row = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('group');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('token', stripslashes($row['token']));
          $xmlWriter->addChild('info', stripslashes($row['info']), true);
          $xmlWriter->endElement();

       }
   }
 }

//  deletegroup --------------------------------------------------

  if ($_POST['cmd'] == 'deletegroup') {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { xmlerror(2); $error = 2; }
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.text_groups WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      else if (pg_num_rows($data) <= 0) { xmlerror(3); $error = 3; }
      else {
        $row = pg_fetch_assoc($data);
        if ($_SESSION['user_admin'] != 1) { xmlerror(5); $error = 5; }
        else {
          $data = pg_query($dbconn, 'DELETE FROM general.text_groups WHERE id='.$_POST['id']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          else {

            $xmlWriter->addChild('id', $_POST['id']);

            if ($_POST['deletecontent'] == 'true') {
              $xmlWriter->addChild('deletecontent', 1);
              $data = pg_query($dbconn, 'SELECT * FROM general.texts WHERE groupid='.$_POST['id']);
              while ($row = pg_fetch_assoc($data)) {
                $data2 = pg_query($dbconn, 'DELETE FROM general.texts_lang WHERE text_id='.$row['id']);
             }
              $data = pg_query($dbconn, 'DELETE FROM general.texts WHERE groupid='.$_POST['id']);
           } else {
              $xmlWriter->addChild('deletecontent', 0);
           }
         }  
       }
     }
   }
 }

//  addgroup --------------------------------------------------

  if ($_POST['cmd'] == 'addgroup') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(6); }
    if ((!isset($_POST['token']) || strlen($_POST['token']) < 5 || strlen($_POST['token']) > 255)) { xmlerror(7); }
    else { $_POST['token'] = strtolower(sanitize($_POST['token'])); }
    if (strstr($_POST['token'], ',') != false) { xmlerror(11); }
 }

//  addgroup - new group --------------------------------------------------

  if ($_POST['cmd'] == 'addgroup' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {
    
//  availability check

    $data = pg_query($dbconn, 'SELECT * FROM general.text_groups WHERE token ILIKE \''.$_POST['token'].'\' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) > 0) { xmlerror(8); }

      $data = pg_query($dbconn, 'INSERT INTO general.text_groups (
                                    token,
                                    info

                                 ) VALUES (

                                    \''.pg_escape_string($_POST['token']).'\',
                                    \''.pg_escape_string($_POST['info']).'\'
                                 ) RETURNING id');

      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

//  response

        $row = pg_fetch_assoc($data);
        $xmlWriter->startElement('addgroup');
        $xmlWriter->addChild('id', $row['id']);
        $xmlWriter->addChild('token', $_POST['token']);
        $xmlWriter->endElement();
 }

//  addgroup - modify group --------------------------------------------------

  if ($_POST['cmd'] == 'addgroup' && is_numeric($_POST['id']) && $_POST['id'] != 0) {
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.text_groups WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      else if (pg_num_rows($data) <= 0) { xmlerror(9); $error = 9; }
      else $group = pg_fetch_assoc($data);
   }
    
    if ($error == 0) {
            
      $data = pg_query($dbconn, 'UPDATE general.text_groups SET
                          token = \''.pg_escape_string($_POST['token']).'\',
                          info = \''.pg_escape_string($_POST['info']).'\'

                        WHERE id='.$_POST['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      else {
        $xmlWriter->startElement('addgroup');
        $xmlWriter->addChild('id', $_POST['id']);
        $xmlWriter->addChild('token', $_POST['token']);
        $xmlWriter->endElement();
     }
   }
 }

//  texts_ajaxsearch --------------------------------------------------

  if ($_POST['cmd'] == 'texts_ajaxsearch' && strlen($_POST['search']) > 3) {
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT
                                    texts.*,
                                    texts_lang.*
                                    
                                 FROM general.texts AS texts
                                 LEFT JOIN general.texts_lang AS texts_lang ON texts_lang.text_id = texts.id
                                 WHERE texts.token ILIKE \'%'.pg_escape_string($_POST['search']).'%\' OR
                                       (texts_lang.text ILIKE \'%'.pg_escape_string($_POST['search']).'%\' AND texts_lang.language = '.$_SESSION['language'].')');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      else {
        $row = pg_fetch_assoc($data);
        $xmlWriter->startElement('ajaxsearch');
        
        if (strtolower(substr(stripslashes($row['token']), 0, strlen($_POST['search']))) == strtolower($_POST['search'])) {
          $xmlWriter->addChild('result', stripslashes($row['token']));
        }
        
        if (strtolower(substr(stripslashes($row['text']), 0, strlen($_POST['search']))) == strtolower($_POST['search'])) {
          $xmlWriter->addChild('text', stripslashes($row['text']), true);
        }
        $xmlWriter->endElement();
        
     }
   }
 }

//  translate_text --------------------------------------------------

  if ($_POST['cmd'] == 'translate_text') {

      if (!isset($_POST['text']) || $_POST['text'] == '' || strlen($_POST['text']) < 2) { xmlerror(198); }
      if ($_SESSION['user_admin'] != 1) { xmlerror(199); }
      if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 4) { xmlerror(203); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(201); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = false; }
      
      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { $target_ok = true; }
      }
      if (!$target_ok) { xmlerror(201); }
      
      if (!$_POST['overwrite']) {
        $data = pg_query($dbconn, 'SELECT * FROM general.texts_lang WHERE text_id='.$_POST['id'].' AND language='.$_POST['target'].' LIMIT 1');
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data) > 0) { xmlerror(204); }
        $row = pg_fetch_assoc($data);
     } else {
        $data = pg_query($dbconn, 'DELETE FROM general.texts_lang WHERE text_id='.$_POST['id'].' AND language='.$_POST['target']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
     }
      
      $data = pg_query($dbconn, 'INSERT INTO general.texts_lang (
                                    text_id,
                                    language,
                                    user_id,
                                    text
                                ) VALUES (
                                    '.$_POST['id'].',
                                    '.$_POST['target'].',
                                    '.$_SESSION['user_id'].',
                                    \''.pg_escape_string($_POST['text']).'\'
                                ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $row = pg_fetch_assoc($data);
      $xmlWriter->addChild('id', $row['id']);
 }

//  translate_all --------------------------------------------------

  if ($_POST['cmd'] == 'translate_all') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(207); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(206); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = 'false'; }

      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { 
          $target_ok = true; 
          $target = $language->name.' ('.$language->nameeng.')'; }
      }
      if (!$target_ok) { xmlerror(206); }
      
      $data = pg_query($dbconn, 'SELECT * FROM general.texts_lang WHERE language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $counter = 0;
      while ($row = pg_fetch_assoc($data)) {
          
          $go = false;
          if ($_POST['overwrite'] == 'true') {
            $data2 = pg_query($dbconn, 'DELETE FROM general.texts_lang WHERE language='.$_POST['target'].' AND text_id = '.$row['text_id']);
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            $go = true;
          } else {
            $data2 = pg_query($dbconn, 'SELECT * FROM general.texts_lang WHERE language='.$_POST['target'].' AND text_id = '.$row['text_id'].' LIMIT 1');
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            if (pg_num_rows($data2) <= 0) { $go = true; }
          }
          
          if ($go == true) {
          
              $data2 = pg_query($dbconn, 'INSERT INTO general.texts_lang (
                                          text_id,
                                          language,
                                          user_id,
                                          text
                                        ) VALUES (
                                          '.$row['text_id'].',
                                          '.$_POST['target'].',
                                          '.$_SESSION['user_id'].',
                                          \''.pg_escape_string($row['text']).'\'
                                        )');
              if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
              $counter++;
          }
      }
      
      $xmlWriter->addChild('target', $target);
      $xmlWriter->addChild('counter', $counter);
      
  }

//  getarticle --------------------------------------------------

  if ($_POST['cmd'] == 'getarticle') {
  
    if (isset($_POST['token']) && ($_POST['token'] == '' || strlen($_POST['token']) < 3)) { xmlerror(209); }
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6)) { xmlerror(210); }
  
    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }
    if ($_POST['search'] == 'Keresés...') { $_POST['search'] = null; }

    $filter = 'AND ';
    if (isset($_POST['id']) && $_POST['id'] != '') { $filter .= 'articles.id='.$_POST['id']; }
    
    if (isset($_POST['token']) && $_POST['token'] != '') {
       $token = explode('.', $_POST['token']);
       if (count($token) == 1) {
          $filter .= 'articles.token=\''.$_POST['token'].'\'';
       } else {
          $filter .= 'articles.token=\''.$token[1].'\' AND groups.token=\''.$token[0].'\'';
       }
    }

    if (isset($_POST['search']) && $_POST['search'] != '' && strlen($_POST['search']) > 2) { 
      $filter .= '(articles.token ILIKE \'%'.$_POST['search'].'%\' OR 
                  articles.info ILIKE \'%'.$_POST['search'].'%\' OR
                  articles_lang.intro ILIKE \'%'.$_POST['search'].'%\' OR
                  articles_lang.title ILIKE \'%'.$_POST['search'].'%\')'; 
    }

    if ($filter == 'AND ') { $filter = ''; }
            
        $maxcount = getsetting('LIST_COUNT');
        $xmlWriter->addChild('maxcount', $maxcount);

        $data = pg_query($dbconn, 'SELECT articles.*, 
                                          articles_lang.*,
                                          groups.*
                                          
                                   FROM general.articles AS articles
                                   LEFT JOIN general.articles_lang AS articles_lang ON articles_lang.article_id = articles.id
                                   LEFT JOIN general.text_groups AS groups ON groups.id = articles.groupid
                                   WHERE articles_lang.language = '.$_SESSION['language'].'
                                   '.$filter);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        $xmlWriter->addChild('total', pg_num_rows($data));
    
        $data = pg_query($dbconn, 'SELECT
                                        articles.id AS id,
                                        articles.token AS token,
                                        articles.status AS status,
                                        articles.groupid AS groupid,
                                        articles.info AS info,
                                        articles.created AS created,
                                        
                                        articles_lang.id AS lang_id,
                                        articles_lang.language AS lang_language,
                                        articles_lang.text AS lang_text,
                                        articles_lang.user_id AS user_id,
                                        articles_lang.created AS lang_created,
                                        articles_lang.intro AS intro,
                                        articles_lang.title AS title,
                                        
                                        groups.token AS group_token,
                                        groups.info AS group_info,
                                        
                                        users.id AS user_id,
                                        users.name AS user_name

                                   FROM general.articles AS articles
                                   LEFT JOIN general.articles_lang AS articles_lang ON articles_lang.article_id = articles.id
                                   LEFT JOIN general.text_groups AS groups ON groups.id = articles.groupid
                                   LEFT JOIN general.users AS users ON users.id = articles_lang.user_id
                                   WHERE articles_lang.language = '.$_SESSION['language'].'
                                   '.$filter.'
                                   ORDER BY group_token, articles.created DESC
                                   OFFSET '.$offset.'
                                   LIMIT '.$maxcount);
                                   
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data) == 0 && ((isset($_POST['token']) && $_POST['token'] != '') || (isset($_POST['id']) && $_POST['id'] != ''))) { xmlerror(45); }

        $xmlWriter->addChild('offset', $offset);
        $languages = simplexml_load_file('../xmldata/languages.xml');

        while ($row = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('article');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('token', stripslashes($row['token']));
          $xmlWriter->addChild('language', stripslashes($row['lang_language']));
          $xmlWriter->addChild('user_id', stripslashes($row['user_id']));
          $xmlWriter->addChild('group_id', stripslashes($row['groupid']));
          $xmlWriter->addChild('group_token', stripslashes($row['group_token']));
          $xmlWriter->addChild('created', stripslashes($row['created']));
          $xmlWriter->addChild('created_formatted', date('Y.m.d. H:i', $row['created'] / 1000));
          $xmlWriter->addChild('translated', stripslashes($row['lang_created']));
          $xmlWriter->addChild('translated_formatted', date('Y.m.d. H:i', $row['lang_created'] / 1000));

            $text = stripslashes($row['lang_text']);
            if (strlen($text) > $chunksize) {
              $xmlWriter->startElement('text');
              for ($t=0; $t<strlen($text); $t+=$chunksize) {
                $xmlWriter->addChild('chunk', substr($text, $t, $chunksize), true);
              }
              $xmlWriter->endElement();
            } else {
              $xmlWriter->addChild('text', $text, true);
            }

            $text = stripslashes($row['intro']);
            if (strlen($text) > $chunksize) {
              $xmlWriter->startElement('intro');
              for ($t=0; $t<strlen($text); $t+=$chunksize) {
                $node2->addChild('chunk', substr($text, $t, $chunksize), true);
              }
              $xmlWriter->endElement();
            } else {
              $xmlWriter->addChild('intro', $text, true);
            }

          $xmlWriter->addChild('title', stripslashes($row['title']), true);
          $xmlWriter->addChild('user_name', stripslashes($row['user_name']), true);
          $xmlWriter->addChild('group_info', stripslashes($row['group_info']), true);
          $xmlWriter->addChild('info', stripslashes($row['info']), true);

          foreach($languages as $language) {
            if ((string)$language['id'] == $row['lang_language']) {
              $xmlWriter->addChild('language_name', $language->name);
              $xmlWriter->addChild('language_nameeng', $language->nameeng);
           }
         }
       
        $xmlWriter->endElement();
       
       }
 }


//  addarticle --------------------------------------------------

  if ($_POST['cmd'] == 'addarticle') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(211); }
    if ((!isset($_POST['token']) || strlen($_POST['token']) < 5 || strlen($_POST['token']) > 255)) { xmlerror(212); }
    if (!isset($_POST['groupid']) || !is_numeric($_POST['groupid']) || $_POST['groupid'] == '' || strlen($_POST['groupid']) > 6) { xmlerror(213); }
    if (!isset($_POST['token']) || $_POST['token'] == '' || strlen($_POST['token']) < 4) { xmlerror(215); }
    if (!isset($_POST['text']) || $_POST['text'] == '') { xmlerror(217); }
//    if (!isset($_POST['intro']) || $_POST['intro'] == '') { xmlerror(218); }
    if (!isset($_POST['title']) || $_POST['title'] == '') { xmlerror(219); }
//    if (!isset($_POST['info']) || $_POST['info'] == '') { xmlerror(220); }
    if (!isset($_POST['created']) || !is_numeric($_POST['created'])) { xmlerror(241); }

    $_POST['token'] = strtolower(sanitize($_POST['token']));
 }

//  addarticle - new article --------------------------------------------------

  if ($_POST['cmd'] == 'addarticle' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {
    
//  availability check

    $data = pg_query($dbconn, 'SELECT * FROM general.articles WHERE token ILIKE \''.$_POST['token'].'\' AND groupid = '.$_POST['groupid'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) > 0) { xmlerror(214); }

      $data = pg_query($dbconn, 'INSERT INTO general.articles (
                                    token,
                                    info,
                                    groupid,
                                    status,
                                    created
                                 ) VALUES (
                                    \''.pg_escape_string($_POST['token']).'\',
                                    \''.pg_escape_string($_POST['info']).'\',
                                    '.pg_escape_string($_POST['groupid']).',
                                    0,
                                    '.$_POST['created'].'
                                 ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
        $row = pg_fetch_assoc($data);
        $data = pg_query($dbconn, 'INSERT INTO general.articles_lang (
                                      article_id,
                                      language,
                                      user_id,
                                      text,
                                      intro,
                                      created,
                                      title
                                  ) VALUES (
                                      '.$row['id'].',
                                      '.$_SESSION['language'].',
                                      '.$_SESSION['user_id'].',
                                      \''.pg_escape_string($_POST['text']).'\',
                                      \''.pg_escape_string($_POST['intro']).'\',
                                      '.$_POST['created'].',
                                      \''.pg_escape_string($_POST['title']).'\'
                                  ) RETURNING article_id');

        if (!$data) { 
          $data = pg_query($dbconn, 'DELETE FROM general.texts WHERE token ILIKE \''.$_POST['token'].'\' AND groupid = '.$_POST['groupid']);  
          xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn));
        }
        
        $row = pg_fetch_assoc($data);

//  response

          $xmlWriter->startElement('addtext');
          $xmlWriter->addChild('id', $row['id']);
          $xmlWriter->addChild('token', stripslashes($row['token']));
          $xmlWriter->addChild('title', $_POST['title'], true);
          $xmlWriter->endElement();
 }

//  addarticle - modify article --------------------------------------------------

  if ($_POST['cmd'] == 'addarticle' && is_numeric($_POST['id']) && $_POST['id'] != 0) {

    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6) { xmlerror(216); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.text_groups WHERE id='.$_POST['groupid'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(213); }
    $group = pg_fetch_assoc($data);
    
    $data = pg_query($dbconn, 'UPDATE general.articles SET
                                  token = \''.pg_escape_string($_POST['token']).'\',
                                  groupid = '.$_POST['groupid'].',
                                  status = 0,
                                  created = '.$_POST['created'].',
                                  info = \''.pg_escape_string($_POST['info']).'\'
                               WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            
    $data = pg_query($dbconn, 'UPDATE general.articles_lang SET 
                                    language = '.$_SESSION['language'].',
                                    text = \''.pg_escape_string($_POST['text']).'\',
                                    intro = \''.pg_escape_string($_POST['intro']).'\',
                                    user_id = '.$_SESSION['user_id'].',
                                    title = \''.pg_escape_string($_POST['title']).'\'
                               WHERE article_id='.$_POST['id'].' AND language='.$_SESSION['language']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->startElement('addarticle');
    $xmlWriter->addChild('id', $_POST['id']);
    $xmlWriter->addChild('token', $_POST['token']);
    $xmlWriter->addChild('title', stripslashes($row['title']), true);
    $xmlWriter->endElement();
 }

//  deletearticle --------------------------------------------------

  if ($_POST['cmd'] == 'deletearticle') {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { xmlerror(221); }
    if ($_SESSION['user_admin'] != 1) { xmlerror(222); }
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.articles WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      else if (pg_num_rows($data) <= 0) { xmlerror(221); }
      else {
        $row = pg_fetch_assoc($data);

        if ($_POST['deleteall'] == 'true') {
          
          $data = pg_query($dbconn, 'DELETE FROM general.articles WHERE id='.$_POST['id']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          $data = pg_query($dbconn, 'DELETE FROM general.articles_lang WHERE article_id='.$_POST['id']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

        } else {

          $data = pg_query($dbconn, 'DELETE FROM general.articles_lang WHERE article_id='.$_POST['id'].' AND language='.$_SESSION['language']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

        }
          
        $xmlWriter->addChild('id', $_POST['id']);

     }
   }
 }

//  translate_article --------------------------------------------------

  if ($_POST['cmd'] == 'translate_article') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(222); }
      if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 4) { xmlerror(223); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(224); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = false; }
      
      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { $target_ok = true; }
      }
      if (!$target_ok) { xmlerror(224); }
      
      if (!$_POST['overwrite']) {
        $data = pg_query($dbconn, 'SELECT * FROM general.articles_lang WHERE article_id='.$_POST['id'].' AND language='.$_POST['target'].' LIMIT 1');
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data) > 0) { xmlerror(204); }
      } else {
        $data = pg_query($dbconn, 'DELETE FROM general.articles_lang WHERE article_id='.$_POST['id'].' AND language='.$_POST['target']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      }

      $data = pg_query($dbconn, 'SELECT * FROM general.articles_lang WHERE article_id='.$_POST['id'].' AND language='.$_SESSION['language'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      $row = pg_fetch_assoc($data);
      
      $data = pg_query($dbconn, 'INSERT INTO general.articles_lang (
                                    article_id,
                                    language,
                                    user_id,
                                    text,
                                    intro,
                                    created,
                                    title
                                ) VALUES (
                                    '.$_POST['id'].',
                                    '.$_POST['target'].',
                                    '.$_SESSION['user_id'].',
                                    \''.pg_escape_string($row['text']).'\',
                                    \''.pg_escape_string($row['intro']).'\',
                                    \''.date('U').'\',
                                    \''.pg_escape_string($row['title']).'\'
                                ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $xmlWriter->addChild('id', $row['id']);
      $xmlWriter->addChild('title', stripslashes($row['title']), true);
 }

//  translate_all_articles --------------------------------------------------

  if ($_POST['cmd'] == 'translate_all_articles') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(228); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(229); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = 'false'; }

      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { 
          $target_ok = true; 
          $target = $language->name.' ('.$language->nameeng.')'; }
      }
      if (!$target_ok) { xmlerror(229); }
      
      $data = pg_query($dbconn, 'SELECT * FROM general.articles_lang WHERE language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $counter = 0;
      while ($row = pg_fetch_assoc($data)) {
          
          $go = false;
          if ($_POST['overwrite'] == 'true') {
            $data2 = pg_query($dbconn, 'DELETE FROM general.articles_lang WHERE language='.$_POST['target'].' AND article_id = '.$row['article_id']);
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            $go = true;
          } else {
            $data2 = pg_query($dbconn, 'SELECT * FROM general.articles_lang WHERE language='.$_POST['target'].' AND article_id = '.$row['article_id'].' LIMIT 1');
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            if (pg_num_rows($data2) <= 0) { $go = true; }
          }
          
          if ($go == true) {
          
              $data2 = pg_query($dbconn, 'INSERT INTO general.articles_lang (
                                          article_id,
                                          language,
                                          user_id,
                                          text,
                                          intro,
                                          title,
                                          created,
                                        ) VALUES (
                                          '.$row['article_id'].',
                                          '.$_POST['target'].',
                                          '.$_SESSION['user_id'].',
                                          \''.pg_escape_string($row['text']).'\',
                                          \''.pg_escape_string($row['intro']).'\',
                                          \''.pg_escape_string($row['title']).'\',
                                          '.date('U').'
                                        )');
              if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
              $counter++;
          }
      }
      
      $xmlWriter->addChild('target', $target);
      $xmlWriter->addChild('counter', $counter);
      
  }

//  cunnilingus --------------------------------------------------

  if ($_POST['cmd'] == 'cunnilingus') {
  
    if (!isset($_POST['tokens']) || $_POST['tokens'] == '') { xmlerror(12); }
    $tokens = explode(',', $_POST['tokens']);

//  If there's no language set, switch to default

    if (!isset($_SESSION['language'])) { setlanguage(getsetting('DEFAULT_LANGUAGE')); }

//  Get contents
    
    $filter = '';
    foreach($tokens as $token) {
      if ($filter == '') { $filter = 'WHERE ('; } else { $filter .= ' OR '; }
      if (stripos($token, '.')) {
        $token = explode('.', $token);
        $filter .= '(text_groups.token=\''.pg_escape_string($token[0]).'\' AND texts.token=\''.pg_escape_string($token[1]).'\')';
      } else { 
        $filter .= '(text_groups.token=\'\' AND texts.token=\''.pg_escape_string($token).'\')';
      }
    }
    
    $filter .= ')';
    
    $data = pg_query($dbconn, 'SELECT
                                        texts.id AS id,
                                        texts.token AS token,
                                        texts.groupid AS groupid,
                                        texts_lang.language AS language,
                                        texts_lang.text AS text,
                                        text_groups.token AS group_token
                                        
                                   FROM general.texts AS texts
                                   LEFT JOIN general.texts_lang AS texts_lang ON texts_lang.text_id = texts.id
                                   LEFT JOIN general.text_groups AS text_groups ON text_groups.id = texts.groupid
                                   '.$filter.' AND texts_lang.language = '.$_SESSION['language'].'
                                   ORDER BY group_token, texts.token');

    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('total_found', pg_num_rows($data));
    $xmlWriter->addChild('tokens', count($tokens));
    $xmlWriter->addChild('sendback', getsetting('ALLOW_AUTOADD'));

    $found[] = array();
    while ($row = pg_fetch_assoc($data)) {
      array_push($found, $row);
    }
    
    foreach($tokens as $token) {

        $xmlWriter->startElement('text');
        $xmlWriter->addChild('token', $token);

        if (stripos($token, '.')) { 
          $token = explode('.', $token);
          $group = $token[0];
          $token = $token[1];
        } else {
          $group = '';
        }

        $text = $group.'.'.$token;
        foreach ($found as $row) {
          if (stripslashes($row['token']) == $token && stripslashes($row['group_token']) == $group) {
            $text = ltrim(rtrim(stripslashes($row['text'])));
          }
        }
        
        $xmlWriter->addChild('text', $text, true);
        $xmlWriter->endElement();
    }
 }

//  anilingus --------------------------------------------------

  if ($_POST['cmd'] == 'anilingus') {
  
    if (!isset($_POST['sendback']) || $_POST['sendback'] == '') { xmldie('STOP'); }

    $output = '';
    $sendback = explode('###ANILINGUS###', $_POST['sendback']);
    foreach ($sendback as $row) {
      $row = explode('|', $row);
      
      $text = pg_escape_string($row[1]);
      $token = $row[0];
      if (stripos($token, '.')) { 
        $token = explode('.', $token);
        $group = $token[0];
        $token = $token[1];
      } else {
        $group = '';
      }
      
//  check if group exists; get id if yes, create if not

      if ($group != '') {
        $data = pg_query('SELECT * FROM general.text_groups WHERE token = \''.$group.'\' LIMIT 1');
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data) == 0) {
          $data = pg_query('INSERT INTO general.text_groups (
                                                              token, 
                                                              info
                                                            ) VALUES (
                                                              \''.$group.'\',
                                                              \'Autogenerated by pgCMS\'
                                                            ) RETURNING id');
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        }
        $row = pg_fetch_assoc($data);
        $group_id = $row['id'];
      } else {
        $group_id = 0;
      }

//  insert text into general.texts

      $data = pg_query($dbconn, 'SELECT * FROM general.texts WHERE token=\''.$token.'\' AND groupid='.$group_id.' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) {
          $data = pg_query($dbconn, 'INSERT INTO general.texts (
                                                                  token,
                                                                  groupid
                                                               ) VALUES (
                                                                  \''.$token.'\',
                                                                  '.$group_id.'
                                                               ) RETURNING id');
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      }
      $row = pg_fetch_assoc($data);
      $text_id = $row['id'];
      
//  insert text into general.texts_lang in all selectable languages

      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language->selectable == 1) {

            $data = pg_query($dbconn, 'SELECT * FROM general.texts_lang WHERE text_id='.$text_id.' AND language='.$language['id'].' LIMIT 1');
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            if (pg_num_rows($data) <= 0) {

            $data = pg_query($dbconn, 'INSERT INTO general.texts_lang (
                                                                        text_id,
                                                                        language,
                                                                        user_id,
                                                                        text
                                                                      ) VALUES (
                                                                        '.$text_id.',
                                                                        '.$language['id'].',
                                                                        0,
                                                                        \''.$text.'\'
                                                                      )');
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

          }
        }
      }
    }
  }

//  fellatio --------------------------------------------------

  if ($_POST['cmd'] == 'fellatio') {
  
    if (!isset($_POST['tokens']) || $_POST['tokens'] == '') { xmlerror(21); }

//  If there's no language set, switch to default

    if (!isset($_SESSION['language'])) { setlanguage(getsetting('DEFAULT_LANGUAGE')); }

    $tokens = explode('###FELLATIO###', $_POST['tokens']);

//  Get contents
    
    $filter = '';
    foreach($tokens as $token) {
      if ($filter == '') { $filter = 'WHERE ('; } else { $filter .= ' OR '; }
      if (stripos($token, '.')) {
        $token = explode('.', $token);
        $groupid = $token[0];
        if (stripos($token[1], ',')) {
          $temp = explode(',', $token[1]);
          $token = $temp[0];
        } else {
          $token = $token[1];
        }
        $filter .= '(text_groups.token=\''.pg_escape_string($groupid).'\' AND articles.token=\''.pg_escape_string($token).'\')';
      } else { 
        $filter .= '(text_groups.token=\'\' AND articles.token=\''.pg_escape_string($token).'\')';
      }
    }
    $filter .= ')';

    $data = pg_query($dbconn, 'SELECT
                                        articles.id AS id,
                                        articles.token AS token,
                                        articles.status AS status,
                                        articles.groupid AS groupid,
                                        articles.info AS info,
                                        articles.created AS created,
                                        
                                        articles_lang.id AS lang_id,
                                        articles_lang.language AS lang_language,
                                        articles_lang.text AS lang_text,
                                        articles_lang.user_id AS user_id,
                                        articles_lang.created AS lang_created,
                                        articles_lang.intro AS intro,
                                        articles_lang.title AS title,
                                        
                                        text_groups.token AS group_token,
                                        text_groups.info AS group_info,
                                        
                                        users.id AS user_id,
                                        users.name AS user_name

                                   FROM general.articles AS articles
                                   LEFT JOIN general.articles_lang AS articles_lang ON articles_lang.article_id = articles.id
                                   LEFT JOIN general.text_groups AS text_groups ON text_groups.id = articles.groupid
                                   LEFT JOIN general.users AS users ON users.id = articles_lang.user_id
                                   '.$filter.' AND articles_lang.language = '.$_SESSION['language']);
                                   
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('sendback', getsetting('ALLOW_AUTOADD'));

    $found[] = array();
    while ($row = pg_fetch_assoc($data)) {
      array_push($found, $row);
    }
    
    foreach($tokens as $token) {

        $xmlWriter->startElement('article');
        $xmlWriter->addChild('token', $token);

        if (stripos($token, '.')) { 
          $token = explode('.', $token);
          $group = $token[0];
          $token = $token[1];
          if (stripos($token, ',')) {
              $temp = explode(',', $token);
              $token = $temp[0];
              $part = $temp[1];
          }
          
        } else {
          $group = '';
        }
        
        if ($part != 'body' && $part != 'title' && $part != 'intro') { $part = 'body'; }

        $text = $group.'.'.$token.','.$part;
        foreach ($found as $row) {
          if (stripslashes($row['token']) == $token && stripslashes($row['group_token']) == $group) {
            if ($part == 'body') { $text = ltrim(rtrim(stripslashes($row['lang_text']))); }
            if ($part == 'title') { $text = ltrim(rtrim(stripslashes($row['title']))); }
            if ($part == 'intro') { $text = ltrim(rtrim(stripslashes($row['intro']))); }
          }
        }
        
        $text = str_ireplace('../', '', $text);

        if (strlen($text) > $chunksize) {
          $xmlWriter->startElement('text');
          for ($t=0; $t<strlen($text); $t+=$chunksize) {
            $node2->addChild('chunk', substr($text, $t, $chunksize), true);
          }
          $xmlWriter->endElement();
        } else {
          $xmlWriter->addChild('text', $text, true);
        }

    $xmlWriter->endElement();

    }
 }

//  blogroll --------------------------------------------------

if ($_POST['cmd'] == 'blogroll') {
  
  if (!isset($_POST['group']) || strlen($_POST['group']) < 3) { xmlerror(46); }
  if (!isset($_POST['order']) || strtolower($_POST['order']) != 'desc') { $_POST['order'] = ''; }

  if (!isset($_POST['offset']) || !is_numeric($_POST['offset'])) { $offset = 0; }
  else { $offset = $_POST['offset']; }
  $xmlWriter->addChild('offset', $offset);

  if (!isset($_POST['limit']) || !is_numeric($_POST['limit']) || $_POST['limit'] == 0) { $maxcount = getsetting('LIST_COUNT'); }
  else { $maxcount = $_POST['limit']; }
  if ($_POST['limit'] == '-1') { $maxcount = '2^62'; }
  $xmlWriter->addChild('maxcount', $maxcount);
  
        $data = pg_query($dbconn, 'SELECT
                                        articles.id AS id,
                                        articles.token AS token,
                                        articles.status AS status,
                                        articles.groupid AS groupid,
                                        articles.info AS info,
                                        articles.created AS created,
                                        
                                        articles_lang.id AS lang_id,
                                        articles_lang.language AS lang_language,
                                        articles_lang.text AS lang_text,
                                        articles_lang.user_id AS user_id,
                                        articles_lang.created AS lang_created,
                                        articles_lang.intro AS intro,
                                        articles_lang.title AS title,
                                        
                                        groups.token AS group_token,
                                        groups.info AS group_info,
                                        
                                        users.id AS user_id,
                                        users.name AS user_name

                                   FROM general.articles AS articles
                                   LEFT JOIN general.articles_lang AS articles_lang ON articles_lang.article_id = articles.id
                                   LEFT JOIN general.text_groups AS groups ON groups.id = articles.groupid
                                   LEFT JOIN general.users AS users ON users.id = articles_lang.user_id
                             
                             WHERE articles_lang.language = '.$_SESSION['language'].' AND status=0 AND groups.token = \''.$_POST['group'].'\'
                             ORDER BY articles.created '.$_POST['order'].'
                             OFFSET '.$offset.' 
                             LIMIT '.$maxcount);
                             
  if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
  if (pg_num_rows($data) <= 0) { xmlerror(50); }
  $languages = simplexml_load_file('../xmldata/languages.xml');
  
  while ($row = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('article');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('token', stripslashes($row['token']));
          $xmlWriter->addChild('language', stripslashes($row['lang_language']));
          $xmlWriter->addChild('user_id', stripslashes($row['user_id']));
          $xmlWriter->addChild('group_id', stripslashes($row['groupid']));
          $xmlWriter->addChild('group_token', stripslashes($row['group_token']));
          $xmlWriter->addChild('created', stripslashes($row['created']));
          $xmlWriter->addChild('created_formatted', date('Y.m.d. H:i', $row['created'] / 1000));
          $xmlWriter->addChild('translated', stripslashes($row['lang_created']));
          $xmlWriter->addChild('translated_formatted', date('Y.m.d. H:i', $row['lang_created'] / 1000));

          $text = stripslashes($row['lang_text']);
          if (strlen($text) > $chunksize) {
            $xmlWriter->startElement('text');
            for ($t=0; $t<strlen($text); $t+=$chunksize) {
              $node2->addChild('chunk', substr($text, $t, (integer)$chunksize), true);
            }
            $xmlWriter->endElement();
          } else {
            $xmlWriter->addChild('text', $text, true);
          }
          
          $xmlWriter->addChild('title', stripslashes($row['title']), true);
          $xmlWriter->addChild('user_name', stripslashes($row['user_name']), true);

          foreach($languages as $language) {
            if ((string)$language['id'] == $row['lang_language']) {
              $xmlWriter->addChild('language_name', $language->name);
              $xmlWriter->addChild('language_nameeng', $language->nameeng);
           }
         }
         
         $xmlWriter->endElement();
  
  }
}

require('api.end.php');

?>