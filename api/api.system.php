<?php
  require_once('api.start.php');

//  version -----------------------------------------------------

  if ($_POST['cmd'] == 'version') {
    $xmlWriter->addChild('version', 'pgCMS 1.1b &copy; 2016 pixeldog studios', true);
  }

//  getsetting -----------------------------------------------------

  if ($_POST['cmd'] == 'getsetting') {
      if (!isset($_POST['token']) || $_POST['token'] == '') { xmlerror(332); }
      
      $_POST['token'] = str_ireplace(' ', '', $_POST['token']);
      
      if (stripos($_POST['token'], ',')) {
        $tokens = explode(',', $_POST['token']);
      } else {
        $tokens = Array();
        array_push($tokens, $_POST['token']);
      }

      $found = false;
      $settings = simplexml_load_file('../xmldata/settings.xml');
      
      foreach ($tokens as $token) {
      
          foreach($settings as $setting) {
            if ((string)$setting['id'] == $token) {
              if ($_SESSION['user_admin'] != 1 && $setting->status == 'Internal') { xmlerror(333); }
              
              $xmlWriter->startElement('setting');
              
              $xmlWriter->addChild('id', $setting['id']);
              $xmlWriter->addChild('label', $setting->label);
              $xmlWriter->addChild('value', $setting->value);
              $xmlWriter->addChild('explanation', $setting->explanation);
              $xmlWriter->addChild('status', $setting->status);

              $xmlWriter->endElement();

              $found = true;
            }
          }
          
      }
      
      if (!$found) { xmlerror(332); }
  }

//  getallsettings -----------------------------------------------------

  if ($_POST['cmd'] == 'getallsettings') {
      if ($_SESSION['user_admin'] != 1) { xmlerror(334); }
      
      $settings = simplexml_load_file('../xmldata/settings.xml');
      foreach($settings as $setting) {
      
        $xmlWriter->startElement('setting');

          $xmlWriter->addChild('id', $setting['id']);
          $xmlWriter->addChild('label', $setting->label);
          $xmlWriter->addChild('value', $setting->value);
          $xmlWriter->addChild('explanation', $setting->explanation);
          $xmlWriter->addChild('status', $setting->status);
          
        $xmlWriter->endElement();
        
      }
  }

//  setlanguage --------------------------------------------------
  
  if ($_POST['cmd'] == 'setlanguage') {
  
    if (!isset($_SESSION['language']) && (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { $_POST['id'] = 1; }
    if (isset($_SESSION['language']) && (!isset($_POST['id']) || !is_numeric($_POST['id']) || $_POST['id'] <= 0 || strlen($_POST['id']) > 5)) { $_POST['id'] = $_SESSION['language']; }
    if (!file_exists('../xmldata/languages.xml')) { xmlerror(4); $error = 4; }

    $languages = simplexml_load_file('../xmldata/languages.xml');
    foreach($languages as $language) {
      if ((string)$language['id'] == $_POST['id']) {
        
        if ((string)$language->selectable == '1') {
            
          setlanguage($_POST['id']);

          if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            $data = pg_query($dbconn, 'UPDATE general.users SET
                                          language = '.$_POST['id'].'
                                       WHERE id='.$_SESSION['user_id']);
            if (!$data) { xmlerror(1000, '%errormsg%|'.pg_last_error($dbconn)); $error = 1000; }
          }
        }
      }
    }
  }

//  getlanguage --------------------------------------------------
  
  if (($_POST['cmd'] == 'getlanguage' || $_POST['cmd'] == 'setlanguage')) {

    $xmlWriter->startElement('language');

    $xmlWriter->addChild('language', (string)$_SESSION['language']);
    $xmlWriter->addChild('language_name', (string)$_SESSION['language_name']);
    $xmlWriter->addChild('language_nameeng', (string)$_SESSION['language_nameeng']);
    $xmlWriter->addChild('language_date', (string)$_SESSION['language_date']);
    $xmlWriter->addChild('language_thousandsseparator', (string)$_SESSION['language_thousandsseparator']);
    $xmlWriter->addChild('language_decimalpoint', (string)$_SESSION['language_decimalpoint']);
    $xmlWriter->addChild('language_langcode', (string)$_SESSION['language_langcode']);
    $xmlWriter->addChild('language_googlecode', (string)$_SESSION['language_googlecode']);
    $xmlWriter->addChild('language_selectable', (string)$_SESSION['language_selectable']);
    $xmlWriter->addChild('language_flag', (string)$_SESSION['language_flag']);
    
    $xmlWriter->endElement();


  }
  
//  updatesetting --------------------------------------------------

  if ($_POST['cmd'] == 'updatesetting') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(14); }
    if (!isset($_POST['id']) || $_POST['id'] == '') { xmlerror(13); }
    if (!isset($_POST['label']) || $_POST['label'] == '') { xmlerror(275); }
//    if (!isset($_POST['value']) || $_POST['value'] == '') { xmlerror(276); }
    if (!isset($_POST['explanation']) || $_POST['explanation'] == '') { xmlerror(277); }
    
    $output = new SimpleXMLElement('<settings/>');
    
    $settings = simplexml_load_file('../xmldata/settings.xml');
    foreach($settings as $setting) {
      
      if ($setting['id'] == $_POST['id']) { 
        $setting->label = $_POST['label'];
        $setting->value = $_POST['value'];
        $setting->explanation = $_POST['explanation'];
      }

      $node = $output->addChild('setting');
      $node->addAttribute('id', $setting['id']);
      $node->addChild('label', $setting->label);
      $node->addChild('value', $setting->value);
      $node->addChild('explanation', $setting->explanation);
      $node->addChild('status', $setting->status);
    }
    
    $error = file_put_contents('../xmldata/settings.xml', $output->asXML());
    if ($error == false) { xmlerror(264); }
    $xmlWriter->addChild('updated', '1');
      
  }

