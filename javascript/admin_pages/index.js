$(document).ready(function() {

    $.ajaxSetup({
        cache: false
    });

    loadlocalization();

    //  get unread messages

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getunread',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                xml = jQuery.parseXML(xhr.responseText);
                $('#message_counter').html($(xml).find('messages').text());
                if ($(xml).find('messages').text() != '0') {
                    $('#message_counter').removeClass('label-default');
                    $('#message_counter').addClass('label-danger');
                } else {
                    $('#message_counter').removeClass('label-danger');
                    $('#message_counter').addClass('label-default');
                }

                $('#todolist').css('display', 'block');
                preloader(false);
            }
        }
    });

});