<?php
  session_start();
  if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && $_SESSION['user_admin'] == 1) { 
    require_once('include/header.php');
  } else {
    header('Location: login.php');
  }
?>

<div class="container" style="margin-bottom: 2em;">

    <div class="row">

        <h1 pgcms=201></h1>
        <div class="alert alert-danger" pgcms=202></div>

<!--  Táblázat adatoknak -->

        <div id="no-more-tables">
            <table class="col-md-12 table-bordered table-striped table-condensed cf">
        		<thead class="cf" id="main_table_header"></thead>
        		<tbody id="main_table_body"></tbody>
        	</table>
        </div>
    </div>

</div>
</div>

<?php
  require('include/footer.php');
?>
