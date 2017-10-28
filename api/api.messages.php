<?php
  require_once('api.start.php');

//  findfaq & sendmessage check --------------------------------------------------

  if ($_POST['cmd'] == 'findfaq' || $_POST['cmd'] == 'sendmessage') {
    if (!isset($_POST['captcha']) || $_POST['captcha'] == '') { xmlerror(22); }
    if (!isset($_POST['name']) || strlen($_POST['name']) < 3) { xmlerror(302); } 
    if (!isset($_POST['text']) || strlen($_POST['text']) < 15) { xmlerror(303); } 
    if ((!isset($_POST['email']) || strlen($_POST['email']) < 5 || substr_count($_POST['email'], '.') == 0 || substr_count($_POST['email'], '@') == 0) && $error == 0) { xmlerror(304); }
  }

//  findfaq --------------------------------------------------

  if ($_POST['cmd'] == 'findfaq') {
  
    if (getsetting('MESSAGE_FAQ_CHECK') != 1) {
      $xmlWriter->addChild('questions', 0);
    } else {
    
      $words = preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', pg_escape_string($_POST['text']), -1, PREG_SPLIT_NO_EMPTY);
      $filter = '';
      foreach ($words as $word) {
        if (strlen($word) > 3) {
          if ($filter != '') $filter .= ' OR ';
          $filter .= 'word ILIKE \'%'.pg_escape_string(strtolower($word)).'%\'';
        }
      }
      
      $data = pg_query($dbconn, 'SELECT
                                      faq_searchwords.*,
                                      faq_lang.question,
                                      faq_lang.text
                                 FROM general.faq_searchwords AS faq_searchwords
                                 LEFT JOIN general.faq_lang AS faq_lang ON faq_lang.faq_id = faq_searchwords.faq_id AND faq_lang.language = '.$_SESSION['language'].'
                                 LEFT JOIN general.faq AS faq ON faq.id = faq_searchwords.faq_id
                                 WHERE faq.visible=TRUE AND faq_searchwords.language='.$_SESSION['language'].' AND ('.$filter.')');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $found = [];
      while ($row = pg_fetch_assoc($data)) {
          $index = -1;
          $t = 0;
          foreach($found as $founditem) {
            if ($founditem[0] == $row['faq_id']) { $index = $t; break; }
            else $t++;
          }
      
          if ($index == -1) {
            array_push($found, [$row['faq_id'], 1, stripslashes($row['question']), stripslashes($row['word']), stripslashes($row['text'])]);
          } else {
            $found[$index][1] = $found[$index][1]+1;
            $found[$index][3] .= ', '.stripslashes($row['word']);
          }
      }
      
      $xmlWriter->addChild('found', count($found));

//  order array

      $ordered_found = [];

      while (count($ordered_found) < count($found)) {
        $largest = -1;
        for ($t=0; $t<count($found); $t++) {
          if ($found[$t][1] > $found[$largest][1] && $found[$t][1] != 0) $largest = $t;
        }
        array_push($ordered_found, $found[$largest]);
        $found[$largest] = 0;
      }
      
//  xml output
      
      foreach ($ordered_found as $founditem) {
        $xmlWriter->startElement('faq');
        $xmlWriter->addChild('id', $founditem[0]);
        $xmlWriter->addChild('question', $founditem[2], true);
        $xmlWriter->addChild('matches', $founditem[1]);
        $xmlWriter->addChild('words', $founditem[3]);
        $xmlWriter->addChild('text', $founditem[4], true);
        $xmlWriter->endElement();
      }
    }
  }

//  setunread --------------------------------------------------

  if ($_POST['cmd'] == 'setunread') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(257); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || strlen($_POST['id']) > 6) { xmlerror(258); }
    
    $data = pg_query($dbconn, 'UPDATE general.messages SET unread=FALSE WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
  }

//  setreplied --------------------------------------------------

  if ($_POST['cmd'] == 'setreplied') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(305); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || strlen($_POST['id']) > 6) { xmlerror(306); }
    if (!isset($_POST['replied'])) { xmlerror(307); }
    if ($_POST['replied'] != 'true') { $_POST['replied'] = 'false'; }
    
    $data = pg_query($dbconn, 'UPDATE general.messages SET replied='.$_POST['replied'].' WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
  }

