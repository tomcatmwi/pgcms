function loaduserdata(user_id, user_name) {

  $('#menubar_userdata').empty();
  var div = $('<div></div>');
    
  if (typeof(user_id) === 'undefined' || isNaN(user_id) || user_id == '') {
     div.append($('<span></span>', { 'pgcms': 'mainmenu.userdata_notloggedin' }));
     div.append($('<br />'));
     div.append($('<a></a>', { 'onclick': 'loginform();', 'pgcms': 'mainmenu.login' })); 
  } else {
     div.append($('<span></span>', { 'pgcms': 'mainmenu.userdata_loggedinas', 'style': 'margin-right: 0.5em;' }));
     div.append($('<span></span>', { 'id': 'mainmenu_username', 'style': 'color: #DDDDDD; font-weight: bold;' }));
     div.find('span:last').html(user_name);
     div.append($('<br />'));
     div.append($('<a></a>', { 'onclick': 'registerform('+user_id+');', 'pgcms': 'mainmenu.userdata', 'style': 'margin-right: 10px;' })); 
     div.append($('<a></a>', { 'onclick': 'logoutform();', 'pgcms': 'mainmenu.logout', 'style': 'margin-right: 10px;' })); 
  }
  
  $('#menubar_userdata').append(div);  
}

function contactform() {

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/contact.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('pgcms=general.formnotfound');
                return false;
            }

            popup_id = createdialog('pgcms=contactform.dialog_title', xhr.responseText, [['pgcms=general.send_button', contactform_send], ['pgcms=general.cancel_button', null]]);
            newcaptcha('contact_captcha_image', function() {
               loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
            });
        }
    });
}

function contactform_send() {

   $.ajax({
      type: 'POST',
      cache: 'false',
      url: 'api/api.messages.php',
      data: 'cmd=findfaq&'+
            'name='+encodeURIComponent($('#contact_name').val())+'&'+
            'email='+encodeURIComponent($('#contact_email').val())+'&'+
            'phone='+encodeURIComponent($('#contact_phone').val())+'&'+
            'company='+encodeURIComponent($('#contact_company').val())+'&'+
            'text='+$.trim(encodeURIComponent($('#contact_text').val()))+'&'+
            'captcha='+encodeURIComponent($('#contact_captcha').val()),
            
      dataType: 'xml',

      complete: function(xhr) {
      
          console.log(xhr.responseText);
          return false;
      
          if (xhr.responseText == null || xhr.responseText == '') return false;
          var xml = $.parseXML(xhr.responseText);

          var error = 0;
          $(xml).find('error').each(function(){
            errormsg($(this).find('message').text());
            error = $(this).find('id').text();
          });
          
          if (error == 0) {
            hidelastdialog();
            errormsg('pgcms=contactform.sent_ok', 'info');
          }
      }
  });
}