//  updatesystemmsg --------------------------------------------------

  if ($_POST['cmd'] == 'updatesystemmsg') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(259); }
    if (!isset($_POST['id']) || $_POST['id'] == '') { xmlerror(263); }
    if (!isset($_POST['subject']) || $_POST['subject'] == '') { xmlerror(261); }
    if (!isset($_POST['body']) || $_POST['body'] == '') { xmlerror(262); }
    
    $output = new SimpleXMLElement('<emails/>');
    
    $messages = simplexml_load_file('../xmldata/emails_'.$_SESSION['language'].'.xml');
    foreach($messages as $message) {
      
      if ($message->id == $_POST['id']) { 
        $message->subject = $_POST['subject'];
        $message->body = $_POST['body'];
      }

      $node = $output->addChild('email');
      $node->addChild('id', $message->id);
      $node->addChild('subject', $message->subject);
      $node->addChild('explanation', $message->explanation);
      $node->addChild('body', '<![CDATA['.stripslashes($message->body).']]>');
    }
    
    $error = file_put_contents('../xmldata/emails_'.$_SESSION['language'].'.xml', html_entity_decode($output->asXML()));
    if ($error == false) { xmlerror(264); }
    $xmlWriter->addChild('updated', '1');
  
  }

//  updatelanguage --------------------------------------------------

  if ($_POST['cmd'] == 'updatelanguage') {
  
    if ($_POST['thousandsseparator'] == '') { $_POST['thousandsseparator'] = ' '; }
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(189); }
    if (!isset($_POST['id']) || $_POST['id'] == '') { xmlerror(190); }
    if (!isset($_POST['name']) || $_POST['name'] == '') { xmlerror(265); }
    if (!isset($_POST['nameeng']) || $_POST['nameeng'] == '') { xmlerr  or(266); }
    if (!isset($_POST['date']) || $_POST['date'] == '') { xmlerror(267); }
    if (!isset($_POST['encoding']) || $_POST['encoding'] == '') { xmlerror(269); }
    if (!isset($_POST['langcode']) || $_POST['langcode'] == '') { xmlerror(270); }
    if (!isset($_POST['googlecode']) || $_POST['googlecode'] == '') { xmlerror(271); }
    if (!isset($_POST['thousandsseparator']) || $_POST['thousandsseparator'] == '' || strlen($_POST['thousandsseparator']) != 1) { xmlerror(272); }
    if (!isset($_POST['decimalpoint']) || $_POST['decimalpoint'] == '' || strlen($_POST['decimalpoint']) != 1) { xmlerror(273); }
    if (!isset($_POST['selectable']) || $_POST['selectable'] == '' || !is_numeric($_POST['selectable']) || $_POST['selectable'] < 0 || $_POST['selectable'] > 1) { xmlerror(262); }
    
    $output = new SimpleXMLElement('<languages/>');
    
    $languages = simplexml_load_file('../xmldata/languages.xml');
    foreach($languages as $language) {
      
      if ($language['id'] == $_POST['id']) { 
        $language->name = $_POST['name'];
        $language->nameeng = $_POST['nameeng'];
        $language->date = $_POST['date'];
        $language->encoding = $_POST['encoding'];
        $language->langcode = $_POST['langcode'];
        $language->googlecode = $_POST['googlecode'];
        $language->thousandsseparator = $_POST['thousandsseparator'];
        $language->decimalpoint = $_POST['decimalpoint'];
        $language->selectable = $_POST['selectable'];
      }

      $node = $output->addChild('language');
      $node->addAttribute('id', $language['id']);
      $node->addChild('name', $language->name);
      $node->addChild('nameeng', $language->nameeng);
      $node->addChild('date', $language->date);
      $node->addChild('encoding', $language->encoding);
      $node->addChild('langcode', $language->langcode);
      $node->addChild('googlecode', $language->googlecode);
      $node->addChild('thousandsseparator', $language->thousandsseparator);
      $node->addChild('decimalpoint', $language->decimalpoint);
      $node->addChild('selectable', $language->selectable);
      $node->addChild('flag', $language->flag);
    }
    
    $error = file_put_contents('../xmldata/languages.xml', $output->asXML());
    if ($error == false) { xmlerror(188); }
    $xmlWriter->addChild('updated', '1');
  
  }

