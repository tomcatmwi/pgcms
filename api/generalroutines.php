<?php

require_once('class.phpmailer.php');
require_once('dbsettings.php');

//  ------------------------------------------------------------------
//  CONNECTDATABASE function
//  Connects to the database server

function connectdatabase() {

   $dbconn = pg_connect('host='.$GLOBALS['database_server'].'
                         port='.$GLOBALS['database_port'].' 
                         user='.$GLOBALS['database_user'].' 
                         password='.$GLOBALS['database_password'].' 
                         dbname='.$GLOBALS['database_name'])
                         or die('Unable to connect PostgreSQL. '.pg_last_error());

   return $dbconn;

}

//  ------------------------------------------------------------------
//  GETSETTING function
//  Returns a value from the settings database
//  Set $full to 1 to get the entire record

function getsetting($key, $full=0) {

  $return = Array('label'=>'ERROR',
                  'value'=>'',
                  'explanation'=>'Setting \''.$key.'\' not found');
  
  if (file_exists('xmldata/settings.xml')) { $xml = simplexml_load_file('xmldata/settings.xml'); }
  else { $xml = simplexml_load_file('../xmldata/settings.xml'); }
  
  foreach($xml->setting as $setting) {
    if ($setting['id'] == $key) {
      $return['label'] = $setting->label[0];
      $return['value'] = $setting->value[0];
      $return['explanation'] = $setting->explanation[0];
      $return['status'] = $setting->status[0];
    }
  }
  
  if ($full == 1) { return($return); } 
  else { return($return['value']); }
}

//  ------------------------------------------------------------------
//  XMLERROR function
//  Stops the API and returns an XML error

function xmlerror($id, $replace='') {

  $xml = new SimpleXMLElement('<pgcms></pgcms>');
  
  if ($_SESSION['ui'] == 'admin') {
    $lang = getsetting('ADMIN_UI_LANGUAGE');
  } else {
    if (isset($_SESSION['language'])) { $lang = $_SESSION['language']; }
    else { $lang = getsetting('DEFAULT_LANGUAGE'); }
  }
  
  if (!file_exists('../xmldata/errors_'.$lang.'.xml')) { $lang = getsetting('DEFAULT_LANGUAGE'); }
  $errorxml = simplexml_load_file('../xmldata/errors_'.$lang.'.xml');

  $message = 'ERROR MESSAGE NOT SET!';
  $module = '';
  $cmd = '';

//  Find error message
  
  foreach($errorxml->error as $error) {
    if ($error['id'] == $id) {
      $message = $error->message;
      $module = $error->module;
      $cmd = $error->cmd;
    }
  }

//  Replace strings in message

  if ($replace != '') {
    $replace_1 = explode('##', $replace);
    foreach ($replace_1 as $replace_2) {
      $replace_3 = explode('|', $replace_2);
      $message = str_ireplace($replace_3[0], $replace_3[1], $message);
    }
  }

//  Generate XML
  
  $node = $xml->addChild('error');
  $node->addChild('id', $id);
  $node->addChild('message', $message);
  $node->addChild('module', $module);
  $node->addChild('cmd', $_POST['cmd']);
  
  die(formatxml($xml->asXML()));

}

function removeaccents($source)
   {
		$fubar = $source;
		$fubar = str_replace('Å¹', 'ô', $fubar);
		$fubar = str_replace('Å?', 'ô', $fubar);
		
		return($fubar);
   }
   

//  ------------------------------------------------------------------
//  SENDFULLMAIL function
//  Sends an e-mail with the specified settings
 
function sendfullmail($address, $subject, $body, $from, $fromname, $cc=null, $attachment=null, $customheader=null) {

/*
  $lofasz = file_get_contents('c:\mail.txt');
  $lofasz .= chr(10).chr(13).chr(10).chr(13).'----------------------------------------------------------------------------------------------------------------------------------'.chr(10).chr(13).chr(10).chr(13);
  $lofasz .= 'address: '.$address.chr(10).chr(13);
  $lofasz .= 'subject: '.$subject.chr(10).chr(13);
  $lofasz .= 'from: '.$from.chr(10).chr(13);
  $lofasz .= 'fromname: '.$fromname.chr(10).chr(13);
  $lofasz .= 'cc: '.$cc.chr(10).chr(13);
  $lofasz .= chr(10).chr(13).chr(10).chr(13);
  $lofasz .= $body;
  file_put_contents('c:\mail.txt', $lofasz);
  return true;
*/

	$mail = new PHPMailer(true);
  $mail->CharSet = 'utf-8';

	$mail->IsSMTP();
	$mail->Host = getsetting('SMTP_HOST');
	$mail->SMTPAuth = (getsetting('SMTP_AUTH')==1);
	$mail->Username = getsetting('SMTP_USER');  
	$mail->Password = getsetting('SMTP_PASS');
  
  $sendmail = getsetting('SMTP_SENDMAIL_PATH');
  if ($sendmail != '') {
    $mail->isSendmail(); 
    $mail->Sendmail = $sendmail;
  }

	$mail->From =  utf8_decode($from);
	$mail->FromName = utf8_decode($fromname);
	$mail->AddAddress($address);
	$mail->AddReplyTo($from, utf8_decode($fromname));
	$mail->AddReplyTo($cc);

	$mail->WordWrap = getsetting('SYSTEMMSG_WORDWRAP'); 
	$mail->IsHTML(true); 
	
	$mail->Subject = stripslashes($subject);
	$mail->Body = stripslashes($body);

	if ($attachment != '') {
		$mail->AddAttachment($attachment);
	}
	
	if ($customheader != '') {
		$mail->AddCustomHeader($customheader);
	}
    	
	$result = null;
	if (!$mail->Send()) { $result = ($mail->ErrorInfo); }

  $mail->ClearAddresses();
  $mail->ClearAttachments();
    
  return($result);
  
}

//  ------------------------------------------------------------------
//  JSALERT function
//	Displays a JavaScript error alert

function jsalert($msg) {
	echo('<SCRIPT LANGUAGE="JavaScript">');
	echo('alert(\''.$msg.'\');');
	echo('</SCRIPT>');
}

//  ------------------------------------------------------------------
//  DIRECTORYLIST function
//	Lists all files from a directory. Set recurse=true for subdirs included.

function directorylist($dir, $recurse=false)
  {
  
  if (!file_exists($dir)) { return false; }

// array to hold return value

    $retval = array();

// add trailing slash if missing

    if(substr($dir, -1) != "/") $dir .= "/";

// open pointer to directory and read list of files

    $d = @dir($dir) or die();
    while(false !== ($entry = $d->read())) {

// skip hidden files

      if($entry[0] == '.') continue;
      if(is_dir("$dir$entry")) {
        $retval[] = array(
          "name" => "$dir$entry/",
          "type" => filetype("$dir$entry"),
          "size" => 0,
          "lastmod" => filemtime("$dir$entry")
        );

       if($recurse && is_readable("$dir$entry/")) {
          $retval = array_merge($retval, directorylist("$dir$entry/", true));
        }
      } elseif(is_readable("$dir$entry")) {
        $retval[] = array(
          "name" => "$dir$entry",
          "filename" => "$entry",
          "size" => filesize("$dir$entry"),
          "lastmod" => filemtime("$dir$entry")
        );
      }
    }
    $d -> close();

    return $retval;
  }


//  ------------------------------------------------------------------
//  REAL_EASTER_DATE function
//	Returns the day of Easter in a given year
//  (C) BigTree

function real_easter_date ($Year) {
  
//     G is the Golden Number-1
//     H is 23-Epact (modulo 30)
//     I is the number of days from 21 March to the Paschal full moon
//     J is the weekday for the Paschal full moon (0=Sunday, 1=Monday, etc.)
//     L is the number of days from 21 March to the Sunday on or before
//     the Paschal full moon (a number between -6 and 28)

	$G = $Year % 19;
	$C = (int)($Year / 100);
	$H = (int)($C - (int)($C / 4) - (int)((8*$C+13) / 25) + 19*$G + 15) % 30;
	$I = (int)$H - (int)($H / 28)*(1 - (int)($H / 28)*(int)(29 / ($H + 1))*((int)(21 - $G) / 11));
	$J = ($Year + (int)($Year/4) + $I + 2 - $C + (int)($C/4)) % 7;
	$L = $I - $J;
	$m = 3 + (int)(($L + 40) / 44);
	$d = $L + 28 - 31 * ((int)($m / 4));
	$y = $Year;
	$E = mktime(0,0,0, $m, $d, $y);
	return $E;

} 
  
//  ------------------------------------------------------------------
//  DELETEARRAYROW function
//  Deletes the specified row from an array and compacts it

function deletearrayrow($array, $index) {

	$bertafing = Array();
	for ($t=0; $t<count($array); $t++) {
		if ($t != $index) { array_push($bertafing, $array[$t]); }
	}
	return ($bertafing);
}

//  ------------------------------------------------------------------
//  DATEDIFF function
//  Calculates the number of days between two dates

function datediff ($date1, $date2) {

	$date1 = mktime(0,0,0, substr($date1, 5, 2), substr($date1, 8, 2), substr($date1, 0, 4));
	$date2 = mktime(0,0,0, substr($date2, 5, 2), substr($date2, 8, 2), substr($date2, 0, 4));
	
	return(abs(intval(($date1 - $date2)/86400+1))+1);
}

//  GETEMAIL function
//  Returns the specified e-mail template

function getemail($token, $lang=0, $headers=true) {

  if ($lang == 0) {
    if (!isset($_SESSION['language']) || $_SESSION['language'] == '') { $lang = getsetting('DEFAULT_LANGUAGE'); }
    else { $lang = $_SESSION['language']; }
  }
  
  if (file_exists('xmldata/emails_'.$lang.'.xml')) { $xml = simplexml_load_file('xmldata/emails_'.$lang.'.xml'); }
  else { $xml = simplexml_load_file('../xmldata/emails_'.$lang.'.xml'); }
  if ($xml === false) { return false; }
  
  foreach($xml->email as $email) {

    if ($headers) {
      if ($email->id == 'signature') { $signature = $email->body; }
      if ($email->id == 'header') { $header = $email->body; }
    } else {
      $signature = '';
      $header = '';
    }

    if ($email->id == $token) {
      $return = Array(
                       'id' => $email->id,
                       'subject' => $email->subject,
                       'body' => $header.$email->body.$signature,
                      );
    }
    
    if ($token == '') {
      $return = Array(
                       'id' => $email->id,
                       'subject' => '',
                       'body' => $header.$signature,
                      );
    }
    
  }

  $return['body'] = str_ireplace('%frontend_root%', getsetting('FRONTEND_ROOT'), $return['body']);
  $return['body'] = str_ireplace('%sendername%', getsetting('AUTOMAIL_SENDER'), $return['body']);
  $return['body'] = str_ireplace('%senderemail%', getsetting('AUTOMAIL_SENDER_ADDRESS'), $return['body']);
  return($return);
  
}


//  ------------------------------------------------------------------
//  CURRENTSCRIPT function
//	Returns the current PHP script filename

function currentscript() {
  $file = $_SERVER["SCRIPT_NAME"];
  $break = Explode('/', $file);
  $filename = $break[count($break) - 1];
  return $filename;
}

//  ------------------------------------------------------------------
//  FORMATXML function
//	Formats an XML

function formatxml($xml) {  
  
  // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
  $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);
  
  // now indent the tags
  $token      = strtok($xml, "\n");
  $result     = ''; // holds formatted version as it is built
  $pad        = 0; // initial indent
  $matches    = array(); // returns from preg_matches()
  
  // scan each line and adjust indent based on opening/closing tags
  while ($token !== false) : 
  
    // test for the various tag states
    
    // 1. open and closing tags on same line - no change
    if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) : 
      $indent=0;
    // 2. closing tag - outdent now
    elseif (preg_match('/^<\/\w/', $token, $matches)) :
      $pad--;
    // 3. opening tag - don't pad this one, only subsequent tags
    elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
      $indent=1;
    // 4. no indentation needed
    else :
      $indent = 0; 
    endif;
    
    // pad the line with the required number of leading spaces
    $line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
    $result .= $line . "\n"; // add to the cumulative result, with linefeed
    $token   = strtok("\n"); // get the next token
    $pad    += $indent; // update the pad size for subsequent lines    
  endwhile; 
  
  return $result;
}

