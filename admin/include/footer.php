<!-- Vége -->
    
    <footer class="footer">
      <div class="container">
        
        <p class="text-muted" id="statusbar">
          <img src="../pic/pixeldog_mini.png" alt="Pixeldog" style="vertical-align: middle; display: inline-block; margin-right: 1em;" />
          <span id="footer_version"></span>
        </p>
      </div>
    </footer>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

    <!-- Bootstrap JS files -->
		
    <script src="../javascript/admin_bootstrap/bootstrap.min.js"></script>
    <script src="../javascript/admin_bootstrap/ie10-viewport-bug-workaround.js"></script>

    <!-- Admin system JS files -->

    <script type="text/javascript" src="../javascript/general.js"></script>
    <script type="text/javascript" src="../javascript/admin.js"></script>
    <script type="text/javascript" src="../javascript/dialogs.js"></script>
    <script type="text/javascript" src="../javascript/gallery.js"></script>
    <script type="text/javascript" src="../javascript/filestorage.js"></script>
    <script type="text/javascript" src="../javascript/jquery.rte.js"></script>
    <script type="text/javascript" src="../javascript/jquery.rte.tb.js"></script>
    <script type="text/javascript" src="../javascript/admin_pages/footer.js"></script>

<?php
      $_SESSION['ui'] = 'admin';
      $file = $_SERVER["SCRIPT_NAME"];
      $break = Explode('/', $file);
      $filename = $break[count($break) - 1];
      $filename = substr($filename, 0, stripos($filename, '.'));
      if (file_exists('../javascript/admin_pages/'.$filename.'.js')) { echo('    <script src="../javascript/admin_pages/'.$filename.'.js"></script>'."\n"); }
?>

<script type="text/javascript">

</script>

</body>
</html>
