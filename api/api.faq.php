<?php
  require_once('api.start.php');
  $chunksize = (integer)getsetting('XML_NODE_CHUNKSIZE');

//  getquestion --------------------------------------------------

  if ($_POST['cmd'] == 'getquestion') {
  
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6)) { xmlerror(98); }
  
    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }

    $filter = 'AND ';
    if (isset($_POST['id']) && $_POST['id'] != '') { $filter .= 'faq.id='.$_POST['id']; }
    
    if (isset($_POST['search']) && $_POST['search'] != '' && strlen($_POST['search']) > 2) { 

//  Search - to be implemented!

    }

    if ($filter == 'AND ') { $filter = ''; }
            
    $maxcount = getsetting('LIST_COUNT');
    $xmlWriter->addChild('maxcount', $maxcount);

    $data = pg_query($dbconn, 'SELECT faq.*, 
                                      faq_lang.*,
                                      faq_groups.*
                                          
                                   FROM general.faq AS faq
                                   LEFT JOIN general.faq_lang AS faq_lang ON faq_lang.faq_id = faq.id
                                   LEFT JOIN general.faq_groups AS faq_groups ON faq_groups.id = faq.group_id
                                   WHERE faq_lang.language = '.$_SESSION['language'].'
                                   '.$filter);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $xmlWriter->addChild('total', pg_num_rows($data));
    
    $data = pg_query($dbconn, 'SELECT
                                        faq.id AS id,
                                        faq.group_id AS group_id,
                                        faq.visible AS visible,
                                        faq.priority AS priority,
                                        
                                        faq_lang.id AS lang_id,
                                        faq_lang.language AS lang_language,
                                        faq_lang.question AS lang_question,
                                        faq_lang.user_id AS user_id,
                                        faq_lang.created AS lang_created,
                                        faq_lang.text AS text,
                                        
                                        faq_groups.priority AS group_priority,
                                        faq_groups_lang.name AS group_name,
                                        
                                        users.name AS user_name

                                   FROM general.faq AS faq
                                   LEFT JOIN general.faq_lang AS faq_lang ON faq_lang.faq_id = faq.id
                                   LEFT JOIN general.faq_groups AS faq_groups ON faq_groups.id = faq.group_id
                                   LEFT JOIN general.faq_groups_lang AS faq_groups_lang ON faq_groups_lang.group_id = faq.group_id AND faq_groups_lang.language = '.$_SESSION['language'].'
                                   LEFT JOIN general.users AS users ON users.id = faq_lang.user_id
                                   WHERE faq_lang.language = '.$_SESSION['language'].'
                                   '.$filter.'
                                   ORDER BY faq_groups.priority, priority
                                   OFFSET '.$offset.'
                                   LIMIT '.$maxcount);
                                   
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) == 0 && isset($_POST['id']) && $_POST['id'] != '') { xmlerror(98); }

    $xmlWriter->addChild('offset', $offset);
    $languages = simplexml_load_file('../xmldata/languages.xml');

        while ($row = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('faq');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('language', stripslashes($row['lang_language']));
          $xmlWriter->addChild('user_id', stripslashes($row['user_id']));
          $xmlWriter->addChild('group_id', stripslashes($row['group_id']));
          $xmlWriter->addChild('created', stripslashes($row['lang_created']));
          $xmlWriter->addChild('group_priority', stripslashes($row['group_priority']));
          $xmlWriter->addChild('priority', stripslashes($row['priority']));
          $xmlWriter->addChild('created_formatted', date('Y.m.d. H:i', $row['lang_created']));
          if ($row['visible'] == 't') { $xmlWriter->addChild('visible', 1); } else { $xmlWriter->addChild('visible', 0); }

          $text = stripslashes($row['text']);
          if (strlen($text) > $chunksize) {
            $xmlWriter->startElement('text');
            for ($t=0; $t<strlen($text); $t+=$chunksize) {
              $xmlWriter->addChild('chunk', substr($text, $t, $chunksize), true);
            }
            $xmlWriter->endElement();
          } else {
            $xmlWriter->addChild('text', $text, true);
          }

          $xmlWriter->addChild('question', stripslashes($row['lang_question']), true);
          $xmlWriter->addChild('user_name', stripslashes($row['user_name']), true);
          $xmlWriter->addChild('group_name', stripslashes($row['group_name']), true);

          foreach($languages as $language) {
            if ((string)$language['id'] == $row['lang_language']) {
              $xmlWriter->addChild('language_name', $language->name);
              $xmlWriter->addChild('language_nameeng', $language->nameeng);
           }
         }

//  Add search words

        $data2 = pg_query($dbconn, 'SELECT * FROM general.faq_searchwords WHERE faq_id = '.$row['id'].' AND language='.$_SESSION['language']);
        if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data2) > 0) {
          $xmlWriter->startElement('searchwords');
          while($row2 = pg_fetch_assoc($data2)) {
            $xmlWriter->addChild('searchword', stripslashes($row2['word']));
          }
          $xmlWriter->endElement();
        }
       
        $xmlWriter->endElement();
       
       }
 }

