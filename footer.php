    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="javascript/general.js"></script>
    <script src="javascript/dialogs.js"></script>
    <script src="javascript/frontend.js"></script>
    <script src="javascript/frontend_pages/allpages.js"></script>

<?php
      $_SESSION['ui'] = 'frontend';
      $file = $_SERVER["SCRIPT_NAME"];
      $break = explode('/', $file);
      $filename = $break[count($break) - 1];
      $filename = substr($filename, 0, stripos($filename, '.'));
      if (file_exists('javascript/frontend_pages/'.$filename.'.js')) { echo('    <script src="javascript/frontend_pages/'.$filename.'.js"></script>'."\n"); }
?>

</body>
</html>