//  getmessage --------------------------------------------------

  if ($_POST['cmd'] == 'getmessage') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(251); }
    
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || strlen($_POST['id']) > 6)) { xmlerror(252); }
    if (isset($_POST['year']) && (!is_numeric($_POST['year']) || strlen($_POST['year']) != 4)) { xmlerror(253); }
    if (isset($_POST['month']) && (!is_numeric($_POST['month']) || strlen($_POST['month']) > 2 || $_POST['month'] < 1 || $_POST['month'] > 12)) { xmlerror(254); }
    if (isset($_POST['day']) && (!is_numeric($_POST['day']) || strlen($_POST['day']) > 2 || $_POST['day'] < 0 || $_POST['day'] > 31)) { xmlerror(255); }
    if (isset($_POST['folder']) && (!is_numeric($_POST['folder']) || strlen($_POST['folder']) > 6)) { xmlerror(256); }
    if (isset($_POST['search']) && strlen($_POST['search']) < 3 && $_POST['search'] != '') { xmlerror(326); }
    if (isset($_POST['unread']) && $_POST['unread'] != 'true') { $_POST['unread'] = 'false'; }
  
    $filter = '';
    
    if (isset($_POST['year']) && isset($_POST['month'])) {
      if ($_POST['day'] != 0) { $filter = 'WHERE date >= '.mktime(0, 0, 0, $_POST['month'], $_POST['day'], $_POST['year']).' AND date <= '.mktime(23, 59, 59, $_POST['month'], $_POST['day'], $_POST['year']); }
      else { 
        $maxday = 31;
        if ($_POST['month'] == 4 || $_POST['month'] == 6 || $_POST['month'] == 9 || $_POST['month'] == 11) { $maxday = 30; }
        if ($_POST['month'] == 2 && $_POST['year'] % 4 == 0) { $maxday = 29; }
        if ($_POST['month'] == 2 && $_POST['year'] % 4 != 0) { $maxday = 28; }
        $filter = 'WHERE date >= '.mktime(0, 0, 0, $_POST['month'], 1, $_POST['year']).' AND date <= '.mktime(23, 59, 59, $_POST['month'], $maxday, $_POST['year']); }
    }

    if (isset($_POST['folder']) && $_POST['folder'] != 0) {
      if ($filter != '') { $filter .= ' AND '; } else { $filter = 'WHERE '; }
      $filter .= 'folderid = '.$_POST['folder'];
    }

    if (isset($_POST['search']) && $_POST['search'] != '') {
      $filter = 'WHERE email ILIKE \'%'.pg_escape_string($_POST['search']).'%\' OR 
                       name ILIKE \'%'.pg_escape_string($_POST['search']).'%\' OR 
                       text ILIKE \'%'.pg_escape_string($_POST['search']).'%\'';
    }
    
    if (isset($_POST['id'])) { $filter = 'WHERE id='.$_POST['id']; }
    if ($_POST['unread'] == 'true') { $filter = 'WHERE unread=TRUE'; }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages '.$filter.' ORDER BY date DESC');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $xmlWriter->addChild('messages', pg_num_rows($data));
    
    while ($row = pg_fetch_assoc($data)) {

      $xmlWriter->addElement('message');

      $xmlWriter->addChild('id', $row['id']);
      $xmlWriter->addChild('email', stripslashes($row['email']));
      $xmlWriter->addChild('phone', stripslashes($row['phone']));
      $xmlWriter->addChild('name', stripslashes($row['name']));
      $xmlWriter->addChild('company', stripslashes($row['company']));
      $xmlWriter->addChild('date', $row['date']);
      $xmlWriter->addChild('date_formatted', date($_SESSION['language_date'].' H:i:s', $row['date']));
      $xmlWriter->addChild('date_formatted_short', date($_SESSION['language_date'], $row['date']));
      $xmlWriter->addChild('language', $row['language']);
      $xmlWriter->addChild('unread', $row['unread']);
      $xmlWriter->addChild('folderid', $row['folderid']);
      $xmlWriter->addChild('replied', $row['replied']);
      $xmlWriter->addChild('ip', $row['ip']);
      $xmlWriter->addChild('text', stripslashes($row['text']), true);

      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $row['language']) {
          $xmlWriter->addChild('language_name', $language->name);
          $xmlWriter->addChild('language_nameeng', $language->nameeng);
          $xmlWriter->addChild('language_flag', $language->flag);
        }
      }
      
      $xmlWriter->endElement();
      
    }
  }