function debug($string) {
  $file = fopen('c:\debug.txt', 'a');
  fwrite($file, $string);
  fclose($file);
}

//  ------------------------------------------------------------------
//  DATELOCAL function
//  Converts a GMT timestamp to a localized date form.

function datelocal($timestamp, $timezone='UTC', $language=0, $time=true) {

    if ($language == 0) {
      if (!isset($_SESSION['language'])) { $language = getsetting('DEFAULT_LANGUAGE'); }
      else { $language = $_SESSION['language']; }
    }
    
    $months = localmsg('', 'months', $language);
    $days = localmsg('', 'daysofweek', $language);
    $lang = getlanguage($language);

    $timezone = new DateTimeZone($timezone);
    $gmt = new DateTimeZone('UTC');

    $gmttime = new DateTime('now', $gmt);
    $gmttime->setTimestamp($timestamp);
    
    $offset = $timezone->getOffset($gmttime);
    
    $timestamp += $offset;
    
    $datetime = new DateTime('UTC');
    $datetime->setTimestamp($timestamp);
    
    $return = str_ireplace('y', $datetime->format('Y'), $lang['date']);
    $return = str_ireplace('d', $datetime->format('d'), $return);
    $return = str_ireplace('m', $months['MONTH_'.$datetime->format('m')], $return);
    if ($time) { $return .= date(' '.$datetime->format('H:i')); }
    
    return $return;

}

