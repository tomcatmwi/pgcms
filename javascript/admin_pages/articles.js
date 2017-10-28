$(document).ready(function() {

    preloader(true);
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();

    $('#search').keyup(function(e) {
        if (e.keyCode == 13 && $('#search').val().length > 3) {
            load_data(0, $('#search').val());
        }
        if (e.keyCode == 13 && $('#search').val() == '') {
            load_data(0, '');
        }
    });

    //  Load groups for future use

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=getgroup&' +
            'start=0&nolimit=1',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') return false;
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                errormsg($(this).find('message').text());
                preloader(false);
                error = $(this).find('id').text();
            });

            if (error == 0) {
                document.grouplist = $(xml);
            }
        }
    });
});

function paginator_change() {
    load_data($('#paginator').val(), $('#search').val());
    document.zindex = 10001;
}

function load_data(start, search) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=getarticle&' +
            'start=' + start + '&' +
            'search=' + encodeURIComponent(search),
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
                
                xml = solvearticlechunks(xml);

                var columns = [
                    [237, 'id'],
                    [6, 'token'],
                    [238, 'title'],
                    [7, 'info'],
                    [239, 'created_formatted'],
                ];

                var groups = ['group_id', 'group_token'];

                datatable(columns, groups, $(xml), 'article', new Object({
                    'info': true
                }));
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
        url: 'forms/articles.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' admin/forms/articles.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(240), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            $('#id').val(0);

            document.grouplist.find('group').each(function() {
                $('#groupid').append($('<option></option>', {
                    'value': $(this).find('id').text(),
                    'text': $(this).find('token').text()
                }));
            });

            filldateselector('publish_date', true);
            if (localStorage.getItem('lastgroup') != null) $('#groupid').val(localStorage.getItem('lastgroup'));
            preloader(false);
            showdialog(popup_id);
        }
    });
}

function newrecord_perform() {

    preloader(true);
    localStorage.setItem('lastgroup', $('#groupid').val());

    if ($('#token').val() == '' || $('#token').val().length < 4) {
       if ($('#info').val() != '') {
          $('#token').val($('#info').val());
       } else {
          var token = '';
          var chars = 'abcdefghijklmnopqrstuvwxyz';
          for(var t=0; t < 5 + Math.floor((Math.random() * 10)+1); t++) {
            token += chars.charAt(Math.floor((Math.random() * (chars.length-1))));
          }
          $('#token').val(token);
       }
    }

    var created = new Date(
        $('#publish_date_year').val(),
        $('#publish_date_month').val() - 1,
        $('#publish_date_day').val(),
        $('#publish_date_hour').val(),
        $('#publish_date_minute').val(),
        0, 0
    );

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=addarticle&' +
            'id=' + $('#id').val() + '&' +
            'title=' + encodeURIComponent($('#title').val()) + '&' +
            'intro=' + encodeURIComponent(el[0]['intro'].get_content()) + '&' +
            'text=' + encodeURIComponent(el[1]['text'].get_content()) + '&' +
            'groupid=' + $('#groupid').val() + '&' +
            'info=' + encodeURIComponent($('#info').val()) + '&' +
            'created=' + created.getTime() + '&' +
            'token=' + encodeURIComponent($('#token').val()),
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

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/articles_delete.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                errormsg(getlocalmsg(220)+' admin/forms/articles_delete.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(241), xhr.responseText, [
                [getlocalmsg(45), function() {
                    deleterecord_perform(id);
                }],
                [getlocalmsg(227), null]
            ]);
            showdialog(popup_id);
        }
    });
}

function deleterecord_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=deletearticle&' +
            'id=' + id + '&' +
            'deleteall=' + String($('#delete_all').prop('checked')),
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
        url: '../api/api.texts.php',
        data: 'cmd=getarticle&' +
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
                    url: 'forms/articles.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' admin/forms/articles.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(242), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);

                        xml = solvearticlechunks(xml);

                        $(xml).find('pgcms > article').each(function() {

                            el[0]['intro'].set_content($.trim($(this).find('intro').text()));
                            el[1]['text'].set_content($.trim($(this).find('text').text()));

                            document.grouplist.find('group').each(function() {
                                $('#groupid').append($('<option></option>', {
                                    'value': $(this).find('id').text(),
                                    'text': $(this).find('token').text()
                                }));
                            });
                            $('#id').val($(this).find('id').text());
                            $('#token').val($(this).find('token').text());
                            $('#groupid').val($(this).find('group_id').text());
                            $('#title').val($.trim($(this).find('title').text()));
                            $('#info').val($.trim($(this).find('info').text()));

                            filldateselector('publish_date', false);
                            var date = new Date(Number($(this).find('created').text()));
                            $('#publish_date_year').val(date.getFullYear());
                            $('#publish_date_month').val(date.getMonth() + 1);
                            $('#publish_date_day').val(date.getDate());
                            $('#publish_date_hour').val(date.getHours());
                            $('#publish_date_minute').val(date.getMinutes());

                        });
                        showdialog(popup_id);
                        preloader(false);
                    }
                });
            }
        }
    });
}

function translate_all(id) {

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/translate_all.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                errormsg(getlocalmsg(220)+' admin/forms/translate_all.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_all_articles();
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

            showdialog(popup_id);

        }
    });
}

function translate_all_articles() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=translate_all_articles&' +
            'overwrite=' + $('#translate_overwrite').prop('checked') + '&' +
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
                msg = getlocalmsg(243);
                msg = msg.replace(/%count%/g , $(xml).find('counter').text());
                msg = msg.replace(/%language%/g , $(xml).find('target').text());
                errormsg(msg, 'info');
            }
        }
    });
}

function translation(id) {

    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/articles_translate.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' admin/forms/articles_translate.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_article(id);
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

function translate_article(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=translate_article&' +
            'id=' + id + '&' +
            'target=' + $('#translate_language').val() + '&' +
            'overwrite=' + $('#translate_overwrite').prop('checked'),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                hidedialog();
                preloader(false);
                var msg = getlocalmsg(244);
                msg = msg.replace(/%title%/g, $.trim($(xml).find('title').text()));
                errormsg(msg, 'info');
            }
        }
    });
}

function inforecord(id) {

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=getarticle&' +
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
                    url: 'forms/articles_info.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' admin/forms/articles_info.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(7), xhr.responseText, [
                            [getlocalmsg(225), null]
                        ]);

                        $(xml).find('pgcms > article').each(function() {
                            $('#id').html($(this).find('id').text());
                            $('#token').html($(this).find('token').text());
                            $('#group_token').html($(this).find('group_token').text());
                            $('#title').html($.trim($(this).find('title').text()));
                            $('#info').html($.trim($(this).find('info').text()));
                            $('#created').html($.trim($(this).find('created_formatted').text()));
                            $('#translated').html($.trim($(this).find('translated_formatted').text()));
                            $('#user_name').html($.trim($(this).find('user_name').text()));
                        });

                        preloader(false);
                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}