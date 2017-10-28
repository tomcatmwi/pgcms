$('#footer_version').ready(function() {

    //  get version

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=version',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') { return false; }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                xml = jQuery.parseXML(xhr.responseText);
                $('#footer_version').html($.trim($(xml).find('version').text()));
            }
        }
    });

});