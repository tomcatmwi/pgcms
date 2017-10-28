<?php
  require_once('api.start.php');

//  getstatus --------------------------------------------------

  if ($_POST['cmd'] == 'getstatus') {
    $xmlWriter->addChild('cmd', 'getstatus');
  }

//  login --------------------------------------------------

  if ($_POST['cmd'] == 'login') {
  
    if ((!isset($_POST['username']) || strlen($_POST['username']) < 5 || strlen($_POST['username']) > 64)) { xmlerror(58); }
    if ((!isset($_POST['password']) || strlen($_POST['password']) < 5 || strlen($_POST['password']) > 64)) { xmlerror(58); }

    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE username=\''.pg_escape_string($_POST['username']).'\' AND password=\''.md5($_POST['password']).'\' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) == 0) { xmlerror(58); }
      $user = pg_fetch_assoc($data);
    }
        
//  set session variables

      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_name'] = stripslashes($user['name']);
      $_SESSION['user_email'] = $user['email'];
      setlanguage($user['language']);
      if ($user['admin'] == 't') { $_SESSION['user_admin'] = 1; } else { $_SESSION['user_admin'] = 0; }

//  response

      $xmlWriter->startElement('login');
      $xmlWriter->addChild('id', $user['id']); 
      $xmlWriter->addChild('name', stripslashes($user['name']));
      $xmlWriter->endElement();

  }

//  logout --------------------------------------------------

  if ($_POST['cmd'] == 'logout') {

        $xmlWriter->startElement('logout');
        $xmlWriter->addChild('id', $_SESSION['user_id']); 
        $xmlWriter->addChild('name', $_SESSION['user_name']);
        $xmlWriter->endElement();

        $_SESSION['user_id'] = null;
        $_SESSION['user_name'] = null;
        $_SESSION['user_email'] = null;
        $_SESSION['user_admin'] = null;

  }

//  forgotpassword --------------------------------------------------
    
  if ($_POST['cmd'] == 'forgotpassword') {

    if ((!isset($_POST['email']) || strlen($_POST['email']) < 5 || substr_count($_POST['email'], '.') == 0 || substr_count($_POST['email'], '@') == 0)) { xmlerror(66); $error = 66; }
    
    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE email=\''.pg_escape_string($_POST['email']).'\' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
      else if (pg_num_rows($data) <= 0) { xmlerror(67); $error = 67; }
      else {
        $user = pg_fetch_assoc($data);
        if ($user['active'] == 'f') { xmlerror(69); $error = 69; } 
        if ($error == 0) {

          $resetcode = '';
          for ($t=0; $t<12; $t++) {
            $resetcode .= chr(round(rand(97, 122)));
          }

          $data = pg_query($dbconn, 'UPDATE general.users SET resetcode=\''.$resetcode.'\' WHERE email=\''.pg_escape_string($_POST['email']).'\'');
          if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
          
          if ($error == 0) {

            $email = getemail('forgot_password', $_SESSION['language']);
            $email['body'] = str_ireplace('%name%', stripslashes($user['name']), $email['body']);
            $email['body'] = str_ireplace('%resetcode%', $resetcode, $email['body']);
            sendfullmail($_POST['email'], $email['subject'], $email['body'], getsetting('AUTOMAIL_SENDER_ADDRESS'), getsetting('AUTOMAIL_SENDER'));
            
            $xmlWriter->startElement('forgotpassword');
            $xmlWriter->addChild('id', $user['id']); 
            $xmlWriter->addChild('name', stripslashes($user['name']));
            $xmlWriter->endElement();
          }
        }
      }
    }
  }