//  ------------------------------------------------------------------
//  TZCONVERT function
//  Converts a date/time to another time zone.
//  Invoking without parameters return the current GMT time.

function tzconvert($timestamp=null, $tz1='GMT', $tz2='GMT') {
  if (is_numeric($timestamp)) { $timestamp = date('Y-m-d H:i:s', $timestamp); }
  $datetime = new DateTime($timestamp, new DateTimeZone($tz1));
  $offset1 = date_offset_get($datetime);
  $datetime->setTimezone(new DateTimeZone($tz2));
  $offset2 = date_offset_get($datetime);
  $offset = abs($offset1 - $offset2);
  if ($offset1 > $offset2) { $offset = -$offset; }
  $timestamp = (integer)$datetime->format('U');
  return ($timestamp + $offset);
}

//  ------------------------------------------------------------------
//  Locale functions
//	Set locale settings and save them as session variables.
//  To be implemented: Save values to database for each user
  
  function setlanguage($id) {

    $languages = simplexml_load_file('../xmldata/languages.xml');
    $found = 0;
    foreach($languages as $language) {
      if ($language['id'] == $id) {
          $_SESSION['language'] = (string)$language['id'];
          $_SESSION['language_name'] = (string)$language->name;
          $_SESSION['language_nameeng'] = (string)$language->nameeng;
          $_SESSION['language_date'] = (string)$language->date;
          $_SESSION['language_thousandsseparator'] = (string)$language->thousandsseparator;
          $_SESSION['language_decimalpoint'] = (string)$language->decimalpoint;
          $_SESSION['language_encoding'] = (string)$language->encoding;
          $_SESSION['language_langcode'] = (string)$language->langcode;
          $_SESSION['language_googlecode'] = (string)$language->googlecode;
          $_SESSION['language_flag'] = (string)$language->flag;
          $found++;
      }
    }
    if ($found == 0) setlanguage(getsetting('DEFAULT_LANGUAGE'));
  }
  