//  addquestion --------------------------------------------------

  if ($_POST['cmd'] == 'addquestion') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(99); }
    if (!isset($_POST['group_id']) || !is_numeric($_POST['group_id']) || $_POST['group_id'] == '' || strlen($_POST['group_id']) > 6) { xmlerror(100); }
    if (!isset($_POST['text']) || $_POST['text'] == '') { xmlerror(101); }
    if (!isset($_POST['question']) || $_POST['question'] == '') { xmlerror(102); }
    if (!isset($_POST['priority']) || !is_numeric($_POST['priority']) || $_POST['priority'] == '' || strlen($_POST['priority']) > 6) { xmlerror(103); }
    if ($_POST['visible'] != 'true') { $_POST['visible'] = 'false'; }

 }

//  addquestion - new question --------------------------------------------------

  if ($_POST['cmd'] == 'addquestion' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {

    $data = pg_query($dbconn, 'INSERT INTO general.faq (
                                    group_id,
                                    visible,
                                    priority
                                 ) VALUES (
                                    \''.pg_escape_string($_POST['group_id']).'\',
                                    \''.pg_escape_string($_POST['visible']).'\',
                                    '.pg_escape_string($_POST['priority']).'
                                 ) RETURNING id');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $row = pg_fetch_assoc($data);

    $data = pg_query($dbconn, 'INSERT INTO general.faq_lang (
                                    language,
                                    faq_id,
                                    user_id,
                                    text,
                                    created,
                                    question

                                  ) VALUES (
                                      '.$_SESSION['language'].',
                                      '.$row['id'].',
                                      '.$_SESSION['user_id'].',
                                      \''.pg_escape_string($_POST['text']).'\',
                                      '.date('U').',
                                      \''.pg_escape_string($_POST['question']).'\'
                                  )');

    if (!$data) { 
       $error = pg_last_error($dbconn);
       $data = pg_query($dbconn, 'DELETE FROM general.faq WHERE id = '.$row['id']);  
       xmlerror(1000, '%errormsg%|'.$error);
    }

//  add search words
    
    if ($_POST['searchwords'] != '') {
      
      foreach(explode('###', $_POST['searchwords']) as $searchword) {
        $data = pg_query($dbconn, 'INSERT INTO general.faq_searchwords (
                                     faq_id,
                                     language,
                                     word
                                   ) VALUES (
                                     '.$row['id'].',
                                     '.$_SESSION['language'].',
                                     \''.pg_escape_string($searchword).'\'
                                   )');
        if (!$data) { 
          $error = pg_last_error($dbconn);
          $data = pg_query($dbconn, 'DELETE FROM general.faq WHERE id = '.$row['id']);  
          $data = pg_query($dbconn, 'DELETE FROM general.faq_lang WHERE faq_id = '.$row['id']);
          xmlerror(1000, '%errormsg%|'.$error);
        }                         
      }
    }
        
//  response

    $xmlWriter->addChild('id', $row['id']);

 }

//  addquestion - modify question --------------------------------------------------

  if ($_POST['cmd'] == 'addquestion' && is_numeric($_POST['id']) && $_POST['id'] != 0) {

    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6) { xmlerror(98); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.faq WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(98); }
    $faq = pg_fetch_assoc($data);
    
    $data = pg_query($dbconn, 'UPDATE general.faq SET
                                  group_id = '.$_POST['group_id'].',
                                  visible = '.$_POST['visible'].',
                                  priority = '.$_POST['priority'].'
                               WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            
    $data = pg_query($dbconn, 'UPDATE general.faq_lang SET 
                                    text = \''.pg_escape_string($_POST['text']).'\',
                                    user_id = '.$_SESSION['user_id'].',
                                    question = \''.pg_escape_string($_POST['question']).'\'
                               WHERE faq_id='.$_POST['id'].' AND language='.$_SESSION['language']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    if ($_POST['searchwords'] != '') {
      
      $data = pg_query($dbconn, 'DELETE FROM general.faq_searchwords WHERE faq_id = '.$_POST['id'].' AND language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      foreach(explode('###', $_POST['searchwords']) as $searchword) {
        $data = pg_query($dbconn, 'INSERT INTO general.faq_searchwords (
                                     faq_id,
                                     language,
                                     word
                                   ) VALUES (
                                     '.$_POST['id'].',
                                     '.$_SESSION['language'].',
                                     \''.pg_escape_string($searchword).'\'
                                   )');
        if (!$data) { xmlerror(1000, '%errormsg%|'.$error); }                         
      }
    }

    $xmlWriter->addChild('id', $_POST['id']);
 }

//  getgroup --------------------------------------------------

  if ($_POST['cmd'] == 'getgroup') {

    if (isset($_POST['id']) && $_POST['id'] != '' && (!is_numeric($_POST['id']) || $_POST['id'] < 0 || strlen($_POST['id']) > 5)) { xmlerror(80); }

    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }
    
    $filter = '';
    if (isset($_POST['id']) && $_POST['id'] != '') { $filter = 'faq_groups.id='.$_POST['id'].' AND '; }
        
    if ($error == 0) {

        if (!isset($_POST['nolimit'])) { $maxcount = getsetting('LIST_COUNT'); }
        else { $maxcount = '2^62'; }
        $xmlWriter->addChild('maxcount', $maxcount);
    
        $data = pg_query($dbconn, 'SELECT faq_groups.*
                                   FROM general.faq_groups AS faq_groups
                                   LEFT JOIN general.faq_groups_lang AS faq_groups_lang ON faq_groups_lang.group_id = faq_groups.id
                                   LEFT JOIN general.users AS users ON faq_groups_lang.user_id = users.id
                                   WHERE '.$filter.' faq_groups_lang.language = '.$_SESSION['language']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        $xmlWriter->addChild('total', pg_num_rows($data));
        $xmlWriter->addChild('offset', $offset);

        $data = pg_query($dbconn, 'SELECT
                                        faq_groups.id AS id,
                                        faq_groups.priority AS priority,
                                        faq_groups.created AS created,
                                        faq_groups.visible AS visible,
                                        
                                        faq_groups_lang.name AS name,
                                        faq_groups_lang.user_id AS user_id,
                                        users.name AS user_name
                                       
                                   FROM general.faq_groups AS faq_groups
                                   LEFT JOIN general.faq_groups_lang AS faq_groups_lang ON faq_groups_lang.group_id = faq_groups.id
                                   LEFT JOIN general.users AS users ON faq_groups_lang.user_id = users.id
                                   WHERE '.$filter.' faq_groups_lang.language = '.$_SESSION['language'].'
                                   ORDER BY priority, id DESC
                                   OFFSET '.$offset.'
                                   LIMIT '.$maxcount);
                                   
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        else while ($row = pg_fetch_assoc($data)) {
        
          $data2 = pg_query($dbconn, 'SELECT * FROM general.faq WHERE group_id = '.$row['id']);
          if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

          $xmlWriter->startElement('faq_group');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('priority', $row['priority']);
          $xmlWriter->addChild('created', $row['created']);
          $xmlWriter->addChild('created_formatted', date('Y.m.d. H:i', $row['created'] / 1000));
          $xmlWriter->addChild('name', stripslashes($row['name']), true);
          $xmlWriter->addChild('user_id', $row['user_id']);
          $xmlWriter->addChild('user_name', stripslashes($row['user_name']), true);
          $xmlWriter->addChild('answers', pg_num_rows($data2));
          if ($row['visible'] == 't') { $xmlWriter->addChild('visible', 1); } else { $xmlWriter->addChild('visible', 0); }

          $xmlWriter->endElement();

       }
   }
 }

//  addgroup --------------------------------------------------

  if ($_POST['cmd'] == 'addgroup') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(81); }
    if (!isset($_POST['priority']) || !is_numeric($_POST['priority']) || $_POST['priority'] < 0 || strlen($_POST['priority']) > 6) { xmlerror(83); }
    if (!isset($_POST['name']) || strlen($_POST['name']) < 3) { xmlerror(87); }
    if ($_POST['visible'] != 'true') { $_POST['visible'] = 'false'; }
    
 }

