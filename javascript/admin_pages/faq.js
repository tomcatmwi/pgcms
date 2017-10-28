$(document).ready(function() {

    preloader(true);
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();

    //  Load groups for future use

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=getgroup&start=0&nolimit=1',
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

    $('#search').keyup(function(e) {
        if (e.keyCode == 13 && $('#search').val().length > 3) {
            load_data(0, $('#search').val());
        }
        if (e.keyCode == 13 && $('#search').val() == '') {
            load_data(0, '');
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
        url: '../api/api.faq.php',
        data: 'cmd=getquestion&' +
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
                    [348, 'priority'],
                    [361, 'question'],
                    [349, 'visible', 'boolean'],
                ];

                var groups = ['group_id', 'group_name'];

                datatable(columns, groups, $(xml), 'faq', new Object({ 'info': true }));
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
        url: 'forms/faq.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/faq.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(360), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            
            $('#id').val(0);

            document.grouplist.find('faq_group').each(function() {
                $('#group_id').append($('<option></option>', {
                    'value': $(this).find('id').text(),
                    'text': $(this).find('name').text()
                }));
            });

            if (localStorage.getItem('faq_lastgroup') != null) $('#groupid').val(localStorage.getItem('faq_lastgroup'));
            preloader(false);
            showdialog(popup_id);
        }
    });
}

function newrecord_perform() {

    preloader(true);
    localStorage.setItem('faq_lastgroup', $('#groupid').val());
    
    var searchwords = '';
    $('#searchwords').find('div').each(function() {
      if (searchwords != '') searchwords += '###';
      searchwords += $.trim($(this).html());
    });
    
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=addquestion&' +
            'id=' + $('#id').val() + '&' +
            'question=' + encodeURIComponent($('#question').val()) + '&' +
            'text=' + encodeURIComponent(el[0]['text'].get_content()) + '&' +
            'group_id=' + encodeURIComponent($('#group_id').val()) + '&' +
            'priority=' + encodeURIComponent($('#priority').val()) + '&' +
            'searchwords=' + encodeURIComponent(searchwords) + '&' +
            'visible=' + encodeURIComponent($('#visible').prop('checked')),
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
        url: 'forms/faq_delete.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                errormsg(getlocalmsg(220)+' forms/faq_delete.html');
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
        url: '../api/api.faq.php',
        data: 'cmd=deletequestion&' +
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
        url: '../api/api.faq.php',
        data: 'cmd=getquestion&' +
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
                    url: 'forms/faq.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/faq.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(368), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);

                        xml = solvearticlechunks(xml);

                        $(xml).find('pgcms > faq').each(function() {

                            el[0]['text'].set_content($.trim($(this).find('text').text()));

                            document.grouplist.find('faq_group').each(function() {
                                $('#group_id').append($('<option></option>', {
                                    'value': $(this).find('id').text(),
                                    'text': $(this).find('name').text()
                                }));
                            });
                            $('#id').val($(this).find('id').text());
                            $('#group_id').val($(this).find('group_id').text());
                            $('#question').val($.trim($(this).find('question').text()));
                            $('#priority').val($.trim($(this).find('priority').text()));
                            $('#visible').prop('checked', $(this).find('visible').text() == 1);
                            
                            $(this).find('searchwords > searchword').each(function() {
                              addsearchword_perform($.trim($(this).text()));
                            });

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
                errormsg(getlocalmsg(220)+' forms/translate_all.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_all_faqs();
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

function translate_all_faqs() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=translate_all_questions&' +
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
                msg = getlocalmsg(369);
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
        url: 'forms/faq_translate.html',
        dataType: 'html',

        complete: function(xhr) {

            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/faq_translate.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(10), xhr.responseText, [
                [getlocalmsg(197), function() {
                    translate_faq(id);
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

function translate_faq(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=translate_question&' +
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
                errormsg(getlocalmsg(370), 'info');
            }
        }
    });
}

function inforecord(id) {

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.faq.php',
        data: 'cmd=getquestion&' +
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
                    url: 'forms/faq_info.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/faq_info.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(7), xhr.responseText, [
                            [getlocalmsg(225), null]
                        ]);

                        $(xml).find('pgcms > faq').each(function() {
                            $('#id').html($(this).find('id').text());
                            $('#priority').html($(this).find('priority').text());
                            $('#group_name').html($(this).find('group_name').text());
                            $('#created').html($.trim($(this).find('created_formatted').text()));
                            $('#question').html($.trim($(this).find('question').text()));
                            $('#user_name').html($.trim($(this).find('user_name').text()));
                            if ($(this).find('visible').text() == '0') $('#visible').html(getlocalmsg(44));
                            if ($(this).find('visible').text() == '1') $('#visible').html(getlocalmsg(45));
                            
                            var searchwords = '';
                            $(this).find('searchwords > searchword').each(function() {
                              if (searchwords != '') searchwords += ', ';
                              searchwords += $.trim($(this).text());
                            });
                            $('#searchwords').html(searchwords);
                            
                        });

                        preloader(false);
                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function addsearchword() {
    preloader(true);
    
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/faq_searchword.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/faq_searchword.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(375), xhr.responseText, [
                [getlocalmsg(374), function() {
                    addsearchword_perform($('#searchword').val());
                    hidelastdialog();
                }],
                [getlocalmsg(227), null]
            ]);
            
            preloader(false);
            showdialog(popup_id);
        }
    });
}

function addsearchword_perform(searchword) {
    if (searchword == '###') return false;
    var div = $('<div></div>', { 'class': 'form_searchword' });
    div.dblclick(function() { 
        $(this).remove();
        if ($('#searchwords').find('div').length <= 0) $('#searchwords').css('display', 'none');
    });
    div.html(searchword);
    $('#searchwords').append(div);
    $('#searchwords').css('display', 'block');
}