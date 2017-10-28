function paginator_change() {
    load_data($('#paginator').val(), $('#search').val());
}

function load_data(start) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=getgroup&' +
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
                    [348, 'priority'],
                    [346, 'name'],
                    [347, 'answers'],
                    [349, 'visible', 'boolean'],
                    [301, 'user_name']
                ];

                datatable(columns, [], $(xml), 'faq_group');
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
        url: 'forms/faq_groups.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/faq_groups.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(353), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            
            $('#id').val(0);
            showdialog(popup_id);
            preloader(false);
        }
    });
}

function newrecord_perform() {

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=addgroup&' +
            'name=' + encodeURIComponent($('#name').val()) + '&' +
            'priority=' + encodeURIComponent($('#priority').val()) + '&' +
            'visible=' + encodeURIComponent($('#visible').prop('checked')) + '&' +
            'id=' + encodeURIComponent($('#id').val()),

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
        url: 'forms/faq_groups_delete.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/faq_groups_delete.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(355), xhr.responseText, [
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
        url: '../api/api.faq.php',
        data: 'cmd=deletegroup&' +
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
        url: '../api/api.faq.php',
        data: 'cmd=getgroup&' +
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
                    url: 'forms/faq_groups.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/faq_groups.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(354), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);
                        $(xml).find('pgcms > faq_group').each(function() {

                            $('#id').val($(this).find('id').text());
                            $('#name').val($.trim($(this).find('name').text()));
                            $('#priority').val($(this).find('priority').text());
                            $('#visible').prop('checked', $(this).find('visible').text() == 1);
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
                    translate_group(id);
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

function translate_group(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=translate_group&' +
            'id=' + id + '&' +
            'target=' + $('#translate_language').val() + '&' +
            'name=' + encodeURIComponent($('#translate_text').val()) + '&' +
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
                    translate_all_groups();
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

function translate_all_groups() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=translate_all_groups&' +
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
                var msg = getlocalmsg(358);
                msg = msg.replace(/%count%/g, $(xml).find('counter').text());
                msg = msg.replace(/%language%/g, $('#translate_language').find('option:selected').text());
                hidedialog();
                preloader(false);
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

});