//  addgroup - new group --------------------------------------------------

  if ($_POST['cmd'] == 'addgroup' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0)) {
    
//  availability check

      $data = pg_query($dbconn, 'INSERT INTO general.faq_groups (
                                    priority,
                                    created,
                                    visible
                                 ) VALUES (
                                    '.$_POST['priority'].',
                                    '.date('U').',
                                    '.$_POST['visible'].'
                                 ) RETURNING id');

      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      $row = pg_fetch_assoc($data);
      
      $data = pg_query($dbconn, 'INSERT INTO general.faq_groups_lang (
                                    group_id,
                                    language,
                                    name,
                                    user_id
                                 ) VALUES (
                                    '.$row['id'].',
                                    '.$_SESSION['language'].',
                                    \''.pg_escape_string($_POST['name']).'\',
                                    '.$_SESSION['user_id'].'
                                 )');
      
//  response

        $row = pg_fetch_assoc($data);
        $xmlWriter->addChild('id', $row['id']);
 }

//  addgroup - modify group --------------------------------------------------

  if ($_POST['cmd'] == 'addgroup' && is_numeric($_POST['id']) && $_POST['id'] != 0) {
    
    $data = pg_query($dbconn, 'SELECT * FROM general.faq_groups WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(88); }
    $group = pg_fetch_assoc($data);
        
    $data = pg_query($dbconn, 'UPDATE general.faq_groups SET
                                  priority = '.$_POST['priority'].',
                                  visible = '.$_POST['visible'].'
                               WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $data = pg_query($dbconn, 'UPDATE general.faq_groups_lang SET
                          name = \''.pg_escape_string($_POST['name']).'\',
                          user_id = '.$_SESSION['user_id'].'
                        WHERE group_id='.$_POST['id'].' AND language='.$_SESSION['language']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('id', $_POST['id']);
 }

//  deletegroup --------------------------------------------------

  if ($_POST['cmd'] == 'deletegroup') {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { xmlerror(89); $error = 89; }
    
      $data = pg_query($dbconn, 'SELECT * FROM general.faq_groups WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(89); }

      $row = pg_fetch_assoc($data);
      if ($_SESSION['user_admin'] != 1) { xmlerror(90); }

      $data = pg_query($dbconn, 'DELETE FROM general.faq_groups WHERE id='.$_POST['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      $data = pg_query($dbconn, 'DELETE FROM general.faq_groups_lang WHERE group_id='.$_POST['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      $xmlWriter->addChild('id', $_POST['id']);

      if ($_POST['deletecontent'] == 'true') {
          $xmlWriter->addChild('deletecontent', 1);
          $data = pg_query($dbconn, 'SELECT * FROM general.faq WHERE group_id='.$_POST['id']);
          while ($row = pg_fetch_assoc($data)) {
              $data2 = pg_query($dbconn, 'DELETE FROM general.faq_lang WHERE faq_id='.$row['id']);
          }
          $data = pg_query($dbconn, 'DELETE FROM general.faq WHERE group_id='.$_POST['id']);
      } else {
        $xmlWriter->addChild('deletecontent', 0);
      }
 }

//  translate_group --------------------------------------------------

  if ($_POST['cmd'] == 'translate_group') {

      if (!isset($_POST['name']) || $_POST['name'] == '' || strlen($_POST['name']) < 2) { xmlerror(91); }
      if ($_SESSION['user_admin'] != 1) { xmlerror(92); }
      if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 4) { xmlerror(93); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(94); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = false; }
      
      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { $target_ok = true; }
      }
      if (!$target_ok) { xmlerror(94); }
      
      if (!$_POST['overwrite']) {
        $data = pg_query($dbconn, 'SELECT * FROM general.faq_groups_lang WHERE group_id='.$_POST['id'].' AND language='.$_POST['target'].' LIMIT 1');
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data) > 0) { xmlerror(95); }
        $row = pg_fetch_assoc($data);
     } else {
        $data = pg_query($dbconn, 'DELETE FROM general.faq_groups_lang WHERE group_id='.$_POST['id'].' AND language='.$_POST['target']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
     }
      
      $data = pg_query($dbconn, 'INSERT INTO general.faq_groups_lang (
                                    group_id,
                                    language,
                                    user_id,
                                    name
                                ) VALUES (
                                    '.$_POST['id'].',
                                    '.$_POST['target'].',
                                    '.$_SESSION['user_id'].',
                                    \''.pg_escape_string($_POST['name']).'\'
                                ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $row = pg_fetch_assoc($data);
      $xmlWriter->addChild('id', $row['id']);
 }

//  translate_all_groups --------------------------------------------------

  if ($_POST['cmd'] == 'translate_all_groups') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(96); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(97); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = 'false'; }

      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { 
          $target_ok = true; 
          $target = $language->name.' ('.$language->nameeng.')'; }
      }
      if (!$target_ok) { xmlerror(97); }
      
      $data = pg_query($dbconn, 'SELECT * FROM general.faq_groups_lang WHERE language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $counter = 0;
      while ($row = pg_fetch_assoc($data)) {
          
          $go = false;
          if ($_POST['overwrite'] == 'true') {
            $data2 = pg_query($dbconn, 'DELETE FROM general.faq_groups_lang WHERE language='.$_POST['target'].' AND group_id = '.$row['group_id']);
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            $go = true;
          } else {
            $data2 = pg_query($dbconn, 'SELECT * FROM general.faq_groups_lang WHERE language='.$_POST['target'].' AND group_id = '.$row['group_id'].' LIMIT 1');
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            if (pg_num_rows($data2) <= 0) { $go = true; }
          }
          
          if ($go == true) {
          
              $data2 = pg_query($dbconn, 'INSERT INTO general.faq_groups_lang (
                                          group_id,
                                          language,
                                          user_id,
                                          name
                                        ) VALUES (
                                          '.$row['group_id'].',
                                          '.$_POST['target'].',
                                          '.$_SESSION['user_id'].',
                                          \''.pg_escape_string($row['name']).'\'
                                        )');
              if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
              $counter++;
          }
      }
      
      $xmlWriter->addChild('target', $target);
      $xmlWriter->addChild('counter', $counter);
  }

//  deletequestion --------------------------------------------------

  if ($_POST['cmd'] == 'deletequestion') {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { xmlerror(104); }
    if ($_SESSION['user_admin'] != 1) { xmlerror(105); }
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.faq WHERE id='.$_POST['id'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(104); }

      $row = pg_fetch_assoc($data);

      if ($_POST['deleteall'] == 'true') {
          
          $data = pg_query($dbconn, 'DELETE FROM general.faq WHERE id='.$_POST['id']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          $data = pg_query($dbconn, 'DELETE FROM general.faq_lang WHERE faq_id='.$_POST['id']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          $data = pg_query($dbconn, 'DELETE FROM general.faq_searchwords WHERE faq_id='.$_POST['id']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      } else {
          $data = pg_query($dbconn, 'DELETE FROM general.faq_lang WHERE faq_id='.$_POST['id'].' AND language='.$_SESSION['language']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          $data = pg_query($dbconn, 'DELETE FROM general.faq_searchwords WHERE faq_id='.$_POST['id'].' AND language='.$_SESSION['language']);
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      }
          
      $xmlWriter->addChild('id', $_POST['id']);

   }
 }

//  translate_all_questions --------------------------------------------------

  if ($_POST['cmd'] == 'translate_all_questions') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(106); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(107); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = 'false'; }

      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { 
          $target_ok = true; 
          $target = $language->name.' ('.$language->nameeng.')'; }
      }
      if (!$target_ok) { xmlerror(107); }
      
      $data = pg_query($dbconn, 'SELECT * FROM general.faq_lang WHERE language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $counter = 0;
      while ($row = pg_fetch_assoc($data)) {
          
          $go = false;
          if ($_POST['overwrite'] == 'true') {
            $data2 = pg_query($dbconn, 'DELETE FROM general.faq_lang WHERE language='.$_POST['target'].' AND faq_id = '.$row['faq_id']);
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            $go = true;
          } else {
            $data2 = pg_query($dbconn, 'SELECT * FROM general.faq_lang WHERE language='.$_POST['target'].' AND faq_id = '.$row['faq_id'].' LIMIT 1');
            if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            if (pg_num_rows($data2) <= 0) { $go = true; }
          }
          
          if ($go == true) {
          
              $data2 = pg_query($dbconn, 'INSERT INTO general.faq_lang (
                                          faq_id,
                                          language,
                                          user_id,
                                          text,
                                          question,
                                          created
                                        ) VALUES (
                                          '.$row['faq_id'].',
                                          '.$_POST['target'].',
                                          '.$_SESSION['user_id'].',
                                          \''.pg_escape_string($row['text']).'\',
                                          \''.pg_escape_string($row['question']).'\',
                                          '.date('U').'
                                        ) RETURNING faq_id');
              if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

              $row2 = pg_fetch_assoc($data2);

              $data3 = pg_query($dbconn, 'SELECT * FROM general.faq_searchwords WHERE faq_id = '.$row2['faq_id'].' AND language='.$_SESSION['language']);
              if (!$data3) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
              while ($row3 = pg_fetch_assoc($data3)) {
                $data4 = pg_query($dbconn, 'INSERT INTO general.faq_searchwords (
                                                                                  language,
                                                                                  faq_id,
                                                                                  word
                                                                                ) VALUES (
                                                                                  '.$_POST['target'].',
                                                                                  '.$row['faq_id'].',
                                                                                  \''.$row3['word'].'\'
                                                                                )');
                if (!$data4) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
              }

              $counter++;              
          }
      }
      
      $xmlWriter->addChild('target', $target);
      $xmlWriter->addChild('counter', $counter);
      
  }

//  translate_question --------------------------------------------------

  if ($_POST['cmd'] == 'translate_question') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(108  ); }
      if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 4) { xmlerror(109); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(110); }
      if ($_POST['overwrite'] != 'true') { $_POST['overwrite'] = false; }
      
      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { $target_ok = true; }
      }
      if (!$target_ok) { xmlerror(110); }
      
      if (!$_POST['overwrite']) {
        $data = pg_query($dbconn, 'SELECT * FROM general.faq_lang WHERE faq_id='.$_POST['id'].' AND language='.$_POST['target'].' LIMIT 1');
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        if (pg_num_rows($data) > 0) { xmlerror(111); }
      } else {
        $data = pg_query($dbconn, 'DELETE FROM general.faq_lang WHERE faq_id='.$_POST['id'].' AND language='.$_POST['target']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      }

      $data = pg_query($dbconn, 'SELECT * FROM general.faq_lang WHERE faq_id='.$_POST['id'].' AND language='.$_SESSION['language'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      $row = pg_fetch_assoc($data);
      
      $data = pg_query($dbconn, 'INSERT INTO general.faq_lang (
                                    faq_id,
                                    language,
                                    user_id,
                                    text,
                                    created,
                                    question
                                ) VALUES (
                                    '.$_POST['id'].',
                                    '.$_POST['target'].',
                                    '.$_SESSION['user_id'].',
                                    \''.pg_escape_string($row['text']).'\',
                                    \''.date('U').'\',
                                    \''.pg_escape_string($row['question']).'\'
                                ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      $row = pg_fetch_assoc($data);

      $data = pg_query($dbconn, 'SELECT * FROM general.faq_searchwords WHERE faq_id = '.$_POST['id'].' AND language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      while ($row = pg_fetch_assoc($data)) {
        $data2 = pg_query($dbconn, 'INSERT INTO general.faq_searchwords (
                                                                          language,
                                                                          faq_id,
                                                                          word
                                                                        ) VALUES (
                                                                          '.$_POST['target'].',
                                                                          '.$row['faq_id'].',
                                                                          \''.$row['word'].'\'
                                                                        )');
        if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      }
      
      $xmlWriter->addChild('id', $row['id']);
 }


/*

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
            if (pg_num_rows($data2) > 0) { $go = false; }
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
*/

require('api.end.php');

?>