//  ------------------------------------------------------------------
//  GETLANGUAGE function
//	Returns the parameters of a given language

function getlanguage($id) {

   $return = Array('id' => 0,
                    'name' => '',
                    'nameeng' => '',
                    'ui' => 0,
                    'date' => 'd. m y.',
                    'encoding' => 'utf8',
                    'langcode' => 'en-EN',
                    'thousandsseparator' => ',',
                    'decimalpoint' => '.',
                    'flag' => ''
                    );

   global $languages;
   if ($languages == '') { $languages = simplexml_load_file('../xmldata/languages.xml'); }
   
   foreach($languages as $language) {
   if ($language['id'] == $id) {
      $return['id'] = $language['id'];
      $return['name'] = (string)$language->name;
      $return['nameeng'] = (string)$language->nameeng;
      $return['ui'] = (integer)$language->ui;
      $return['date'] = (string)$language->date;
      $return['encoding'] = (string)$language->encoding;
      $return['langcode'] = (string)$language->langcode;
      $return['thousandsseparator'] = (string)$language->thousandsseparator;
      $return['decimalpoint'] = (string)$language->decimalpoint;
      $return['flag'] = (string)$language->flag;
    }
   }
   
   return $return;
}

//  ------------------------------------------------------------------
//  RESIZEIMAGE function
//	Resizes an image and returns it
//
//  $image: An image resource.
//  $width, $height: New picture size
//  $square: Should the result be a square (letterboxed)
//  $proportional: Proportional resize
//  $background: Canvas color (RGB hex)