//  updatecountry --------------------------------------------------

  if ($_POST['cmd'] == 'updatecountry') {
  
    if ($_SESSION['user_admin'] != 1) { xmlerror(25); }
    if (!isset($_POST['id']) || strlen($_POST['id']) != 2) { xmlerror(26); }
    if (!isset($_POST['name']) || $_POST['name'] == '') { xmlerror(27); }
    if (!isset($_POST['nameeng']) || $_POST['nameeng'] == '') { xmlerror(28); }
    if (!isset($_POST['capital']) || $_POST['capital'] == '') { xmlerror(31); }
    if (!isset($_POST['currency']) || strlen($_POST['currency']) != 3) { xmlerror(32); }
    if (!isset($_POST['phonecode']) || !is_numeric($_POST['phonecode']) || strlen($_POST['phonecode']) > 4) { xmlerror(41); }
    if (!isset($_POST['language']) || !is_numeric($_POST['language']) || strlen($_POST['language']) > 4) { xmlerror(43); }
    if (!isset($_POST['imperial']) || !is_numeric($_POST['imperial']) || $_POST['imperial'] < 0 || $_POST['imperial'] > 1) { xmlerror(33); }
    if (!isset($_POST['selectable']) || !is_numeric($_POST['selectable']) || $_POST['selectable'] < 0 || $_POST['selectable'] > 1) { xmlerror(36); }
    
    $output = new SimpleXMLElement('<countries/>');
    
    $countries = simplexml_load_file('../xmldata/countries.xml');
    foreach($countries as $country) {
      
      if ($country['id'] == $_POST['id']) { 
        $country->name = $_POST['name'];
        $country->nameeng = $_POST['nameeng'];
        $country->capital = $_POST['capital'];
        $country->currency = $_POST['currency'];
        $country->phonecode = $_POST['phonecode'];
        $country->language = $_POST['language'];
        $country->imperial = $_POST['imperial'];
        $country->selectable = $_POST['selectable'];
      }

      $node = $output->addChild('country');
      $node->addAttribute('id', $country['id']);
      $node->addChild('name', $country->name);
      $node->addChild('nameeng', $country->nameeng);
      $node->addChild('capital', $country->capital);
      $node->addChild('currency', $country->currency);
      $node->addChild('phonecode', $country->phonecode);
      $node->addChild('language', $country->language);
      $node->addChild('imperial', $country->imperial);
      $node->addChild('selectable', $country->selectable);
    }
    
    $error = file_put_contents('../xmldata/countries.xml', $output->asXML());
    if ($error == false) { xmlerror(44); }
    $xmlWriter->addChild('updated', '1');
  
  }

//  getcaptcha --------------------------------------------------

  if ($_POST['cmd'] == 'getcaptcha') {
      
      $width = (integer)getsetting('CAPTCHA_WIDTH'); // 160
      $height = (integer)getsetting('CAPTCHA_HEIGHT'); // 60
      $color = getsetting('CAPTCHA_COLOR'); // BBBBBB
      $fontsize = (integer)getsetting('CAPTCHA_FONT_SIZE'); // 17
      
      $xmlWriter->addChild('width', $width);
      $xmlWriter->addChild('height', $height);
      $xmlWriter->addChild('color', $color);
      $xmlWriter->addChild('fontsize', $fontsize);
      
      $chars = 'ABCDEFGHIJKLMNOPQRSTUVXYZ1234567890';
      for ($t=0; $t<6; $t++) {
        $captchacode .= substr($chars, rand(0, strlen($chars)-2), 1);
      }
      
      $captcha = imagecreatetruecolor($width, $height);
      $color = imagecolorallocate($captcha, (integer)hexdec(substr($color, 0, 2)), (integer)hexdec(substr($color, 2, 2)), (integer)hexdec(substr($color, 4, 2)));

      $background = imagecreatefrompng('../pic/captcha.png');
      imagecopy($captcha, $background, 0, 0, rand(0, (imagesx($background)-$width)), rand(0, (imagesy($background)-$height)), $width, $height);
            
      $angle = rand(-6, 6);
      imagettftext($captcha, $fontsize, $angle, rand(5, 50), ($height / 2)-($angle / 2), $color, '../pic/captcha.ttf', $captchacode);
  
      ob_start();
      imagegif($captcha);
      $image_data = ob_get_contents();
      ob_end_clean();
      
      $xmlWriter->addChild('captcha', 'data:image/gif;base64,'.base64_encode($image_data), true);
      $_SESSION['captcha'] = $captchacode;

  }
  
require('api.end.php');

?>