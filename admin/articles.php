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
        <h1 pgcms=176></h1>
      </div>

<!--  Header dolgoknak -->

<div id="control-header" class="panel panel-default">
  <div class="container">
  
  <div class="control-header-section">
    <input type="button" pgcms=177 onclick="newrecord();" />
    <input type="button" pgcms=178 onclick="translate_all();" />
  </div>

  <div class="control-header-section">
    <input type="text" id="search" pgcms=62 onclick="$(this).select();" />
  </div>

  <div class="control-header-section">
    <input type="button" pgcms=179 onclick="imagegallery();" />
    <input type="button" pgcms=320 onclick="filestorage();" />
  </div>

  
  <div class="control-header-section" style="float: right; margin-top: 3px;">
    <select id="paginator" onchange="paginator_change();"></select>
  </div>
  
  </div>
  
</div>

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