//  deletemessage --------------------------------------------------

  if ($_POST['cmd'] == 'deletemessage') {
 
    if ($_SESSION['user_admin'] != 1) { xmlerror(295); }
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) && $error == 0) { xmlerror(296); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(296); }
    $row = pg_fetch_assoc($data);
      
    $data = pg_query($dbconn, 'DELETE FROM general.messages WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('id', $_POST['id']);
 }

//  modifymessage --------------------------------------------------

  if ($_POST['cmd'] == 'modifymessage') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(297); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 6) { xmlerror(298); }
    if (!isset($_POST['name']) || strlen($_POST['name']) < 2) { xmlerror(299); } 
    if (!isset($_POST['text'])) { xmlerror(300); } 
    if ((!isset($_POST['email']) || strlen($_POST['email']) < 5 || substr_count($_POST['email'], '.') == 0 || substr_count($_POST['email'], '@') == 0) && $error == 0) { xmlerror(301); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(298); }
                
    $data = pg_query($dbconn, 'UPDATE general.messages SET 
                                    name = \''.pg_escape_string($_POST['name']).'\',
                                    phone = \''.pg_escape_string($_POST['phone']).'\',
                                    company = \''.pg_escape_string($_POST['company']).'\',
                                    email = \''.pg_escape_string($_POST['email']).'\',
                                    text = \''.pg_escape_string(preg_replace('/\r|\n/', '', nl2br(strip_tags($_POST['text'])))).'\'
                               WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }

    $xmlWriter->addChild('id', $_POST['id']);
  }

//  sendmessage --------------------------------------------------

  if ($_POST['cmd'] == 'sendmessage') {

//  input validation happens in findfaq
  
    $data = pg_query($dbconn, 'INSERT INTO general.messages (
                                                              name,
                                                              email,
                                                              text,
                                                              phone,
                                                              company,
                                                              language,
                                                              date,
                                                              ip
                                                            ) VALUES (
                                                              \''.pg_escape_string($_POST['name']).'\',
                                                              \''.pg_escape_string($_POST['email']).'\',
                                                              \''.pg_escape_string(preg_replace('/\r|\n/', '', nl2br(strip_tags($_POST['text'])))).'\',
                                                              \''.pg_escape_string($_POST['phone']).'\',
                                                              \''.pg_escape_string($_POST['company']).'\',
                                                              '.$_SESSION['language'].',
                                                              '.date('U').',
                                                              \''.$_SERVER['HTTP_X_FORWARDED_FOR'].'\'
                                                            ) RETURNING id');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
    $row = pg_fetch_assoc($data);

    $xmlWriter->addChild('id', $_POST['id']);
  }

//  addfolder --------------------------------------------------

  if ($_POST['cmd'] == 'addfolder') {
    if ($_SESSION['user_admin'] != 1 && $error == 0) { xmlerror(245); }
    if (!isset($_POST['title']) || strlen($_POST['title']) < 3) { xmlerror(246); }
  }

//  addfolder - new folder --------------------------------------------------

  if ($_POST['cmd'] == 'addfolder' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0) && $error == 0) {
    
//  availability check

      $data = pg_query($dbconn, 'INSERT INTO general.messagefolders (
                                    title,
                                    created
                                 ) VALUES (
                                    \''.pg_escape_string($_POST['title']).'\',
                                    '.date('U').'
                                 ) RETURNING id');

      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

//  response

      $row = pg_fetch_assoc($data);
      
      $xmlWriter->startElement('addfolder');
      $xmlWriter->addChild('id', $row['id']);
      $xmlWriter->addChild('title', $_POST['title']);
      $xmlWriter->endElement();
  }

