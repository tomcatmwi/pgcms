$(document).ready(function() {
    preloader(true);
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();

    el = [];

    el[0] = $('#body').rte({
        controls_rte: rte_toolbar,
        controls_html: html_toolbar,
        css: [
            ['../css/articles.css']
        ]
    });

    $(el[0].body).attr('id', 'body');
    $(el[0].body).attr('arrayIndex', 0);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getmassmailertemplate',
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
                $('#subject').val($.trim($(xml).find('subject').text()));
                el[0]['body'].set_content($.trim($(xml).find('body').text()));
                preloader(false);
            }
        }
    });

});

function addaddress() {
    if ($('#newaddress').val() == '') return false;
    if ($('#newname').val() == '') $('#newname').val($('#newaddress').val());

    var ok = true;
    $('#address_list > option').each(function() {
        if ($(this).val() == $('#newaddress').val()) {
            ok = false;
        }
    });
    if (!ok) {
        errormsg('Ez a cím már szerepel a listán.');
        return false;
    }

    $('#address_list').append($('<option></option>', {
        'text': $('#newname').val(),
        'value': $('#newaddress').val()
    }));
    $('#newaddress').val('');
    $('#newname').val('');
    $('#newname').focus();
}

function deleteaddress() {
    if ($('#address_list > option').length == 0) {
        errormsg(getlocalmsg(257));
        return false;
    }

    $('#address_list').find(':selected').remove();
}

function addusers(method) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=getuser&' +
            'nolimit=1&' +
            'method=' + method,
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
                $(xml).find('user').each(function() {
                    var ok = true;
                    var email = $(this).find('email').text();
                    $('#address_list > option').each(function() {
                        if ($(this).val() == email) {
                            ok = false;
                        }
                    });
                    if (ok) $('#address_list').append($('<option></option>', {
                        'text': $(this).find('name').text(),
                        'value': $(this).find('email').text()
                    }));
                });
                preloader(false);
            }
        }
    });
}

function loadlist() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getmasslist',
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
                    url: 'forms/massmailer_loadlist.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            preloader(false);
                            errormsg(getlocalmsg(220)+' admin/forms/massmailer_loadlist.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(258), xhr.responseText, [
                            [getlocalmsg(190), function() {
                                loadlist_perform();
                            }],
                            [getlocalmsg(9), function() {
                                deletelist();
                            }],
                            [getlocalmsg(227), null]
                        ]);

                        $(xml).find('list').each(function() {
                            $('#loadlist_list').append($('<option></option>', {
                                'text': $(this).find('title').text(),
                                'value': $(this).find('id').text()
                            }));
                        });

                        preloader(false);
                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function loadlist_perform() {

    preloader(true);
    if ($('#loadlist_list').val() == null) return false;
    var id = $('#loadlist_list').val();

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getmasslist&id=' + id,
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
                hidelastdialog();

                $(xml).find('address').each(function() {
                    var ok = true;
                    var email = $(this).find('email').text();
                    $('#address_list > option').each(function() {
                        if ($(this).val() == email) {
                            ok = false;
                        }
                    });
                    if (ok) $('#address_list').append($('<option></option>', {
                        'text': $(this).find('name').text(),
                        'value': $(this).find('email').text()
                    }));
                });
                preloader(false);
            }
        }
    });
}

function deletelist() {
    if ($('#loadlist_list').val() == null) return false;
    var popup_id = createdialog(getlocalmsg(259), getlocalmsg(260), [
        [getlocalmsg(45), function() {
            deletelist_perform($('#loadlist_list').val());
        }],
        [getlocalmsg(227), null]
    ]);
    showdialog(popup_id);
}

function deletelist_perform(id) {
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=deletemasslist&id=' + id,
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
                $('#loadlist_list').find(':selected').remove();
                hidelastdialog();
                preloader(false);
            }
        }
    });

}

function savelist() {

    if ($('#address_list > option').length == 0) {
        errormsg(getlocalmsg(257));
        return false;
    }

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/massmailer_savelist.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' admin/forms/massmailer_savelist.html');
                return false;
            }
            showdialog(createdialog(getlocalmsg(261), xhr.responseText, [
                [getlocalmsg(189), function() {
                    savelist_perform();
                }],
                [getlocalmsg(227), null]
            ]));
            preloader(false);
        }
    });

}

function savelist_perform() {

    if ($('#address_list > option').length == 0) {
        errormsg(getlocalmsg(257));
        return false;
    }

    var addresslist = '';
    $('#address_list > option').each(function() {
        if (addresslist != '') addresslist += ',';
        addresslist += $(this).text() + ',' + $(this).val();
    });

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=savemasslist&' +
            'addresslist=' + encodeURI(addresslist) + '&' +
            'public=' + $('#savelist_public').prop('checked') + '&' +
            'title=' + encodeURI($('#savelist_name').val()),
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
                hidelastdialog();
                preloader(false);
                errormsg(getlocalmsg(262), 'info');
            }
        }
    });

}

function deletelist_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=deletemasslist&id=' + id,
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
                $('#loadlist_list').find(':selected').remove();
                hidelastdialog();
                preloader(false);
            }
        }
    });
}

function importlist() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/massmailer_import.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/massmailer_import.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(263), xhr.responseText, [
                [getlocalmsg(190), function() {
                    importlist_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            showdialog(popup_id);
            preloader(false);
        }
    });

}

