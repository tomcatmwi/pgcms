<?php

//    pgCMS API 2.0
//    (c) 2013/16 pixeldog industries
//    tigers kill cats lol

  $exec_start = microtime(true);
  
  session_start();
  session_set_cookie_params(1200);

  header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
  header('Cache-Control: no-store, no-cache, must-revalidate');
  header('Cache-Control: post-check=0, pre-check=0', false);
  header('Pragma: no-cache');

  require_once('generalroutines.php');
  $dbconn = connectdatabase();
  
//  Set system defaults

  date_default_timezone_set(getsetting('DEFAULT_TIMEZONE'));
  if (!isset($_SESSION['language']) || $_SESSION['language'] == '' || !is_numeric($_SESSION['language'])) { setlanguage((string)getsetting('DEFAULT_LANGUAGE')); }

//  XMLWriter extension

  class AdvancedXMLWriter extends XMLWriter {
    public function addChild($name, $value, $cdata = false) {
      parent::startElement($name);
      if (!$cdata) { parent::text($value); } 
      else { parent::writeCData($value); }
      parent::endElement();
    }
  }

//  Start XML output

  $xmlWriter = new AdvancedXMLWriter();
  $xmlWriter->openMemory();
  $xmlWriter->setIndent(true);
  $xmlWriter->startDocument('1.0','UTF-8');
  $xmlWriter->startElement('pgcms');

  if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {

    $xmlWriter->addChild('user_id', $_SESSION['user_id']);
    $xmlWriter->addChild('user_name', $_SESSION['user_name']);
    $xmlWriter->addChild('user_email', $_SESSION['user_email']);
  }

  $xmlWriter->addChild('user_language', $_SESSION['language']);
  $xmlWriter->addChild('cmd', $_POST['cmd']);

//  Check captcha

  if (isset($_POST['captcha']) && strtoupper($_POST['captcha']) != strtoupper($_SESSION['captcha'])) { xmlerror(22); }

//  Set 'undefined' fields to empty strings

  foreach($_POST as $key=>$value) {
    if ($value == 'undefined') { $_POST[$key] = ''; }
  }

?>