//  addfolder - modify folder --------------------------------------------------

  if ($_POST['cmd'] == 'addfolder' && is_numeric($_POST['id']) && $_POST['id'] != 0) {
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messagefolders WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(247); $error = 247; }
    $folder = pg_fetch_assoc($data);
                
    $data = pg_query($dbconn, 'UPDATE general.messagefolders SET title = \''.pg_escape_string($_POST['title']).'\' WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }

    $xmlWriter->addChild('id', $_POST['id']);
    $xmlWriter->addChild('title', $_POST['title']);
  }

//  getfolder --------------------------------------------------

  if ($_POST['cmd'] == 'getfolder' && $error == 0) {

    if (isset($_POST['id']) && $_POST['id'] != '' && (!is_numeric($_POST['id']) || $_POST['id'] < 0 || strlen($_POST['id']) > 5)) { xmlerror(248); }

    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }
    
    $filter = '';
    if (isset($_POST['id']) && $_POST['id'] != '') { $filter = 'WHERE messagefolders.id='.$_POST['id']; }
        
    if ($error == 0) {

        if (!isset($_POST['nolimit'])) { $maxcount = getsetting('LIST_COUNT'); }
        else { $maxcount = '2^62'; }
        $xmlWriter->addChild('maxcount', $maxcount);
    
        $data = pg_query($dbconn, 'SELECT * FROM general.messagefolders '.$filter);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        $xmlWriter->addChild('total', pg_num_rows($data));
        $xmlWriter->addChild('offset', $offset);

        $data = pg_query($dbconn, 'SELECT 
                                      messagefolders.*,
                                      COUNT(messages.id) AS id_count
                                      
                                   FROM general.messagefolders AS messagefolders
                                   LEFT JOIN general.messages AS messages ON messages.folderid = messagefolders.id
                                   '.$filter.'
                                   GROUP BY messagefolders.id, messagefolders.title, messagefolders.created
                                   ORDER BY created DESC, title 
                                   OFFSET '.$offset.'
                                   LIMIT '.$maxcount);
                                   
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        while ($row = pg_fetch_assoc($data)) {
        
          $xmlWriter->startElement('folder');
          $xmlWriter->addChild('id', $row['id']); 
          $xmlWriter->addChild('created', $row['created']); 
          $xmlWriter->addChild('created_formatted', date('Y-m-d H:i', $row['created'])); 
          $xmlWriter->addChild('title', stripslashes($row['title']));
          $xmlWriter->addChild('messages', $row['id_count']);
          $xmlWriter->endElement();
       }
   }
 }

//  deletefolder --------------------------------------------------

  if ($_POST['cmd'] == 'deletefolder' && $error == 0) {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) && $error == 0) { xmlerror(249); $error = 249; }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messagefolders WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(249); }
    $row = pg_fetch_assoc($data);
    if ($_SESSION['user_admin'] != 1) { xmlerror(250); $error = 250; }
      
    $data = pg_query($dbconn, 'DELETE FROM general.messagefolders WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    $xmlWriter->addChild('id', $_POST['id']);

    if ($_POST['deletecontent'] == 'true') {
        $xmlWriter->addChild('deletecontent', 1);
        $data = pg_query($dbconn, 'DELETE FROM general.messages WHERE folderid='.$_POST['id']);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    } else {
        $xmlWriter->addChild('deletecontent', 0);
    }
 }

//  getautoreply --------------------------------------------------

  if ($_POST['cmd'] == 'getautoreply') {
  
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6)) { xmlerror(278); }
  
    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }

    $filter = '';
    if (isset($_POST['id']) && $_POST['id'] != '') { $filter = ' AND id='.$_POST['id']; }

    if (!isset($_POST['nolimit'])) { $maxcount = getsetting('LIST_COUNT'); }
    else { $maxcount = '2^62'; }
    $xmlWriter->addChild('maxcount', $maxcount);

    $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply WHERE language = '.$_SESSION['language'].' '.$filter);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $xmlWriter->addChild('total', pg_num_rows($data));
    $xmlWriter->addChild('offset', $offset);
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply
                                   WHERE language = '.$_SESSION['language'].'
                                   '.$filter.'
                                   ORDER BY id DESC
                                   OFFSET '.$offset.'
                                   LIMIT '.$maxcount);
                                   
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

    while ($row = pg_fetch_assoc($data)) {
        $xmlWriter->startElement('autoreply');
        $xmlWriter->addChild('id', $row['id']); 
        $xmlWriter->addChild('subject', stripslashes($row['subject']));
        $xmlWriter->addChild('info', stripslashes($row['info']));
        $xmlWriter->addChild('language', stripslashes($row['language']));
        $xmlWriter->addChild('body', stripslashes($row['body']), true);
        $xmlWriter->endElement();
    }
 }

