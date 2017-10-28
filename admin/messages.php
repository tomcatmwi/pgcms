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
        <h1 pgcms=194></h1>
      </div>

<!--  Header dolgoknak -->

<div id="control-header" class="panel panel-default">

  <div class="container">
  
  <div class="control-header-section">
    <input type="button" value="<<" onclick="$('#search').val(''); nextday(-1);" />
    <select id="msgdate_year" style="min-width: 5em !important; width: 5em !important;" onchange="$('#search').val(''); loadmessages($('#msgdate_year').val(), $('#msgdate_month').val(), $('#msgdate_day').val());"></select>
    <select id="msgdate_month" style="min-width: 7.6em !important; width: 7.6em !important;" onchange="$('#search').val(''); loadmessages($('#msgdate_year').val(), $('#msgdate_month').val(), $('#msgdate_day').val());"></select>
    <select id="msgdate_day" style="min-width: 4em !important; width: 4em !important;" onchange="$('#search').val(''); loadmessages($('#msgdate_year').val(), $('#msgdate_month').val(), $('#msgdate_day').val());">
      <option value="0">--</option>
    </select>
    <input type="button" value=">>" onclick="nextday(1);" />
    <input type="button" pgcms=195 onclick="$('#search').val(''); var date = new Date(); loadmessages(date.getFullYear(), date.getMonth()+1, date.getDate());" />
  </div>

  <div class="control-header-section">
    <select id="folder" style="min-width: 100% !important; width: auto;" onchange="$('#search').val(''); loadmessages($('#msgdate_year').val(), $('#msgdate_month').val(), $('#msgdate_day').val());">
      <option value="0" pgcms=196></option>
    </select>
  </div>

  <div class="control-header-section">
    <input type="text" id="search" pgcms=62 onfocus="$(this).select();" />
    <input type="button" pgcms=197 onclick="$('#folder').val(0); var date = new Date(); loadmessages(date.getFullYear(), date.getMonth()+1, date.getDate(), $('#search').val());" />
  </div>

  <div class="control-header-section">
    <input type="button" pgcms=198 onclick="$('#folder').val(0); $('#search').val(''); var date = new Date(); loadmessages(date.getFullYear(), date.getMonth()+1, date.getDate(), '', true);" />
  </div>

  </div>
  
</div>

<!--  Táblázat adatoknak -->

    <table id="maintable" class="col-md-12 table-bordered table-striped table-condensed" style="margin-bottom: 1em; min-width: 100%;"></table>

    </div>
    </div>

</div>

<?php
  require('include/footer.php');
?>
