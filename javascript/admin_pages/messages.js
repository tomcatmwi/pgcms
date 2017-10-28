function nextday(dir) {

    var date = new Date();
    date.setYear($('#msgdate_year').val());
    date.setMonth($('#msgdate_month').val() - 1);
    date.setDate($('#msgdate_day').val());
    date.setDate(date.getDate() + dir);

    var year = String(date.getFullYear());

    if ($('#msgdate_year option[value=' + year + ']').length == 0) {
        $('#msgdate_year').append($('<option></option>', {
            'value': year,
            'text': year
        }));
    }

    loadmessages(date.getFullYear(), date.getMonth() + 1, date.getDate());
}

function sendautoreply(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getreply&' +
            'id=' + id + '&' +
            'autoreply=' + $('#autoreply_' + id).val() + '&' +
            'autosend=true',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = jQuery.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                preloader(false);
                errormsg(getlocalmsg(272), 'info');
                $('#replied_' + id).prop('checked', true);
            }
        }
    });
}

function editrecord(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getmessage&' +
            'id=' + id,
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = jQuery.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {

                hidedialog();

                $.ajax({
                    type: 'GET',
                    cache: 'false',
                    url: 'forms/messages_edit.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/messages_edit.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(273), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);
                        $(xml).find('message').each(function() {
                            $('#id').val($(this).find('id').text());
                            $('#name').val($(this).find('name').text());
                            $('#email').val($(this).find('email').text());
                            var temp = $.trim($(this).find('text').text());
                            $('#text').val(br2nl(temp.replace(/(\r\n|\n|\r|    )/gm, '')));

                        });
                        preloader(false);
                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function replymsg(id, autoreply) {

    if (typeof autoreply == 'undefined') {
        autoreply = 0;
    }
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getreply&' +
            'id=' + id + '&' +
            'autoreply=' + autoreply,
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

                $.ajax({
                    type: 'GET',
                    cache: 'false',
                    url: 'forms/messages_reply.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/messages_reply.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(274), xhr.responseText, [
                            [getlocalmsg(192), function() {
                                sendmessage_perform(id);
                            }],
                            [getlocalmsg(227), null]
                        ]);

                        $('#from').val($(xml).find('from_name').text() + ' <' + $(xml).find('from_email').text() + '>');
                        $('#subject').val($(xml).find('subject').text());
                        $('#to').val($(xml).find('to_name').text() + ' <' + $(xml).find('to_address').text() + '>');
                        el[0]['body'].set_content($.trim($(xml).find('body').text()));
                        preloader(false);

                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function sendmessage_perform(id) {

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=sendemail&' +
            'to=' + encodeURIComponent($('#to').val()) + '&' +
            'cc=' + encodeURIComponent($('#cc').val()) + '&' +
            'subject=' + encodeURIComponent($('#subject').val()) + '&' +
            'from=' + encodeURIComponent($('#from').val()) + '&' +
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

                $.ajax({
                    type: 'POST',
                    cache: 'false',
                    url: '../api/api.messages.php',
                    data: 'cmd=setreplied&' +
                        'id=' + id + '&' +
                        'replied=true',
                    dataType: 'xml',

                    complete: function(xhr) {

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
                            $('#replied_' + id).prop('checked', true);
                            hidedialog();
                            preloader(false);
                            errormsg(getlocalmsg(275), 'info');
                        }
                    }
                });
            }
        }
    });

}

function newrecord_perform() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=modifymessage&' +
            'id=' + encodeURIComponent($('#id').val()) + '&' +
            'name=' + encodeURIComponent($('#name').val()) + '&' +
            'email=' + encodeURIComponent($('#email').val()) + '&' +
            'text=' + $.trim(encodeURIComponent($('#text').val())),

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
                if ($('#id').val() != 0) {
                    $('#messagebody_' + $('#id').val()).html($.trim(nl2br($('#text').val())));
                    $('#name_' + $('#id').val()).html($('#name').val());
                    $('#email_' + $('#id').val()).html($('#email').val());
                    hidedialog();
                    preloader(false);
                }
            }
        }
    });
}