function resizeimage($image, $width, $height, $square=0, $proportional=1, $background='000000') {

     $fullwidth = imagesx($image);
     $fullheight = imagesy($image);
     
     if ($proportional == 1) {
            $origwidth = $width;
            $origheight = $height;
            
            $percent = round($width / ($fullwidth / 100)); 
            $height = round(($fullheight / 100) * $percent);
            
            if ($height > $origheight) {
              $height = $origheight;
              $percent = round($height / ($fullheight / 100)); 
              $width = round(($fullwidth / 100) * $percent);
            }
     }
              
     $width = round($width);
     $height = round($height);

     $resized = imagecreatetruecolor($width, $height); 
     imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $fullwidth, $fullheight);

     if ($square == 1) {
        $squarewidth = $width;
        if ($height > $width) { $squarewidth = $height; }
        $square_image = imagecreatetruecolor($squarewidth, $squarewidth);

        if (strlen($background) == 6) {
            $red = hexdec(substr($background, 0, 2));
            $green = hexdec(substr($background, 2, 2));
            $blue = hexdec(substr($background, 4, 2));
        } else {
            $red = 0;
            $green = 0;
            $blue = 0;
        }
                        
        imagefilledrectangle($square_image, 0, 0, $squarewidth, $squarewidth, imagecolorallocate($square_image, $red, $green, $blue));
        if ($squarewidth > $width) {
            imagecopy($square_image, $resized, round(($squarewidth-$width) / 2), 0, 0, 0, $width, $height);
        } else {
            imagecopy($square_image, $resized, 0, round(($squarewidth-$height) / 2), 0, 0, $width, $height);
        }
            return($square_image);
            
     } else { return($resized); }

}

//  ------------------------------------------------------------------
//  SANITIZE function
//	Sanitizes a string to be used as a filename.

