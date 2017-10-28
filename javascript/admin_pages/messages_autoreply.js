function paginator_change() {
    load_data($('#paginator').val(), $('#search').val());
    document.zindex = 10001;
}

function load_data(start, search) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getautoreply&' +
            'start=' + start,
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

                hidedialog();
                $('#main_table_body').empty();

                var columns = [
                    [7, 'info'],
                    [285, 'subject']
                ];

                datatable(columns, [], $(xml), 'autoreply', new Object({}));
                preloader(false);
            }
        }
    });
}

function newrecord() {
    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/messages_autoreply.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/messages_autoreply.html');
                return false;
            }
            preloader(false);
            showdialog(createdialog(getlocalmsg(200), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]));
        }
    });
}

function newrecord_perform() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=addautoreply&' +
            'id=' + $('#id').val() + '&' +
            'subject=' + encodeURIComponent($('#subject').val()) + '&' +
            'info=' + encodeURIComponent($('#info').val()) + '&' +
            'body=' + encodeURIComponent($.trim(el[0]['body'].get_content())),
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
                hidedialog();
                load_data($('#paginator').val(), '');
                preloader(false);
            }
        }
    });
}

function deleterecord(id) {
    showdialog(createdialog(getlocalmsg(286), getlocalmsg(287), [
        [getlocalmsg(45), function() {
            deleterecord_perform(id);
        }],
        [getlocalmsg(227), null]
    ]));
}

function deleterecord_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=deleteautoreply&' +
            'id=' + id,
        dataType: 'xml',

        complete: function(xhr) {

            hidedialog();

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                hidedialog();
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                load_data(0, '');
                preloader(false);
            }
        }
    });
}

function editrecord(id) {

    hidedialog();
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getautoreply&' +
            'id=' + id,
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

                $.ajax({
                    type: 'GET',
                    cache: 'false',
                    url: 'forms/messages_autoreply.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(localmsg(220)+' forms/messages_autoreply.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(288), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);
                        $(xml).find('pgcms > autoreply').each(function() {
                            $('#id').val($(this).find('id').text());
                            $('#subject').val($(this).find('subject').text());
                            $('#info').val($(this).find('info').text());
                            el[0]['body'].set_content($.trim($(this).find('body').text()));
                        });
                        preloader(false);
                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function translation(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/messages_autoreply_translate.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/messages_autoreply_translate.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_autoreply(id);
                }],
                [getlocalmsg(227), null]
            ]);

            $(document.langlist).find('language').each(function() {
                if ($(this).find('selectable').text() == '1' && $(this).attr('id') != document.current_language)
                    $('#translate_language').append($('<option></option>', {
                        'value': $(this).attr('id'),
                        'text': $(this).find('name').text() + ' (' + $(this).find('nameeng').text() + ')'
                    }));
            });

            if (localStorage.getItem('last_language') != null) {
                var ok = false;
                $('#select-box option').each(function() {
                    if ($(this).value == localStorage.getItem('last_language')) ok = true;
                });
                if (ok) $('#translate_language').val(localStorage.getItem('last_language'));
            }

            $('#translate_id').val(id);
            preloader(false);

            showdialog(popup_id);

        }
    });
}

function translate_autoreply(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=translate_autoreply&' +
            'id=' + id + '&' +
            'target=' + $('#translate_language').val(),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') return false;
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                hidedialog();
                preloader(false);
                errormsg(getlocalmsg(289), 'info');
            }
        }
    });
}

function translate_all(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/messages_autoreply_translate.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/messages_autoreply_translate.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_all_autoreplies();
                }],
                [getlocalmsg(227), null]
            ]);

            $(document.langlist).find('language').each(function() {
                if ($(this).find('selectable').text() == '1' && $(this).attr('id') != document.current_language)
                    $('#translate_language').append($('<option></option>', {
                        'value': $(this).attr('id'),
                        'text': $(this).find('name').text() + ' (' + $(this).find('nameeng').text() + ')'
                    }));
            });


            if (localStorage.getItem('last_language') != null) {
                var ok = false;
                $('#select-box option').each(function() {
                    if ($(this).value == localStorage.getItem('last_language')) ok = true;
                });
                if (ok) $('#translate_language').val(localStorage.getItem('last_language'));
            }
            preloader(false);

            showdialog(popup_id);

        }
    });
}

function translate_all_autoreplies() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=translate_all_autoreplies&' +
            'target=' + $('#translate_language').val(),
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
                hidedialog();
                preloader(false);
                var msg = getlocalmsg(290);
                msg = msg.replace(/%count%/g, $(xml).find('counter').text());
                msg = msg.replace(/%language%/g, $(xml).find('target').text());
                errormsg(msg, 'info');
            }
        }
    });
}

$(document).ready(function() {
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();
});