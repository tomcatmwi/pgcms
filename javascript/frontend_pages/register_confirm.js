$(document).ready(function() {
  $.ajaxSetup({ cache: false });
  filllanguageselector();
  
   $.ajax({
      type: 'POST',
      cache: 'false',
      url: 'api/api.userdata.php',
      data: 'cmd=confirmreg&'+
            'resetcode='+encodeURIComponent(getparam('id')),
      dataType: 'xml',

      complete: function(xhr) {
      
          if (xhr.responseText == null || xhr.responseText == '') return false;
          var xml = $.parseXML(xhr.responseText);

          var error = 0;
          $(xml).find('error').each(function(){
            error = $(this).find('id').text();
            $('#register_confirm_successful').css('display', 'hidden');
            $('#register_confirm_failed').css('display', 'block');
          });
          
          if (error == 0) {
            $('#register_confirm_successful').css('display', 'block');
            $('#register_confirm_failed').css('display', 'hidden');
          }
      }
   });
  
});
