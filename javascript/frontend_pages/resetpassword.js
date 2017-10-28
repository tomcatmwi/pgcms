$(document).ready(function() {
  $.ajaxSetup({ cache: false });
  filllanguageselector();
  
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/resetpassword.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('pgcms=general.formnotfound');
                return false;
            }

            popup_id = createdialog('pgcms=resetpassword.dialog_title', xhr.responseText, [['pgcms=general.ok_button', resetpassword], ['pgcms=general.cancel_button', null]]);
            $('#resetpassword_resetcode').val(getparam('id'));
            loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
        }
    });
});

function resetpassword() {

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.userdata.php',
        data: 'cmd=resetpassword&'+
              'resetcode='+encodeURIComponent($('#resetpassword_resetcode').val())+'&'+
              'username='+encodeURIComponent($('#resetpassword_username').val())+'&'+
              'password1='+encodeURIComponent($('#resetpassword_password1').val())+'&'+
              'password2='+encodeURIComponent($('#resetpassword_password2').val()),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') return false;
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
              error = $(this).find('id').text();
              errormsg($(this).find('message').text());
            });

            if (error == 0) {
              hidedialog();
              $('#resetpassword_successful').css('display', 'block');
              $('#resetpassword_failed').css('display', 'none');
            }
        }
    });

}