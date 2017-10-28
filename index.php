<?php
  require('header.php');
?>

    <h1>pgCMS test page</h1>
    
    <b>Language selector:</b><br />
    <select id="language_selector" onchange="changelanguage($(this).val());"></select>
    <br /><br />
    
    <b>Test text:</b><br />
    <div style="border: 1px solid #CCCCCC; padding: 1em;" pgcms="test.test">If you see this then it's not working.</div>
    <br /><br />
    
    <b>Test article:</b><br />
    <div class="article" style="border: 1px solid #CCCCCC; padding: 1em;" pgcms_article="test.test_article,body">If you see this then it's not working.</div>
    <br /><br />
    
    <b>Functions:</b><br />
    <a onclick="registerform();">Registration form</a><br />
    <a onclick="contactform();">Contact form</a><br />
    <br />
    
    <b>Current user:</b><br />
    <div id="menubar_userdata"></div>

<?php
  require('footer.php');
?>