//  register - general check

  if ($_POST['cmd'] == 'register') {
  
//  xmldie($_POST['id']);

    if ($_SESSION['user_admin'] != 1 && (isset($_POST['admin']) || isset($_POST['active']))) { xmlerror(34); }
    if (isset($_POST['id']) && $_POST['id'] != 0 && ($_SESSION['user_admin'] != 1 && (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $_POST['id']))) { xmlerror(34); }

    if ((!isset($_POST['name']) || strlen($_POST['name']) < 3 || strlen($_POST['name']) > 64)) { xmlerror(243); }
//    if ((!isset($_POST['nickname']) || strlen($_POST['nickname']) < 3 || strlen($_POST['nickname']) > 64)) { xmlerror(35); }
    if ((!isset($_POST['country']) || strlen($_POST['country']) != 2)) { xmlerror(37); }
    if ((!isset($_POST['city']) || strlen($_POST['city']) < 2)) { xmlerror(38); }
    if ((!isset($_POST['email']) || strlen($_POST['email']) < 5 || substr_count($_POST['email'], '.') == 0 || substr_count($_POST['email'], '@') == 0)) { xmlerror(39); }
    if ((!isset($_POST['username']) || strlen($_POST['username']) < 5)) { xmlerror(40); }
    if ((!isset($_POST['password1']) || strlen($_POST['password1']) < 5)) { xmlerror(40); }
    if ((!isset($_POST['password2']) || strlen($_POST['password2']) < 5)) { xmlerror(40); }
    if ($_POST['password1'] != $_POST['password2']) { xmlerror(42); }
    if ($_POST['password1'] == $_POST['username']) { xmlerror(374); }
    if (strlen($_POST['phone1']) > 3 || strlen($_POST['phone2']) > 3 || strlen($_POST['phone3']) > 9) { xmlerror(48); }

//  check country

    if ($error == 0) {
      $countries = simplexml_load_file('../xmldata/countries.xml');
      $found = 0;
      foreach($countries as $country) if ($country['id'] == $_POST['country']) { $found++; }
      if ($found == 0) { xmlerror(37); }
    }
  }

//  register - new user --------------------------------------------------

  if ($_POST['cmd'] == 'register' && $_POST['id'] == 0) {

    if ($_SESSION['ui'] == 'frontend' && !isset($_POST['captcha'])) { xmlerror(22); }

//  set booleans

    if ($_SESSION['user_admin'] != 1 || $_SESSION['ui'] == 'frontend') { 
       $_POST['admin'] = 'false';
       $_POST['active'] = 'false';
    }
    
//  availability check

    if ($error == 0) {
      $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE 
                            (password=\''.$_POST['password1'].'\' AND username=\''.pg_escape_string($_POST['username']).'\') OR
                            email=\''.pg_escape_string($_POST['email']).'\'
                        LIMIT 1');
                        
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

      if (pg_num_rows($data) > 0) {
        $row = pg_fetch_assoc($data);
        if ($row['email'] == pg_escape_string($_POST['email'])) { xmlerror(49, '%email%|'.$_POST['email']); $error = 49; }
        if ($row['password'] == $_POST['password1'] && $row['username'] == pg_escape_string($_POST['username'])) { xmlerror(47); $error = 47; }
        if ($row['phone1'] == pg_escape_string($_POST['phone1']) && $row['phone2'] == pg_escape_string($_POST['phone2']) && $row['phone3'] == pg_escape_string($_POST['phone3'])) { xmlerror(48); $error = 48; }
      }
    }
      
      $resetcode = '';
      for ($t=0; $t<12; $t++) {
        $resetcode .= chr(round(rand(97, 122)));
      }
      
      $data = pg_query($dbconn, 'INSERT INTO general.users (

                                    name,
                                    nickname,
                                    country,
                                    state,
                                    city,
                                    zip,
                                    address,
                                    email,
                                    phone1,
                                    phone2,
                                    phone3,
                                    company,
                                    company_country,
                                    company_state,
                                    company_zip,
                                    company_city,
                                    company_address,
                                    language,
                                    active,
                                    admin,
                                    newsletter,
                                    username,
                                    password,
                                    resetcode

                                 ) VALUES (

                                    \''.pg_escape_string($_POST['name']).'\',
                                    \''.pg_escape_string($_POST['nickname']).'\',
                                    \''.pg_escape_string($_POST['country']).'\',
                                    \''.pg_escape_string($_POST['state']).'\',
                                    \''.pg_escape_string($_POST['city']).'\',
                                    \''.pg_escape_string($_POST['zip']).'\',
                                    \''.pg_escape_string($_POST['address']).'\',
                                    \''.pg_escape_string($_POST['email']).'\',
                                    \''.pg_escape_string($_POST['phone1']).'\',
                                    \''.pg_escape_string($_POST['phone2']).'\',
                                    \''.pg_escape_string($_POST['phone3']).'\',
                                    \''.pg_escape_string($_POST['company']).'\',
                                    \''.pg_escape_string($_POST['company_country']).'\',
                                    \''.pg_escape_string($_POST['company_state']).'\',
                                    \''.pg_escape_string($_POST['company_zip']).'\',
                                    \''.pg_escape_string($_POST['company_city']).'\',
                                    \''.pg_escape_string($_POST['company_address']).'\',
                                    '.$_POST['language'].',
                                    '.$_POST['active'].',
                                    '.$_POST['admin'].',
                                    '.$_POST['newsletter'].',
                                    \''.pg_escape_string($_POST['username']).'\',
                                    \''.md5($_POST['password1']).'\',
                                    \''.$resetcode.'\'
                                 ) RETURNING id');

      if (!$data) { xmlerror(1000, '%errormsg%|'.$_POST['language'].' '.pg_last_error($dbconn)); $error = 1000; }
      else {

//  send e-mail

        $email = getemail('registration_confirm', $_SESSION['language']);
        $body = str_ireplace('%name%', $_POST['name'], $email['body']);
        $body = str_ireplace('%resetcode%', $resetcode, $body);
        sendfullmail($_POST['email'], $email['subject'], $body, getsetting('AUTOMAIL_SENDER_ADDRESS'), getsetting('AUTOMAIL_SENDER'));

//  response

        $row = pg_fetch_assoc($data);
        $xmlWriter->startElement('register');
        $xmlWriter->addChild('id', $row['id']);
        $xmlWriter->addChild('name', $_POST['name']);
        $xmlWriter->endElement();
    }
  }

//  register - modify user --------------------------------------------------

  if ($_POST['cmd'] == 'register' && is_numeric($_POST['id']) && $_POST['id'] != 0) {

    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == '' || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] < 0) { xmlerror(86); }
    if ($_POST['id'] != $_SESSION['user_id'] && $_SESSION['user_admin'] != 1) { xmlerror(84); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(85); }
    $user = pg_fetch_assoc($data);
    