function deleterecord(id) {
    showdialog(createdialog(getlocalmsg(276), getlocalmsg(277), [
        [getlocalmsg(45), function() {
            deleterecord_perform(id);
        }],
        [getlocalmsg(227), null]
    ]));
}

function deleterecord_perform(id, deletecontent) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=deletemessage&' +
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
                hidedialog();
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                hidedialog();
                $('#message_' + $(xml).find('id').text()).remove();
                $('#message_opener' + $(xml).find('id').text()).remove();
                preloader(false);
            }
        }
    });
}

function setreplied(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=setreplied&' +
            'id=' + id + '&' +
            'replied=' + $('#replied_' + id).prop('checked'),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                hidedialog();
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });
            preloader(false);
        }
    });
}

function messagetofolder(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=messagetofolder&' +
            'id=' + id + '&' +
            'folderid=' + $('#folder_' + id).val(),
        dataType: 'xml',

        complete: function(xhr) {

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
                preloader(false);
                if ($(xml).find('folder_id').text() != 0) {
                  var msg = getlocalmsg(278);
                  msg = msg.replace(/%folder%/g, $(xml).find('foldername').text());
                  errormsg(msg, 'info');
                }
                else errormsg(getlocalmsg(279), 'info');
            }
        }
    });
}


function loadmessages(year, month, day, search, unread) {

    if (typeof day == 'undefined') {
        day = 0;
    }
    if (typeof search == 'undefined') {
        search = '';
    }
    if (typeof unread == 'undefined') {
        unread = false;
    }

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getmessage&' +
            'unread=' + unread + '&' +
            'folder=' + $('#folder').val() + '&' +
            'search=' + encodeURIComponent(search) + '&' +
            'year=' + year + '&' +
            'month=' + month + '&' +
            'day=' + day,
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

                var lastdate = 0;

                $('#maintable').empty();

                $('#msgdate_year').val(year);
                $('#msgdate_month').val(month);
                $('#msgdate_day').val(day);

                $(xml).find('message').each(function() {

                    var current_date = new Date(Number($(this).find('date').text()) * 1000);
                    current_date.setHours(0);
                    current_date.setMinutes(0);
                    current_date.setSeconds(0);
                    current_date = current_date.getTime() / 1000;

                    if (lastdate > current_date || lastdate == 0) {
                        var thead = $('<thead></thead>', {
                            'style': 'margin-top: 2em;'
                        });
                        thead.append($('<tr></tr>'));
                        thead.find('tr:last').append($('<th></th>', {
                            'colspan': 2
                        }));
                        thead.find('tr:last').find('th:last').append($(this).find('date_formatted_short').text());
                        $('#maintable').append(thead);
                        $('#maintable').append($('<tbody></tbody>'));
                        lastdate = current_date;
                    }

                    var id = $(this).find('id').text();

                    var tr = $('<tr></tr>', {
                        'id': 'message_opener' + id,
                        'onclick': 'openmessage(' + id + ');'
                    });
                    tr.append($('<td></td>', {
                        'class': 'message_opener',
                        'colspan': 2
                    }));

                    tr.find('td:last').append($('<div></div>', {
                        'id': 'name_' + id,
                        'class': 'message_header_section'
                    }));
                    tr.find('td:last').find('div:last').append('<b>' + $(this).find('name').text() + '</b>');
                    tr.find('td:last').append($('<div></div>', {
                        'class': 'message_header_section'
                    }));
                    tr.find('td:last').find('div:last').append($(this).find('date_formatted').text());
                    tr.find('td:last').append($('<div></div>', {
                        'id': 'email_' + id,
                        'class': 'message_header_section'
                    }));
                    tr.find('td:last').find('div:last').append($(this).find('email').text());

                    if ($(this).find('unread').text() == 't') {
                        tr.find('td:first').find('div:first').css('color', '#FF0000');
                        tr.find('td:first').find('div:first').attr('unread', 1);
                    } else {
                        tr.find('td:first').find('div:first').attr('unread', 0);
                    }

                    $('#maintable').find('tbody:last').append(tr);

                    var tr = $('<tr></tr>', {
                        'id': 'message_' + id,
                        'style': 'display: none;'
                    });
                    tr.append($('<td></td>', {
                        'class': 'messages_sidebar'
                    }));

                    if ($(this).find('phone').text() != '') {
                        tr.find('td:last').append($('<div></div>', {
                            'id': 'phone_' + id,
                            'style': 'margin-bottom: 4px;'
                        }));
                        tr.find('td:last').find('div:last').append($(this).find('phone').text());
                    }

                    if ($(this).find('company').text() != '') {
                        tr.find('td:last').append($('<div></div>', {
                            'id': 'company_' + id,
                            'style': 'margin-bottom: 4px;'
                        }));
                        tr.find('td:last').find('div:last').append($(this).find('company').text());
                    }

                    tr.find('td:last').append($('<div></div>', {
                        'id': 'email_' + id,
                        'style': 'margin-bottom: 4px;'
                    }));
                    tr.find('td:last').find('div:last').append($('<a></a>', {
                        'href': 'mailto:' + $(this).find('email').text()
                    }));
                    tr.find('td:last').find('div:last').find('a:last').html($(this).find('email').text());


                    tr.find('td:last').append($('<div></div>', {
                        'id': 'flag_' + id,
                        'style': 'margin-bottom: 7px;'
                    }));
                    tr.find('td:last').find('div:last').append($('<img />', {
                        'src': '../pic/flags/' + $(this).find('language_flag').text(),
                        'style': 'margin-right: 5px;'
                    }));
                    tr.find('td:last').find('div:last').append($(this).find('language_name').text() + ' (' + $(this).find('language_nameeng').text() + ')');

                    tr.find('td:last').append($('<div></div>', {
                        'id': 'ip_' + id
                    }));
                    tr.find('td:last').find('div:last').append($.trim($(this).find('ip').text()));

                    tr.find('td:last').append($('<input />', {
                        'type': 'button',
                        'onclick': 'replymsg(' + id + ');',
                        'value': getlocalmsg(284),
                    }));

                    if (document.autoreply.length > 0) {
                        tr.find('td:last').append(getlocalmsg(280)+'<br />');
                        tr.find('td:last').append($('<div></div>', {
                            'class': 'messages_sidebar_container'
                        }));

                        tr.find('td:last').find('div:last').append($('<select></select>', {
                            'id': 'autoreply_' + id,
                            'style': 'max-width: calc(100% - 6em) !important; min-width: calc(100% - 6em) !important;'
                        }));
                        for (t in document.autoreply) {
                            tr.find('td:last').find('div:last').find('select:last').append($('<option></option>', {
                                'value': document.autoreply[t][0],
                                'text': document.autoreply[t][1]
                            }));
                        }

                        tr.find('td:last').find('div:last').append($('<input />', {
                            'type': 'button',
                            'onclick': 'replymsg(' + id + ', $(\'#autoreply_' + id + '\').val());',
                            'value': '?'
                        }));

                        tr.find('td:last').find('div:last').append($('<input />', {
                            'type': 'button',
                            'onclick': 'sendautoreply(' + id + ');',
                            'value': getlocalmsg(197)
                        }));
                    }

                    if (document.folders.length > 0) {
                        tr.find('td:last').append(getlocalmsg(281)+'<br />');
                        tr.find('td:last').append($('<div></div>', {
                            'class': 'messages_sidebar_container'
                        }));
                        tr.find('td:last').find('div:last').append($('<select></select>', {
                            'id': 'folder_' + id,
                            'style': 'max-width: calc(100% - 3em) !important; min-width: calc(100% - 3em) !important;'
                        }));
                        tr.find('td:last').find('div:last').find('select:last').append($('<option></option>', {
                            'value': 0,
                            'text': getlocalmsg(282)
                        }));
                        for (t in document.folders) {
                            tr.find('td:last').find('div:last').find('select:last').append($('<option></option>', {
                                'value': document.folders[t][0],
                                'text': document.folders[t][1]
                            }));
                        }

                        tr.find('td:last').find('div:last').append($('<input />', {
                            'type': 'button',
                            'onclick': 'messagetofolder(' + id + ');',
                            'value': getlocalmsg(197)
                        }));
                    }

                    tr.find('td:last').append($('<input />', {
                        'type': 'checkbox',
                        'id': 'replied_' + id,
                        'onchange': 'setreplied(' + id + ')',
                        'style': 'margin-right: 6px;'
                    }));
                    tr.find('td:last').append($('<label></label>', {
                        'for': 'replied_' + id,
                        'text': getlocalmsg(283),
                        'style': 'cursor: pointer;'
                    }));
                    tr.find('td:last').append($('<br />'));
                    tr.find('td:last').append($('<br />'));

                    tr.find('td:last').append($('<input />', {
                        'type': 'button',
                        'value': getlocalmsg(60),
                        'onclick': 'editrecord(' + id + ')'
                    }));

                    tr.find('td:last').append($('<input />', {
                        'type': 'button',
                        'value': getlocalmsg(9),
                        'onclick': 'deleterecord(' + id + ')'
                    }));


                    tr.append($('<td></td>', {
                        'id': 'messagebody_' + id,
                        'style': 'vertical-align: top;'
                    }));
                    tr.find('td:last').append($.trim($(this).find('text').text()));

                    $('#maintable').find('tbody:last').append(tr);

                    $('#replied_' + id).prop('checked', $(this).find('replied').text() == 't');
                    $('#folder_' + id).val($(this).find('folderid').text());
                });
                preloader(false);
            }
        }
    });
}

