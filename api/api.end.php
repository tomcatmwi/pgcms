<?php

  if (isset($_POST['captcha']) && strtoupper($_POST['captcha']) == strtoupper($_SESSION['captcha'])) { unset($_SESSION['captcha']); }

//  output --------------------------------------------------

  pg_close();
  
  $xmlWriter->addChild('exectime', microtime(true) - $exec_start);
  
  $xmlWriter->endElement();
  $xmlWriter->endDocument();
  echo($xmlWriter->outputMemory());

?>