//  set booleans

    if ($_SESSION['user_admin'] != 1 || $_SESSION['ui'] == 'frontend') { 
       if ($user['admin'] == 't') { $_POST['admin'] = 'true'; } else { $_POST['admin'] = 'false'; }
       if ($user['active'] == 't') { $_POST['active'] = 'true'; } else { $_POST['active'] = 'false'; }
    }
    
      if ($_POST['password1'] == $user['password']) { $password = $user['password']; }
      else { $password = md5($_POST['password1']); }
            
      $data = pg_query($dbconn, 'UPDATE general.users SET
                          name = \''.pg_escape_string($_POST['name']).'\',
                          nickname = \''.pg_escape_string($_POST['nickname']).'\',
                          country = \''.pg_escape_string($_POST['country']).'\',
                          state = \''.pg_escape_string($_POST['state']).'\',
                          city = \''.pg_escape_string($_POST['city']).'\',
                          zip = \''.pg_escape_string($_POST['zip']).'\',
                          address = \''.pg_escape_string($_POST['address']).'\',
                          email = \''.pg_escape_string($_POST['email']).'\',
                          language = \''.pg_escape_string($_POST['language']).'\',
                          username = \''.pg_escape_string($_POST['username']).'\',
                          password = \''.$password.'\',
                          phone1 = \''.$_POST['phone1'].'\',
                          phone2 = \''.$_POST['phone2'].'\',
                          phone3 = \''.$_POST['phone3'].'\',
                          admin = '.$_POST['admin'].',
                          active = '.$_POST['active'].',
                          newsletter = '.$_POST['newsletter'].',
                          company = \''.pg_escape_string($_POST['company']).'\',
                          company_country = \''.pg_escape_string($_POST['company_country']).'\',
                          company_state = \''.pg_escape_string($_POST['company_state']).'\',
                          company_zip = \''.pg_escape_string($_POST['company_zip']).'\',
                          company_city = \''.pg_escape_string($_POST['company_city']).'\',
                          company_address = \''.pg_escape_string($_POST['company_address']).'\'

                        WHERE id='.$_POST['id']);

      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }

      if ($_POST['id'] == $_SESSION['user_id']) {
        $_SESSION['user_name'] = $_POST['name'];
        $_SESSION['user_email'] = $_POST['email'];
        setlanguage($_POST['language']);
      }

      $xmlWriter->startElement('register');
      $xmlWriter->addChild('id', $_POST['id']);
      $xmlWriter->addChild('name', $_POST['name']);
      $xmlWriter->endElement();
  }

