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
        <h1 pgcms=182></h1>
      </div>
      
<div id="control-header" class="panel panel-default" style="box-sizing: border-box; display: flex; flex-direction: row; flex-wrap: wrap;">

  <div style="vertical-align: top; margin-right: 1em; width: auto;">
    <span style="font-weight: bold;" pgcms=183></span><br />
    <div class="dialog_multiedit">
      <input type="text" id="newname" onfocus="$(this).select();" style="margin-right: 0.5em;" />
      <input type="text" id="newaddress" onfocus="$(this).select();" style="margin-right: 0.5em;" />
    </div>
    <input type="button" pgcms=184 onclick="addaddress();"/>
    <br /><br />
    
    <span style="font-weight: bold;" pgcms=185></span><br />
    <select id="addusers" style="width: 100%;">
      <option value="0" pgcms=186></option>
      <option value="1" pgcms=187></option>
      <option value="2" pgcms=188></option>
    </select><br />
    <input type="button" onclick="addusers($('#addusers').val());" pgcms=184 />
  </div>

  <div style="vertical-align: top; width: auto; flex-grow: 1;">
    <select id="address_list" ondblclick="editelement();" multiple style="width: 100%; min-height: 24vh !important;" size=10></select><br />
    <input type="button" pgcms=9 style="min-width: 30px;" onclick="deleteaddress();" />
    <input type="button" pgcms=189 style="min-width: 30px;" onclick="savelist();" />
    <input type="button" pgcms=190 style="min-width: 30px;" onclick="loadlist();" />
    <input type="button" pgcms=191 style="min-width: 30px;" onclick="importlist();" />
  </div>
  
</div>

<div id="control-header" class="panel panel-default">
  <span style="font-weight: bold;" pgcms=132></span><br />
  <input type="text" id="subject" style="width: 100%; margin-bottom: 0.5em;" /><br />
  
  <span style="font-weight: bold;" pgcms=134></span><br />
  <div class="editor_container"><textarea id="body" class="dialog_texteditor article"></textarea></div>
</div>

<div id="control-header" class="panel panel-default">
  <input type="button" onclick="sendmassmail();" class="btn-block" pgcms=192 />
</div>

    </div>

</div>
</div>


<?php
  require('include/footer.php');
?>