//  addautoreply --------------------------------------------------

  if ($_POST['cmd'] == 'addautoreply') {

    if ($_SESSION['user_admin'] != 1) { xmlerror(280); }
    if (!isset($_POST['body']) || $_POST['body'] == '') { xmlerror(281); }
    if (!isset($_POST['subject']) || strlen($_POST['subject']) < 5) { xmlerror(282); }
    if (!isset($_POST['info']) || strlen($_POST['info']) < 5) { xmlerror(283); }
 }

//  addautoreply - new autoreply --------------------------------------------------

  if ($_POST['cmd'] == 'addautoreply' && (!isset($_POST['id']) || $_POST['id'] == '' || $_POST['id'] == 0) && $error == 0) {
    
//  availability check

      $data = pg_query($dbconn, 'INSERT INTO general.messages_autoreply (
                                    info,
                                    subject,
                                    body,
                                    language
                                 ) VALUES (
                                    \''.pg_escape_string($_POST['info']).'\',
                                    \''.pg_escape_string($_POST['subject']).'\',
                                    \''.pg_escape_string($_POST['body']).'\',
                                    '.$_SESSION['language'].'
                                 ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
      
      $row = pg_fetch_assoc($data);

//  response

      $xmlWriter->addChild('id', $row['id']);
 }

//  addautoreply - modify autoreply --------------------------------------------------

  if ($_POST['cmd'] == 'addautoreply' && is_numeric($_POST['id']) && $_POST['id'] != 0 && $error == 0) {

    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 6) { xmlerror(284); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
    else if (pg_num_rows($data) <= 0) { xmlerror(284); }
    else $autoreply = pg_fetch_assoc($data);
    
    $data = pg_query($dbconn, 'UPDATE general.messages_autoreply SET
                                  subject = \''.pg_escape_string($_POST['subject']).'\',
                                  info = \''.pg_escape_string($_POST['info']).'\',
                                  body = \''.pg_escape_string($_POST['body']).'\'
                               WHERE id='.$_POST['id'].' AND language='.$_SESSION['language']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }

    $xmlWriter->addChild('id', $_POST['id']);
 }

//  deleteautoreply --------------------------------------------------

  if ($_POST['cmd'] == 'deleteautoreply') {
 
    if ($_SESSION['user_admin'] != 1) { xmlerror(285); }
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5) && $error == 0) { xmlerror(286); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(286); }
    $row = pg_fetch_assoc($data);
          
    $data = pg_query($dbconn, 'DELETE FROM general.messages_autoreply WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
          
    $xmlWriter->addChild('id', $_POST['id']);

 }

//  translate_autoreply --------------------------------------------------

  if ($_POST['cmd'] == 'translate_autoreply') {

      if ($_SESSION['user_admin'] != 1) { xmlerror(287); }
      if (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] == '' || strlen($_POST['id']) > 4) { xmlerror(288); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(289); }
      if ($_SESSION['language'] == $_POST['target']) { xmlerror(290); }
      
      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { $target_ok = true; }
      }
      if (!$target_ok) { xmlerror(289); }

      $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply WHERE id='.$_POST['id'].' AND language='.$_SESSION['language'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(291); }
      $row = pg_fetch_assoc($data);
      
      $data = pg_query($dbconn, 'INSERT INTO general.messages_autoreply (
                                  subject,
                                  info,
                                  body,
                                  language
                                ) VALUES (
                                    \''.pg_escape_string($row['subject']).'\',
                                    \''.pg_escape_string($row['info']).'\',
                                    \''.pg_escape_string($row['body']).'\',
                                    '.$_POST['target'].'
                                ) RETURNING id');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $xmlWriter->addChild('id', $row['id']);
 }

