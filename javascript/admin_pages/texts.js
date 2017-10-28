function paginator_change() {
    load_data($('#paginator').val(), $('#search').val());
}

function load_data(start, search) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=gettext&' +
            'start=' + start + '&' +
            'group=' + $('#group').val() + '&' +
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

                var columns = [
                    [6, 'token'],
                    [300, 'text'],
                    [301, 'user_name']
                ];

                var groups = ['group_id', 'group_token'];

                datatable(columns, groups, $(xml), 'text');
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
        url: 'forms/texts.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/texts.html');
                return false;
            }
            $('#id').val(0);
            var popup_id = createdialog(getlocalmsg(205), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            document.grouplist.find('group').each(function() {
                $('#groupid').append($('<option></option>', {
                    'value': $(this).find('id').text(),
                    'text': $(this).find('token').text()
                }));
            });
            if (localStorage.getItem('lastgroup') != null) $('#groupid').val(localStorage.getItem('lastgroup'));
            showdialog(popup_id);
            preloader(false);
        }
    });
}

function newrecord_perform() {

    if ($('#token').val() == '' || $('#token').val().length < 4) {
        $('#token').val($('#text').val());
    }
    localStorage.setItem('lastgroup', $('#groupid').val());
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=addtext&' +
            'token=' + encodeURIComponent($('#token').val()) + '&' +
            'text=' + encodeURIComponent($('#text').val()) + '&' +
            'id=' + encodeURIComponent($('#id').val()) + '&' +
            'groupid=' + $('#groupid').val(),

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

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/texts_delete.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/texts_delete.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(302), xhr.responseText, [
                [getlocalmsg(45), function() {
                    deleterecord_perform(id);
                }],
                [getlocalmsg(227), null]
            ]);
            $('#delete_id').val(id);
            showdialog(popup_id);
            preloader(false);
        }
    });

}

function deleterecord_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=deletetext&' +
            'id=' + id + '&' +
            'deleteall=' + $('#delete_all').prop('checked'),
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
        data: 'cmd=gettext&' +
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
                    url: 'forms/texts.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/texts.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(303), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);
                        $(xml).find('pgcms > text').each(function() {

                            document.grouplist.find('group').each(function() {
                                $('#groupid').append($('<option></option>', {
                                    'value': $(this).find('id').text(),
                                    'text': $(this).find('token').text()
                                }));
                            });
                            $('#id').val($(this).find('id').text());
                            $('#token').val($(this).find('token').text());
                            $('#groupid').val($(this).find('group_id').text());
                            $('#text').val($.trim($(this).find('text').text()));
                        });
                        preloader(false);

                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function ajaxsearch(keycode) {

    if (keycode == 13 && $('#search').val().length > 3) {
        load_data(0, $('#search').val());
        return false;
    }
    if (keycode == 13 && $('#search').val() == '') {
        load_data(0, '');
        return false;
    }
    if ($('#search').val().length < 3) return false;

    if (!((keycode >= 65 && keycode <= 90) ||
            (keycode >= 48 && keycode <= 57) ||
            (keycode >= 96 && keycode <= 105))) return false;

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=texts_ajaxsearch&' +
            'search=' + encodeURIComponent($('#search').val()),
        dataType: 'xml',

        complete: function(xhr) {
            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);
            var error = 0;
            $(xml).find('error').each(function() {
                error = $(this).find('id').text();
            });
            if (error == 0 && $(xml).find('result').text() != '' && $(xml).find('result').text() > $('#search').val()) {
                var original = $('#search').val();
                $('#search').val($.trim($(xml).find('result').text()));
                $('#search').selectRange(original.length, $('#search').val().length);
            }
            preloader(false);

        }
    });
}

function translation(id) {

    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/texts_translate.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/texts_translate.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_text(id);
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

            $('#translate_original').html($('#datarow_' + id).find('td:nth-child(2)').html());
            $('#translate_text').val($('#datarow_' + id).find('td:nth-child(2)').html());
            $('#translate_text').select();
            $('#translate_id').val(id);

            preloader(false);
            showdialog(popup_id);

        }
    });
}

function translate_text(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=translate_text&' +
            'id=' + id + '&' +
            'target=' + $('#translate_language').val() + '&' +
            'text=' + encodeURIComponent($('#translate_text').val()) + '&' +
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
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                hidedialog();
                errormsg(getlocalmsg(304), 'info');
                preloader(false);
            }
        }
    });
}

function translate_all(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/translate_all.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/translate_all.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_all_texts();
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

function translate_all_texts() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=translate_all&' +
            'overwrite=' + $('#translate_overwrite').prop('checked') + '&' +
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
                var msg = getlocalmsg(305);
                msg = msg.replace(/%count%/g, $(xml).find('counter').text());
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

    //  AJAX search timer

    var ajaxtimeout = null;

    $('#search').keyup(function(event) {
        clearTimeout(ajaxtimeout);
        ajaxtimeout = setTimeout(function() {
            ajaxsearch(event.keyCode);
        }, 400);
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
                document.grouplist = $(xml);
                $(xml).find('pgcms > group').each(function() {
                    $('#group').append($('<option></option>', {
                        'text': $.trim($(this).find('token').text()),
                        'value': $(this).find('token').text()
                    }));
                });
            }
        }
    });

    //  selectRange for AJAX search box

    $.fn.selectRange = function(start, end) {
        var e = document.getElementById($(this).attr('id')); // I don't know why... but $(this) don't want to work today :-/
        if (!e) return;
        else if (e.setSelectionRange) {
            e.focus();
            e.setSelectionRange(start, end);
        } /* WebKit */
        else if (e.createTextRange) {
            var range = e.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        } /* IE */
        else if (e.selectionStart) {
            e.selectionStart = start;
            e.selectionEnd = end;
        }
    };

});