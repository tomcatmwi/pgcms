$(document).ready(function() {
    $.ajaxSetup({
        cache: false,
    });
    loadlocalization();
});

function load_data() {

    hidedialog();
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=getstatus',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);
            document.language = $(xml).find('user_language').text();

            $.ajax({
                type: 'GET',
                cache: 'false',
                url: '../xmldata/emails_' + document.language + '.xml?rnd='+$.now(),
                dataType: 'xml',

                complete: function(xhr) {
                    if (xhr.status != 200) {
                        preloader(false);
                        errormsg(getlocalmsg(220)+' xmldata/emails_' + document.language + '.xml');
                        return false;
                    }

                    var xml = jQuery.parseXML(xhr.responseText);
                    $('#main_table_body').empty();

                    datatable(
                        [
                            [237, 'id'],
                            [285, 'subject'],
                            [293, 'explanation']
                        ], [],
                        $(xml),
                        'email',
                        new Object({
                            translatable: false,
                            deletable: false,
                            paginator: false
                        })
                    );
                    preloader(false);
                }
            });
        }
    });
}

function editrecord(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/systemmsg.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/systemmsg.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(273), xhr.responseText, [
                [getlocalmsg(197), function() {
                    editrecord_perform(id);
                }],
                [getlocalmsg(227), null]
            ]);

            $.ajax({
                type: 'GET',
                cache: 'false',
                url: '../xmldata/emails_' + document.language + '.xml?rnd='+$.now(),
                dataType: 'xml',

                complete: function(xhr) {
                    if (xhr.status != 200) {
                        preloader(false);
                        errormsg(getlocalmsg(220)+' xmldata/emails_' + document.language + '.xml');
                        return false;
                    }

                    var xml = jQuery.parseXML(xhr.responseText);

                    $(xml).find('email').each(function() {
                        if ($(this).find('id').text() == id) {
                            $('#id').val($(this).find('id').text());
                            $('#subject').val($(this).find('subject').text());
                            el[0]['body'].set_content($.trim($(this).find('body').text()));
                        }
                    });

                    preloader(false);
                    showdialog(popup_id);
                }
            });
        }
    });
}

function editrecord_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=updatesystemmsg&' +
            'id=' + id + '&' +
            'subject=' + encodeURIComponent($('#subject').val()) + '&' +
            'body=' + encodeURIComponent(el[0]['body'].get_content()),
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
                $('#datarow_' + id).find('td:nth-child(2)').text($('#subject').val());
                hidedialog();
                preloader(false);
            }
        }
    });
}