//  translate_all_autoreplies --------------------------------------------------

  if ($_POST['cmd'] == 'translate_all_autoreplies' && $error == 0) {

      if ($_SESSION['user_admin'] != 1) { xmlerror(292); }
      if (!isset($_POST['target']) || !is_numeric($_POST['target']) || $_POST['target'] == '' || strlen($_POST['target']) > 4) { xmlerror(293); }
      if ($_POST['target'] == $_SESSION['language']) { xmlerror(294); }

      $target_ok = false;
      $languages = simplexml_load_file('../xmldata/languages.xml');
      foreach($languages as $language) {
        if ((string)$language['id'] == $_POST['target']) { 
          $target_ok = true; 
          $target = $language->name.' ('.$language->nameeng.')'; }
      }
      if (!$target_ok) { xmlerror(293); }
      
      $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply WHERE language='.$_SESSION['language']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      
      $counter = 0;
      while ($row = pg_fetch_assoc($data)) {
      
          $data2 = pg_query($dbconn, 'INSERT INTO general.messages_autoreply (
                                      subject,
                                      info,
                                      body,
                                      language
                                    ) VALUES (
                                        \''.pg_escape_string($row['subject']).'\',
                                        \''.pg_escape_string($row['info']).'\',
                                        \''.pg_escape_string($row['body']).'\',
                                        '.$_POST['target'].'
                                    ) RETURNING id');
          if (!$data2) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          $counter++;

      }

      $xmlWriter->addChild('target', $target);
      $xmlWriter->addChild('counter', $counter);
  }

//  messagetofolder --------------------------------------------------

  if ($_POST['cmd'] == 'messagetofolder') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(308); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || strlen($_POST['id']) > 6) { xmlerror(309); }
    if (!isset($_POST['folderid']) || !is_numeric($_POST['folderid']) || strlen($_POST['folderid']) > 6) { xmlerror(310); }
    
    if ($_POST['folderid'] != 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.messagefolders WHERE id='.$_POST['folderid'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(310); }
      $row = pg_fetch_assoc($data);
    }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(309); }
    
    $data = pg_query($dbconn, 'UPDATE general.messages SET folderid='.$_POST['folderid'].' WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    
    $xmlWriter->addChild('foldername', stripslashes($row['title']));
    $xmlWriter->addChild('folder_id', $_POST['folderid']);
  }