function importlist_perform() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=importmasslist&' +
            'addresslist=' + encodeURIComponent($('#import_text').val()) + '&' +
            'separator=' + $('#import_separator').val(),
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
                hidelastdialog();

                $(xml).find('email').each(function() {
                    var ok = true;
                    var email = $(this).text();
                    $('#address_list > option').each(function() {
                        if ($(this).val() == email) {
                            ok = false;
                        }
                    });
                    if (ok) $('#address_list').append($('<option></option>', {
                        'text': $(this).text(),
                        'value': $(this).text()
                    }));
                });
                preloader(false);
            }
        }
    });
}

function editelement() {
    $('#newname').val($('#address_list').find(':selected').text());
    $('#newaddress').val($('#address_list').find(':selected').val());
    $('#address_list').find(':selected').remove();
}

function sendmassmail() {

    if ($('#address_list > option').length <= 0) {
        errormsg(getlocalmsg(257));
        return false;
    }

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/massmailer_send.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/massmailer_send.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(264), xhr.responseText, [
                [getlocalmsg(192), function() {
                    sendmassmail_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            preloader(false);
            showdialog(popup_id);
        }
    });

}

function sendmassmail_perform() {

    var delay = $('#send_delay').val();
    var burst = $('#send_burst').val();
    var anon = $('#send_anonymous').val();

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/massmailer_progress.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg(getlocalmsg(220)+' forms/massmailer_progress.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(264), xhr.responseText, [
                [getlocalmsg(265), function() {
                    clearInterval(mailsend);
                    hidedialog();
                }]
            ]);
            showdialog(popup_id);

            progressbar = 0;
            step = 100 / $('#address_list > option').length;
            nth = 1;

            $.ajax({
                type: 'POST',
                cache: 'false',
                url: '../api/api.system.php',
                data: 'cmd=getsetting&token=AUTOMAIL_SENDER_ADDRESS,AUTOMAIL_SENDER',
                dataType: 'xml',

                complete: function(xhr) {

                    if (xhr.responseText == null || xhr.responseText == '') return false;
                    var xml = $.parseXML(xhr.responseText);

                    var error = 0;
                    $(xml).find('error').each(function() {
                        hidelastdialog();
                        errormsg($(this).find('message').text());
                        error = $(this).find('id').text();
                    });

                    if (error == 0) {

                        var sendername = $.trim($(xml).find('user_name').text());
                        var senderemail = $.trim($(xml).find('user_email').text());

                        if ($('#send_sender').val() == 1) {
                            $(xml).find('setting').each(function() {
                                if ($(this).find('id').text() == 'AUTOMAIL_SENDER') {
                                    sendername = $.trim($(this).find('value').text());
                                }
                                if ($(this).find('id').text() == 'AUTOMAIL_SENDER_ADDRESS') {
                                    senderemail = $.trim($(this).find('value').text());
                                }
                            });
                        }

                        mailsend = window.setInterval(function() {

                            var address = $('#address_list > option:nth-child(' + nth + ')').val();
                            var name = $('#address_list > option:nth-child(' + nth + ')').text();
                            if (name == address) {
                                name = $('#send_anonymous').val();
                            }

                            var body = el[0]['body'].get_content();

                            body = body.replace(/%name%/g, $.trim(name));
                            body = body.replace(/%sendername%/g, sendername);
                            body = body.replace(/%senderemail%/g, senderemail);

                            $.ajax({
                                type: 'POST',
                                cache: 'false',
                                url: '../api/api.messages.php',
                                data: 'cmd=sendmassmail&' +
                                    'body=' + encodeURIComponent(body) + '&' +
                                    'subject=' + encodeURIComponent($.trim($('#subject').val())) + '&' +
                                    'to=' + encodeURIComponent(address) + '&' +
                                    'toname=' + encodeURIComponent(name) + '&' +
                                    'sender=' + $('#send_sender').val(),
                                dataType: 'xml',

                                complete: function(xhr) {

                                    if (xhr.responseText == null || xhr.responseText == '') return false;
                                    var xml = $.parseXML(xhr.responseText);

                                    var error = 0;
                                    $(xml).find('error').each(function() {
                                        errormsg($(this).find('message').text());
                                        error = $(this).find('id').text();
                                        clearInterval(mailsend);
                                        $('#send_log').prepend(getlocalmsg(266));
                                    });

                                    if (error == 0) {

                                        var date = new Date();
                                        var logline = '';

                                        if (date.getHours() >= 10) {
                                            logline += date.getHours();
                                        } else {
                                            logline += '0' + date.getHours();
                                        }
                                        logline += ':';
                                        if (date.getMinutes() >= 10) {
                                            logline += date.getMinutes();
                                        } else {
                                            logline += '0' + date.getMinutes();
                                        }
                                        logline += ':';
                                        if (date.getSeconds() >= 10) {
                                            logline += date.getSeconds();
                                        } else {
                                            logline += '0' + date.getSeconds();
                                        }
                                        logline += ' ';
                                        logline += ' ' + name + ' (' + address + ')\n';
                                        $('#send_log').prepend(logline);

                                        progressbar += step;
                                        $('#send_progressbar').css('width', progressbar + '%');
                                        nth++;

                                        if (nth > $('#address_list > option').length) {
                                            clearInterval(mailsend);
                                            $('#send_log').prepend('Kész!\n');
                                        }

                                    }
                                }
                            });

                        }, (delay * 1000));
                    }
                }
            });
        }
    });
}