function registerform(id) {

      $.ajax({
          type: 'GET',
          cache: 'false',
          url: 'forms/register.html?rnd='+$.now(),
          dataType: 'html',

          complete: function(xhr) {
              if (xhr.status != 200) {
                  errormsg('pgcms=general.formnotfound');
                  return false;
              }
              
              hidelastdialog();
              popup_id = createdialog('pgcms=registration.dialog_title', xhr.responseText, [['pgcms=registration.send_button', registerform_send], ['pgcms=general.cancel_button', null]]);
              newcaptcha('registration_captcha_image', function() {
                filllanguages('registration_language', true);

                fillcountries('registration_country', function() {
                
                $('#registration_company_country').empty();
                $('#registration_country option').clone().appendTo('#registration_company_country');
                $('#registration_company_country').val($('#registration_country').val());

                  if (typeof id !== 'undefined') {
                  
                     $.ajax({
                        type: 'POST',
                        cache: 'false',
                        url:  'api/api.userdata.php',
                        data: 'cmd=getuser',
                        
                        complete: function(xhr) {
                        
                            if (xhr.responseText == null || xhr.responseText == '') return false;
                            var xml = $.parseXML(xhr.responseText);
                            
                            var error = 0;
                            $(xml).find('error').each(function(){
                              errormsg($(this).find('message').text());
                              error = $(this).find('id').text();
                            });
                            
                            if (error == 0) {
                            
                              $(xml).find('user').each(function() {
                                 $('#registration_id').val($.trim($(this).find('id').text()));
                                 $('#registration_name').val($.trim($(this).find('name').text()));
                                 $('#registration_nickname').val($.trim($(this).find('nickname').text()));
                                 $('#registration_username').val($.trim($(this).find('username').text()));
                                 $('#registration_password1').val($.trim($(this).find('password').text()));
                                 $('#registration_password2').val($.trim($(this).find('password').text()));
                                 $('#registration_language').val($.trim($(this).find('language').text()));
                                 $('#registration_email').val($.trim($(this).find('email').text()));
                                 $('#registration_phone1').val($.trim($(this).find('phone1').text()));
                                 $('#registration_phone2').val($.trim($(this).find('phone2').text()));
                                 $('#registration_phone3').val($.trim($(this).find('phone3').text()));
                                 $('#registration_country').val($.trim($(this).find('country').text()));
                                 $('#registration_state').val($.trim($(this).find('state').text()));
                                 $('#registration_zip').val($.trim($(this).find('zip').text()));
                                 $('#registration_city').val($.trim($(this).find('city').text()));
                                 $('#registration_address').val($.trim($(this).find('address').text()));
                                 $('#registration_newsletter').prop('checked', $(this).find('newsletter').text() == '1');

                                 if ($(this).find('company').text() == '') {
                                    $('#registration_company').val('');
                                    $('#registration_company_state').val('');
                                    $('#registration_company_zip').val('');
                                    $('#registration_company_city').val('');
                                    $('#registration_company_address').val('');
                                    $('#company_data').css('height', '0px');
                                    $('#company_data').css('display', 'none');
                                    $('#registration_companydata').prop('checked', false)
                                 } else {
                                    $('#registration_companydata').prop('checked', true)
                                    $('#company_data').css('height', 'auto');
                                    $('#company_data').css('display', 'block');
                                    $('#registration_company').val($(this).find('company').text());
                                    $('#registration_company_country').val($(this).find('company_country').text());
                                    $('#registration_company_state').val($(this).find('company_state').text());
                                    $('#registration_company_zip').val($(this).find('company_zip').text());
                                    $('#registration_company_city').val($(this).find('company_city').text());
                                    $('#registration_company_address').val($(this).find('company_address').text());
                                 }

                                 $('#registration_captchadiv').remove();
                                 loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
                              });
                            }
                        }
                     });
                  
                  } else {
                    $('#registration_id').val('');
                    loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
                  }

              });
            });

          }
      });
      
}

function registerform_send() {

   if ($('#company').val() == '' || $('#registration_companydata').prop('checked') == false) {
       $('#company').val('');
       $('#company_state').val('');
       $('#company_zip').val('');
       $('#company_city').val('');
       $('#company_address').val('');
   }

  var data = 'cmd=register&'+
            'id='+encodeURIComponent($('#registration_id').val())+'&'+
            'name='+encodeURIComponent($('#registration_name').val())+'&'+
            'nickname='+encodeURIComponent($('#registration_nickname').val())+'&'+
            'username='+encodeURIComponent($('#registration_username').val())+'&'+
            'password1='+encodeURIComponent($('#registration_password1').val())+'&'+
            'password2='+encodeURIComponent($('#registration_password2').val())+'&'+
            'email='+encodeURIComponent($('#registration_email').val())+'&'+
            'zip='+encodeURIComponent($('#registration_zip').val())+'&'+
            'city='+encodeURIComponent($('#registration_city').val())+'&'+
            'address='+encodeURIComponent($('#registration_address').val())+'&'+
            'state='+encodeURIComponent($('#registration_state').val())+'&'+
            'country='+encodeURIComponent($('#registration_country').val())+'&'+
            'phone1='+encodeURIComponent($('#registration_phone1').val())+'&'+
            'phone2='+encodeURIComponent($('#registration_phone2').val())+'&'+
            'phone3='+encodeURIComponent($('#registration_phone3').val())+'&'+
            'language='+$('#registration_language').val()+'&'+
            'newsletter='+String($('#registration_newsletter').prop('checked'))+'&'+
            'company='+encodeURIComponent($('#company').val())+'&'+
            'company_zip='+encodeURIComponent($('#company_zip').val())+'&'+
            'company_city='+encodeURIComponent($('#company_city').val())+'&'+
            'company_address='+encodeURIComponent($('#company_address').val())+'&'+
            'company_state='+encodeURIComponent($('#company_state').val())+'&'+
            'company_country='+encodeURIComponent($('#company_country').val());

  if ($('#registration_id').val() == '') data += '&captcha='+$('#registration_captcha').val();

   $.ajax({
      type: 'POST',
      cache: 'false',
      url:  'api/api.userdata.php',
      data: data,
            
      dataType: 'xml',

      complete: function(xhr) {
      
          if (xhr.responseText == null || xhr.responseText == '') return false;
          var xml = $.parseXML(xhr.responseText);

          var error = 0;
          $(xml).find('error').each(function(){
            errormsg($(this).find('message').text());
            error = $(this).find('id').text();
          });
          
          if (error == 0) {
            if ($('#id').val() != '') {
              $('#mainmenu_username').html($('#registration_name').val());
              hidedialog();
              errormsg('pgcms=registration.msg_modify_successful', 'info');
            }
            else {
              hidedialog();
              errormsg('pgcms=registration.msg_registration_successful', 'info');
            }
          }
      }
  });

}