//  getreply --------------------------------------------------

  if ($_POST['cmd'] == 'getreply') {
  
    if ($_POST['autoreply'] == 0) { unset($_POST['autoreply']); }
    if ($_POST['autosend'] != 'true') { $_POST['autosend'] = 'false'; }

    if ($_SESSION['user_admin'] != 1) { xmlerror(311); }
    if (!isset($_POST['id']) || !is_numeric($_POST['id']) || strlen($_POST['id']) > 6) { xmlerror(312); }
    if (isset($_POST['autoreply']) && (!is_numeric($_POST['autoreply']) || strlen($_POST['autoreply']) > 6)) { xmlerror(313); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.messages WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(312); }
    $row = pg_fetch_assoc($data);
    
    if (isset($_POST['autoreply'])) {
      $data = pg_query($dbconn, 'SELECT * FROM general.messages_autoreply WHERE id='.$_POST['autoreply'].' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(313); }
      $autoreply = pg_fetch_assoc($data);
    }
    
    $body = '';
    
    $messages = simplexml_load_file('../xmldata/emails_'.$row['language'].'.xml');
    foreach($messages as $message) {
      if ($message->id == 'header') {
        $body = $message->body;
      }
    }
    
    $quote = '';
    
    if (isset($_POST['autoreply'])) {
      $quote = stripslashes($autoreply['body']);
      $body .= $quote;
      $subject = stripslashes($autoreply['subject']);
    } 

    foreach($messages as $message) {
      if ($message->id == 'signature') {
        $body .= $message->body;
        if (!isset($_POST['autoreply'])) { $subject = $message->subject; }
      }
    }
    
    if (!isset($_POST['autoreply'])) {
      $quote = stripslashes($row['text']);
      $quote = wordwrap($quote, (integer)getsetting('SYSTEMMSG_WORDWRAP'), '<br />', false);
      $quote = str_ireplace('<br />', '<br />> ', $quote);
      $quote = '> '.$quote;
      $body .= '<br /><br />'.$quote;
    }
    
    $body = str_ireplace('%name%', stripslashes($row['name']), $body);
    $body = str_ireplace('%url%', getsetting('FRONTEND_ROOT'), $body);
    $body = str_ireplace('%sendername%', $_SESSION['user_name'], $body);
    $body = str_ireplace('%senderemail%', $_SESSION['user_email'], $body);

    if (isset($_POST['autoreply']) && $_POST['autosend'] == 'true') {

      $data = pg_query($dbconn, 'UPDATE general.messages SET replied=TRUE where id='.$row['id']);
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      $send = sendfullmail($row['email'], $subject, $body, $_SESSION['user_name'], $_SESSION['user_email']);
      $xmlWriter->addChild('result', $send);

    } else {

//  xml output

      $xmlWriter->addChild('body', $body, true);
      $xmlWriter->addChild('to_name', stripslashes($row['name']));
      $xmlWriter->addChild('to_address', stripslashes($row['email']));
      $xmlWriter->addChild('subject', $subject);
      $xmlWriter->addChild('from_name', $_SESSION['user_name']);
      $xmlWriter->addChild('from_email', $_SESSION['user_email']);
    }
  }

//  sendemail --------------------------------------------------

  if ($_POST['cmd'] == 'sendemail') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(320); }
    if (!isset($_POST['to']) || strlen($_POST['to']) < 5 || substr_count($_POST['to'], '.') == 0 || substr_count($_POST['to'], '@') == 0) { xmlerror(321); }
    if (!isset($_POST['from']) || strlen($_POST['from']) < 5 || substr_count($_POST['from'], '.') == 0 || substr_count($_POST['from'], '@') == 0) { xmlerror(322); }
    if (isset($_POST['cc']) && $_POST['cc'] != '' && (strlen($_POST['cc']) < 5 || substr_count($_POST['cc'], '.') == 0 || substr_count($_POST['cc'], '@') == 0)) { xmlerror(323); }
    if (!isset($_POST['subject']) || strlen($_POST['subject']) < 3) { xmlerror(324); }
    if (!isset($_POST['body']) || strlen($_POST['body']) < 15) { xmlerror(325); }
    
    $send = sendfullmail($_POST['to'], $_POST['subject'], $_POST['body'], $_POST['from'], $_POST['from']);
    $xmlWriter->addChild('result', $send);
  }

//  getunread --------------------------------------------------

  if ($_POST['cmd'] == 'getunread') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(320); }
    $data = pg_query($dbconn, 'SELECT * FROM general.messages WHERE unread=TRUE');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $xmlWriter->addChild('messages', pg_num_rows($data));
  }

//  getmasslist --------------------------------------------------

  if ($_POST['cmd'] == 'getmasslist') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(320); }
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 6)) { xmlerror(345); }
    
    $filter = 'WHERE (userid='.$_SESSION['user_id'].' OR public=TRUE)';
    if (isset($_POST['id'])) { $filter .= ' AND id='.$_POST['id']; }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.massmailer_addresslist '.$filter.' ORDER BY title');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (isset($_POST['id']) && pg_num_rows($data) <= 0) { xmlerror(346); }
    
    $xmlWriter->addChild('lists', pg_num_rows($data));
    
    while($row = pg_fetch_assoc($data)) {

      $xmlWriter->startElement('list');
      $xmlWriter->addChild('id', $row['id']);
      $xmlWriter->addChild('title', stripslashes($row['title']));
      
      if (isset($_POST['id'])) {
        $addresslist = explode(',', $row['addresslist']);
        for($t=0; $t<count($addresslist); $t+=2) {
          $xmlWriter->startElement('address');
          $xmlWriter->addChild('name', stripslashes($addresslist[$t]));
          $xmlWriter->addChild('email', stripslashes($addresslist[$t+1]));
          $xmlWriter->endElement();
        }
      }
      
      $xmlWriter->endElement();
      
    }
  }