//  getuser --------------------------------------------------

  if ($_POST['cmd'] == 'getuser') {
  
    if (count($_POST) == 1 && !isset($_SESSION['user_id'])) { xmlerror(23); }
    if (count($_POST) == 1) { $_POST['id'] = $_SESSION['user_id']; }
  
    if (isset($_POST['method']) && (!is_numeric($_POST['method']) || $_POST['method'] < 0 || $_POST['method'] > 10)) { xmlerror(24); }
    if (isset($_POST['id']) && $_POST['id'] != '' && (!is_numeric($_POST['id']) || $_POST['id'] < 0 || strlen($_POST['id']) > 5)) { xmlerror(82); }
    if ($_POST['search'] != '' && strlen($_POST['search']) < 3) { xmlerror(319); }

    $filter = '';

    if ($_POST['active'] == 'true') { if ($filter != '') { $filter .= ' AND '; } $filter .= 'users.active=true'; }
    if ($_POST['admin'] == 'true') { if ($filter != '') { $filter .= ' AND '; } $filter .= 'users.admin=true'; }

    $offset = 0;
    if (isset($_POST['start']) && is_numeric($_POST['start'])) { $offset = $_POST['start']; }

    if (isset($_POST['search']) && $_POST['search'] != '') { $filter = 'name ILIKE \'%'.pg_escape_string($_POST['search']).'%\' OR 
                                                                         email ILIKE \'%'.pg_escape_string($_POST['search']).'%\' OR
                                                                         phone3 ILIKE \'%'.pg_escape_string($_POST['search']).'%\'';
                                                            }

    if (isset($_POST['id']) && $_POST['id'] != '' && is_numeric($_POST['id']) && $_POST['id'] > 0) {
        $filter = 'users.id='.$_POST['id'];
        $offset = 0;
    }
    
    if ($filter != '') { $filter = 'WHERE '.$filter; }
      
        if (!isset($_POST['nolimit'])) { $maxcount = getsetting('LIST_COUNT'); }
        else { $maxcount = '2^62'; }
        $xmlWriter->addChild('maxcount', $maxcount);

//  Special filters - massmailer.php
        
        if (isset($_POST['method'])) {
            if ($_POST['method'] == 0) { $filter = 'WHERE newsletter=TRUE AND active=TRUE'; }
            if ($_POST['method'] == 1) { $filter = 'WHERE active=TRUE'; }
            if ($_POST['method'] == 2) { $filter = 'WHERE admin=TRUE AND active=TRUE'; }
        }

        $data = pg_query($dbconn, 'SELECT * FROM general.users '.$filter);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
        $xmlWriter->addChild('total', pg_num_rows($data));
        $xmlWriter->addChild('offset', $offset);
        
        $data = pg_query($dbconn, 'SELECT * FROM general.users '.$filter.' ORDER BY admin DESC, name OFFSET '.$offset.' LIMIT '.$maxcount);
        if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
          
        $countries = simplexml_load_file('../xmldata/countries.xml');

        while ($user = pg_fetch_assoc($data)) {

          $xmlWriter->startElement('user');
          $xmlWriter->addChild('id', $user['id']); 

          $xmlWriter->addChild('name', stripslashes($user['name']));
          $xmlWriter->addChild('nickname', stripslashes($user['nickname']));
          $xmlWriter->addChild('zip', stripslashes($user['zip']));
          $xmlWriter->addChild('city', stripslashes($user['city']));
          $xmlWriter->addChild('country', stripslashes($user['country']));
          $xmlWriter->addChild('state', stripslashes($user['state']));
          $xmlWriter->addChild('company', stripslashes($user['company']));
          $xmlWriter->addChild('company_country', stripslashes($user['company_country']));
          $xmlWriter->addChild('company_state', stripslashes($user['company_state']));
          $xmlWriter->addChild('company_zip', stripslashes($user['company_zip']));
          $xmlWriter->addChild('company_city', stripslashes($user['company_city']));
          $xmlWriter->addChild('company_address', stripslashes($user['company_address']));

          if ($countries != false) { 
            foreach($countries as $country) {

              if ($country['id'] == stripslashes($user['country'])) {
                $xmlWriter->addChild('country_name', $country->name);
                $xmlWriter->addChild('country_nameeng', $country->nameeng);
              }

              if ($country['id'] == stripslashes($user['company_country'])) {
                $xmlWriter->addChild('company_country_name', $country->name);
                $xmlWriter->addChild('company_country_nameeng', $country->nameeng);
              }

            }
          }
          
          $xmlWriter->addChild('language', $user['language']);
          $lang = getlanguage($user['language']);
          $xmlWriter->addChild('language_name', $lang['name']);
          $xmlWriter->addChild('language_nameeng', $lang['nameeng']);
          $xmlWriter->addChild('flag', $lang['flag']);

          if ($user['admin'] == 't') { $xmlWriter->addChild('admin', 1); } else { $xmlWriter->addChild('admin', 0); }
          if ($user['active'] == 't') { $xmlWriter->addChild('active', 1); } else { $xmlWriter->addChild('active', 0); }
          if ($user['newsletter'] == 't') { $xmlWriter->addChild('newsletter', 1); } else { $xmlWriter->addChild('newsletter', 0); }
                  
    //  personal information - for the user himself or admins
          
          if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != '' && ($user['id'] == $_SESSION['user_id'] || $_SESSION['user_admin'] == 1)) {
            $xmlWriter->addChild('email', stripslashes($user['email']));
            $xmlWriter->addChild('phone1', stripslashes($user['phone1']));
            $xmlWriter->addChild('phone2', stripslashes($user['phone2']));
            $xmlWriter->addChild('phone3', stripslashes($user['phone3']));
            $xmlWriter->addChild('address', stripslashes($user['address']));
            $xmlWriter->addChild('username', stripslashes($user['username']));
            $xmlWriter->addChild('password', stripslashes($user['password']));
          }

          $xmlWriter->endElement();

      }
  }