function loginform() {

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/login.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('pgcms=general.formnotfound');
                return false;
            }

            popup_id = createdialog('pgcms=loginform.dialog_title', xhr.responseText, [['pgcms=general.login_button', loginform_send], ['pgcms=general.cancel_button', null]]);
            loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
        }
    });

}

function loginform_send() {

   $.ajax({
      type: 'POST',
      cache: 'false',
      url: 'api/api.userdata.php',
      data: 'cmd=login&'+
            'username='+encodeURIComponent($('#login_username').val())+'&'+
            'password='+encodeURIComponent($('#login_password').val()),
      dataType: 'xml',

      complete: function(xhr) {
      
          if (xhr.responseText == null || xhr.responseText == '') { return false; }
          var xml = $.parseXML(xhr.responseText);

          var error = 0;
          $(xml).find('error').each(function() {
            errormsg($(this).find('message').text());
            error = $(this).find('id').text();
          });
          
          if (error == 0) {
            if ($('#login_remember').prop('checked') == true) {
                localStorage.setItem('login_username', $('#login_username').val());
                localStorage.setItem('login_password', $('#login_password').val());
            } else {
                localStorage.removeItem('login_username');
                localStorage.removeItem('login_password');
            }
            
            hidelastdialog();
            document.location.reload();
          }
      }
  });

}

function logoutform() {
  popup_id = createdialog('pgcms=general.logout_dialog_title', '<span pgcms="general.logout_dialog_msg">Do you really wish to sign out?</span>', [['pgcms=general.logout_button', logout], ['pgcms=general.cancel_button', null]]);
  loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
}

function forgotpsw() {

    hidelastdialog();

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/login_forgotpsw.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('pgcms=general.formnotfound');
                return false;
            }

            popup_id = createdialog('pgcms=forgotpsw.dialog_title', xhr.responseText, [['pgcms=forgotpsw.send_button', forgotpsw_send], ['pgcms=general.cancel_button', null]]);
            loadtexts($('#popup_'+popup_id), function() { showdialog(popup_id); });
        }
    });
}

function forgotpsw_send() {

   $.ajax({
      type: 'POST',
      cache: 'false',
      url: 'api/api.userdata.php',
      data: 'cmd=forgotpassword&'+
            'email='+encodeURIComponent($('#forgotpsw_email').val()),
      dataType: 'xml',

      complete: function(xhr) {
      
          if (xhr.responseText == null || xhr.responseText == '') return false;
          var xml = $.parseXML(xhr.responseText);

          var error = 0;
          $(xml).find('error').each(function(){
            errormsg($(this).find('message').text());
            error = $(this).find('id').text();
         }) 
          
          if (error == 0) {
            errormsg('pgcms=forgotpsw.success', 'info');
            hidelastdialog();
          }
      }
  });

}