function sanitize($f, $noslash=true) {

   $f = rtrim(ltrim($f));

   $replace_chars = array(
       'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
       'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
       'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
       'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
       'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
       'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
       'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f', 'ű'=>'u', 'ő'=>'o', 'Ű'=>'u', 
       'Ő'=>'o', 'ü'=>'u', 'Ü'=>'u'
   );
   $f = strtr($f, $replace_chars);
   // convert & to "and", @ to "at", and # to "number"
   $f = preg_replace(array('/[\&]/', '/[\@]/', '/[\#]/'), array('-and-', '-at-', '-number-'), $f);
   $f = preg_replace('/[^(\x20-\x7F)]*/','', $f); // removes any special chars we missed
   $f = str_replace(' ', '_', $f); // convert space to hyphen 
   $f = str_replace('.', '_', $f); // convert period to hyphen 
   $f = str_replace('&', '_', $f); // convert ampersand to hyphen 
   $f = str_replace('\'', '', $f); // removes apostrophes
   if ($noslash) { 
    $f = preg_replace('/[^\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
    }
   else {
    $f = preg_replace('/[^\x2F\w\-\.]+/', '', $f); // remove non-word chars (leaving hyphens and periods)
   }
   $f = preg_replace('/[\-]+/', '-', $f); // converts groups of hyphens into one
   return strtolower($f);
}

//  ------------------------------------------------------------------
//  DISTANCE function
//	Returns the distance between two GPS coordinates

function distance($lat1, $lon1, $lat2, $lon2, $imperial=false) {

    $delta_lat = $lat2 - $lat1 ;
    $delta_lon = $lon2 - $lon1 ;
    $alpha    = $delta_lat/2;
    $beta     = $delta_lon/2;
    $a        = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($beta)) * sin(deg2rad($beta)) ;
    $c        = asin(min(1, sqrt($a)));
    $distance = 2 * 3960 * $c;
    $distance = round($distance, 4);
    if (!$imperial) { $distance = $distance * 1.609344; }
    return $distance;
}

//  ------------------------------------------------------------------
//  CONVERTCURRENCY function
//	Converts an amount from one currency to another

function convertcurrency($amount, $source='EUR', $dest='EUR') {

// check if xml file exists, is up to date and is a valid xml

      $update = false;
      if (!file_exists('../xmldata/currencyrates.xml') || date('Y-m-d', filemtime('../xmldata/currencyrates.xml')) < date('Y-m-d')) $update = true;
      
      if (!$update) {
        $currencyxml = simplexml_load_file('../xmldata/currencyrates.xml');
        if (!$currencyxml) $update = true;
      } 
      
// update xml
  
      if ($update) {
        
        $currencyxml = simplexml_load_file('http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml');
        if (!$currencyxml) { xmlerror(25); $error = 25; }
        else {
          @file_put_contents('../xmldata/currencyrates.xml', formatxml($currencyxml->asXML()));
        }
      }
        
//  load current rates from local file
  
      $currencyxml = simplexml_load_file('../xmldata/currencyrates.xml');
      if (!$currencyxml) { return false; }

//  calculate rate
      
    $rate1 = 0;
    $rate2 = 0;
    $found1 = 0;
    $found2 = 0;
    $update_date = $currencyxml->Cube->Cube['time'];
    
    foreach($currencyxml->Cube->Cube->Cube as $cube) {
      if ($cube['currency'] == $source) { $found1 = 1; $rate1 = $cube['rate']; }
      if ($cube['currency'] == $dest) { $found2 = 1; $rate2 = $cube['rate']; }
    }

    if ($source == 'EUR') { $found1 = 1; $rate1 = 1; }
    if ($dest == 'EUR') { $found2 = 1; $rate2 = 1; }
    
    if ($found1 == 0 || $found2 == 0) { return false; }

//  do real calculation
  
    $return = (((float)$amount / (float)$rate1) * (float)$rate2);
    
//  cut decimals, do rounding if necessary

    global $currencies;
    
    if ($currencies[1] == null || $currencies[1] == false) {
        $currencies[1] = simplexml_load_file('../xmldata/currencies_1.xml');
    }
    
    if (!$currencies[1]) { return false; }

    $decimals = 2;
    
    foreach($currencies[1] as $currency) {
        if ($dest == $currency['code']) {
            $decimals = (integer)$currency->decimals;
        }
    }
    
    $return = round($return, $decimals);
    return $return;

}


//  ------------------------------------------------------------------
//  XMLDIE function
//	Drops an XML error with the specified message

function xmldie($message) {
  xmlerror(1001, '%errormsg%|'.$message);  
}

?>