//  deleteuser --------------------------------------------------

  if ($_POST['cmd'] == 'deleteuser') {
 
    if ((!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { xmlerror(185); }
    if ($_SESSION['user_admin'] != 1) { xmlerror(244); }
    
    $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE id='.$_POST['id'].' LIMIT 1');
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    if (pg_num_rows($data) <= 0) { xmlerror(185); }
    $row = pg_fetch_assoc($data);
    $data = pg_query($dbconn, 'DELETE FROM general.users WHERE id='.$_POST['id']);
    if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
    $xmlWriter->addChild('id', $_POST['id']);
  }

//  confirmreg --------------------------------------------------

  if ($_POST['cmd'] == 'confirmreg') {
    
    if ((!isset($_POST['resetcode']) || strlen($_POST['resetcode']) != 12 || preg_match('/^[a-z]{1,}$/', $_POST['resetcode']) != 1)) { xmlerror(52); }

      $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE resetcode=\''.$_POST['resetcode'].'\' AND active=FALSE LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(53); }

         $user = pg_fetch_assoc($data);

           $data = pg_query($dbconn, 'UPDATE general.users SET active=TRUE, resetcode=\'\' WHERE resetcode=\''.$_POST['resetcode'].'\' AND active=FALSE');
           if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }

           $xmlWriter->startElement('confirmreg');
           $xmlWriter->addChild('id', $user['id']); 
           $xmlWriter->addChild('name', stripslashes($user['name']));
           $xmlWriter->addChild('realname', stripslashes($user['realname']));
           $xmlWriter->endElement();
  }

//  resetpassword --------------------------------------------------
    
  if ($_POST['cmd'] == 'resetpassword') {
    if ((!isset($_POST['resetcode']) || strlen($_POST['resetcode']) != 12 || preg_match('/^[a-z]{1,}$/', $_POST['resetcode']) != 1)) { xmlerror(70); }
    if ((!isset($_POST['username']) || strlen($_POST['username']) < 5 || strlen($_POST['username']) > 64)) { xmlerror(72); }
    if ((!isset($_POST['password1']) || strlen($_POST['password1']) < 5 || strlen($_POST['password1']) > 64)) { xmlerror(73); }
    if ((!isset($_POST['password2']) || strlen($_POST['password2']) < 5 || strlen($_POST['password2']) > 64)) { xmlerror(74); }
    if ($_POST['password1'] != $_POST['password2']) { xmlerror(75); }
    if ($_POST['password1'] == $_POST['username']) { xmlerror(76); }

      $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE resetcode=\''.$_POST['resetcode'].'\' LIMIT 1');
      if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
      if (pg_num_rows($data) <= 0) { xmlerror(71); }

         $user = pg_fetch_assoc($data);

//  availability check

            $data = pg_query($dbconn, 'SELECT * FROM general.users WHERE 
                                  id <> '.$user['id'].' AND
                                  (password=\''.md5($_POST['password1']).'\' AND username=\''.pg_escape_string($_POST['username']).'\')
                                  LIMIT 1');
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); }
            if (pg_num_rows($data) > 0) { xmlerror(72); }

//  do stuff

           $data = pg_query($dbconn, 'UPDATE general.users SET 
                                                              password=\''.md5($_POST['password1']).'\',
                                                              username=\''.pg_escape_string($_POST['username']).'\', 
                                                              resetcode=\'\'
                                      WHERE id='.$user['id']);

           if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }

           $xmlWriter->addChild('id', $user['id']); 
           $xmlWriter->addChild('name', stripslashes($user['name']));
  }

require('api.end.php');

?>
