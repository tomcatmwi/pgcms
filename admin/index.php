<?php
  session_start();
  if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && $_SESSION['user_admin'] == 1) { 
    require_once('include/header.php');
  } else {
    header('Location: login.php');
  }
?>

<div class="container">

    <div class="row">

      <div class="page-header">
        <h1 pgcms=2></h1>
        <span pgcms=3></span>
      </div>
      
        <div id="messages" class="list-group">
          <a href="messages.php?unread=true" class="list-group-item"><span id="message_counter" class="label label-danger">0</span> <span pgcms=1></span></a>
        </div>
          
        </div>

    </div>

  </div>
</div>

<?php
  require('include/footer.php');
?>