//  deletemasslist --------------------------------------------------

  if ($_POST['cmd'] == 'deletemasslist') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(347); }
    if (isset($_POST['id']) && (!is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 6)) { xmlerror(348); }
    $data = pg_query($dbconn, 'DELETE FROM general.massmailer_addresslist WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
  }

//  savemasslist --------------------------------------------------

  if ($_POST['cmd'] == 'savemasslist') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(347); }
    if (!isset($_POST['addresslist']) || strlen($_POST['addresslist']) < 4) { xmlerror(350); }
    if (!isset($_POST['title']) || strlen($_POST['title']) < 4) { xmlerror(351); }
    if ($_POST['public'] != 'true') { $_POST['public'] = 'false'; }
    
    $data = pg_query($dbconn, 'INSERT INTO general.massmailer_addresslist (
                                                                            addresslist,
                                                                            userid,
                                                                            title,
                                                                            public
                                                                          ) VALUES (
                                                                            \''.pg_escape_string($_POST['addresslist']).'\',
                                                                            '.$_SESSION['user_id'].',
                                                                            \''.pg_escape_string($_POST['title']).'\',
                                                                            '.$_POST['public'].'
                                                                          )');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
  }

//  importmasslist --------------------------------------------------

  if ($_POST['cmd'] == 'importmasslist') {
  
    $_POST['addresslist'] = rtrim(ltrim($_POST['addresslist']));
    if (!isset($_POST['addresslist']) || strlen($_POST['addresslist']) < 4) { xmlerror(353); }
    if (!isset($_POST['separator']) || !is_numeric($_POST['separator']) || $_POST['separator'] < 0 || $_POST['separator'] > 2) { xmlerror(355); }

    $separator = '';
    switch($_POST['separator']) {
      case 0: $separator = ','; break;
      case 1: $separator = "\n"; break;
      case 2: $separator = ' '; break;
    }
    
    $addresslist = explode($separator, $_POST['addresslist']);
    if (count($addresslist) == 1) { xmlerror(354); }
    
    foreach($addresslist as $address) {
      $xmlWriter->addChild('email', ltrim(rtrim($address)));
    }
  }

//  sendmassmail --------------------------------------------------

  if ($_POST['cmd'] == 'sendmassmail') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(356); }
    if (!isset($_POST['to']) || strlen($_POST['to']) < 5 || substr_count($_POST['to'], '.') == 0 || substr_count($_POST['to'], '@') == 0) { xmlerror(357); }
    if (!isset($_POST['subject']) || strlen($_POST['subject']) < 3) { xmlerror(358); }
    if (!isset($_POST['body']) || strlen($_POST['body']) < 15) { xmlerror(359); }
    if (!isset($_POST['sender']) || !is_numeric($_POST['sender']) || $_POST['sender'] < 0 || $_POST['sender'] > 1) { xmlerror(360); }
    
    if ($_POST['sender'] == 0) {
      $from = $_SESSION['user_email'];
      $fromname = $_SESSION['user_name'];
    } else {
      $from = getsetting('AUTOMAIL_SENDER_ADDRESS');
      $fromname = getsetting('AUTOMAIL_SENDER');
    }
    
    $body = str_ireplace('%name%', $_POST['toname']);
    $body = str_ireplace('%url%', getsetting('FRONTEND_ROOT'));
    $body = str_ireplace('%sender%', $from);
    $body = str_ireplace('%sendername%', $fromname);
    
    $send = sendfullmail($_POST['to'], $_POST['subject'], $_POST['body'], $from, $fromname);
    $xmlWriter->addChild('result', $send);
  }

//  getmassmailertemplate --------------------------------------------------

  if ($_POST['cmd'] == 'getmassmailertemplate') {
    if ($_SESSION['user_admin'] != 1) { xmlerror(352); }

    $emails = simplexml_load_file('../xmldata/emails_'.$_SESSION['language'].'.xml');
    
    foreach($emails->email as $email) {
      if ($email->id == 'signature') { $signature = $email->body; }
      if ($email->id == 'header') { $header = $email->body; }
    }
    $xmlWriter->addChild('body', $header.$signature, true);
  }


require('api.end.php');

?>