function openmessage(id) {

    if ($('#message_opener' + id).find('td:first').find('div:first').attr('unread') == 1) {

        preloader(true);
        $.ajax({
            type: 'POST',
            cache: 'false',
            url: '../api/api.messages.php',
            data: 'cmd=setunread&id=' + id,
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
                    $('#message_opener' + id).find('td:first').find('div:first').css('color', '#000000');
                    $('#message_opener' + id).find('td:first').find('div:first').attr('unread', 0);
                    preloader(false);
                    $('#message_' + id).fadeIn(400);
                }
            }
        });


    } else {
        if ($('#message_' + id).css('display') == 'none') $('#message_' + id).fadeIn(400)
        else $('#message_' + id).fadeOut(400);
    }

}

$(document).ready(function() {
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();
    filldateselector('msgdate', true);
    document.folders = [];
    document.autoreply = [];

    //  load message groups

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getfolder&start=0&nolimit=1',
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
                $(xml).find('folder').each(function() {
                    document.folders.push([$(this).find('id').text(), $(this).find('title').text()]);
                    $('#folder').append($('<option></option>', {
                        'text': $(this).find('title').text(),
                        'value': $(this).find('id').text()
                    }));
                });

                //  load predefined messages

                $.ajax({
                    type: 'POST',
                    cache: 'false',
                    url: '../api/api.messages.php',
                    data: 'cmd=getautoreply&start=0&nolimit=1',
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
                            $(xml).find('autoreply').each(function() {
                                document.autoreply.push([$(this).find('id').text(), $(this).find('info').text()]);
                            });

                            var date = new Date();
                            preloader(false);

                            if (getparam('unread') != 'true') loadmessages(date.getFullYear(), date.getMonth() + 1, date.getDate())
                            else loadmessages(date.getFullYear(), date.getMonth() + 1, date.getDate(), '', true);
                        }
                    }
                });
            }
        }
    });

});