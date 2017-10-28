function loadfiles(popup_id, year, month, search) {

    if (typeof search == 'undefined' || search == null) search = '';
    if (year == null || month == null || typeof year == 'undefined' || typeof month == 'undefined') {
        var date = new Date();
        year = date.getFullYear();
        month = date.getMonth() + 1;
    }

    $('#filestorage_year').val(year);
    $('#filestorage_month').val(month);

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.filestorage.php',
        data: 'cmd=getfile&' +
            'year=' + year + '&' +
            'month=' + month + '&' +
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
            
                $('#filestorage_list').empty();
                $('[id^=filestorage_info_]').html(getlocalmsg(74));

                $(xml).find('file').each(function() {
                    var option = filestorage_createnewoption($(this));
                    $('#filestorage_list').append(option);
                });

                $('#filestorage_list').focus();
                $('#filestorage_list option:first-child').attr('selected', 'selected');
                $('#filestorage_list option:first-child').mouseup();

                preloader(false);
                if ($('#popup_' + popup_id).css('visibility') == 'hidden') showdialog(popup_id);

            }
        }
    });
}

function filestorage_createnewoption(xml) {

    var option = $('<option></option>', {
        'value': xml.find('id').text(),
        'text': xml.find('filename').text(),
        'info': $.trim(xml.find('info').text()),
        'accessed': xml.find('accessed').text(),
        'protected': xml.find('protected').text(),
        'filesize': $.trim(xml.find('filesize').text()),
        'mime': $.trim(xml.find('mime').text()),
        'created': $.trim(xml.find('created_formatted').text()),
        'user_name': $.trim(xml.find('user_name').text()),
        'style': 'max-height: 1em'
    });

    option.unbind('mouseup');
    option.mouseup(function() {
        
        $('#filestorage_download').attr('href', '../api/api.download.php?id='+$(this).val());
        $('#filestorage_info_2').html($(this).attr('filesize'));
        $('#filestorage_info_3').html($(this).attr('created'));
        $('#filestorage_info_4').html($(this).attr('user_name'));
        $('#filestorage_info_5').html($(this).attr('mime'));
        $('#filestorage_info_7').html($(this).attr('accessed'));
        $('#filestorage_info_8').html($(this).attr('info'));
        
        switch(Number($(this).attr('protected'))) {
          case 0: $('#filestorage_info_6').html(getlocalmsg(334)); break;
          case 1: $('#filestorage_info_6').html(getlocalmsg(335)); break;
          case 2: $('#filestorage_info_6').html(getlocalmsg(336)); break;
        }
        
        $('#filestorage_link').val($(this).text());
    });

    return option;
}

function filestorage(editor) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/filestorage.html?rnd='+$.now(),
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' forms/filestorage.html');
                return false;
            }

            if (typeof editor == 'undefined') {
                var popup_id = createdialog(getlocalmsg(320), xhr.responseText, [
                    [getlocalmsg(225), null]
                ]);
            } else {
                var popup_id = createdialog(getlocalmsg(320), xhr.responseText, [
                    [getlocalmsg(226), function() {
                        addfile(editor);
                    }],
                    [getlocalmsg(227), null]
                ]);
                
                if (typeof localStorage.getItem('filestorage_insertmode_target') !== 'undefined') $('#filestorage_target').val(localStorage.getItem('filestorage_insertmode_target'));
                if (typeof localStorage.getItem('filestorage_insertmode_align') !== 'undefined') $('#filestorage_align').val(localStorage.getItem('filestorage_insertmode_align'));
                if (typeof localStorage.getItem('filestorage_insertmode_icon') !== 'undefined') $('#filestorage_icon').val(localStorage.getItem('filestorage_insertmode_icon'));

            }

            if (typeof editor == 'undefined') $('#filestorage_insertparams').remove();

            for (t = 2015; t < 2025; t++) {
                $('#filestorage_year').append($('<option></option>', {
                    'value': t,
                    'text': t
                }));
            }
            preloader(false);

            loadfiles(popup_id, null, null, null);
        }
    });
}

function filestorage_delete(id) {
    if ($('#filestorage_list').val() == null) return false;
    showdialog(createdialog(getlocalmsg(330), getlocalmsg(331), [
        [getlocalmsg(197), function() {
            filestorage_delete_perform(id);
        }],
        [getlocalmsg(227), null]
    ]));
}

function filestorage_delete_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.filestorage.php',
        data: 'cmd=deletefile&' +
            'id=' + $('#filestorage_list').val(),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                hidelastdialog();
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                hidelastdialog();
                var no = $('#filestorage_list').find(':selected').index() + 1;
                $('#filestorage_list').find(':selected').remove();
                $('#filestorage_list option:nth-child(' + no + ')').attr('selected', 'selected');
                $('#filestorage_list').find(':selected').mouseup();
                preloader(false);
            }
        }
    });
}

function filestorage_new() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/filestorage_upload.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' forms/filestorage_upload.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(332), xhr.responseText, [
                [getlocalmsg(197), function() {
                    filestorage_upload();
                }],
                [getlocalmsg(227), null]
            ]);
            $('#upload_id').val(0);
            preloader(false);
            showdialog(popup_id);
        }
    });
}

function filestorage_upload() {

    preloader(true, getlocalmsg(230));

    filetodataurl('upload_filename', function(dataurl) {
    
        $.ajax({
            type: 'POST',
            cache: 'false',
            url: '../api/api.filestorage.php',
            data: 'cmd=addfile&' +
                'file=' + encodeURIComponent(dataurl) + '&' +
                'filename=' + encodeURIComponent($('#display_filename').val()) + '&' +
                'info=' + encodeURIComponent($('#upload_info').val()) + '&' +
                'protected=' + encodeURIComponent($('#upload_protected').val()) + '&' +
                'id=' + encodeURIComponent($('#upload_id').val()),
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
                    preloader(false);
                    insertfile($(xml).find('id').text());
                }
            }
        });

    });

}

function insertfile(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.filestorage.php',
        data: 'cmd=getfile&id=' + id,
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                hidelastdialog();
                preloader(false);
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {

                $(xml).find('file').each(function() {
                
                    var option = createnewoption($(this));

                    if ($('#upload_id').val() == 0) {
                        $('#filestorage_list').prepend(option);
                        $('#filestorage_list option:first-child').attr('selected', 'selected');
                        $('#filestorage_list option:first-child').mouseup();

                    } else {
                        var option = $('#filestorage_list option[value='+$(this).find('id').text()+']');

                        option.val($(this).find('id').text());
                        option.text($(this).find('filename').text());
                        option.attr('info', $.trim($(this).find('info').text()));
                        option.attr('accessed', $(this).find('accessed').text());
                        option.attr('protected', $(this).find('protected').text());
                        option.attr('filesize', $.trim($(this).find('filesize').text()));
                        option.attr('mime', $.trim($(this).find('mime').text()));
                        option.attr('created', $.trim($(this).find('created_formatted').text()));
                        option.attr('user_name', $.trim($(this).find('user_name').text()));

                        $('#filestorage_list').val(id);
                        $('#filestorage_list').find(':selected').mouseup();
                    }
                });

                hidelastdialog();
                preloader(false);
            }
        }
    });

}

function filestorage_edit(id) {

    if ($('#filestorage_list').val() == null) return false;
    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/filestorage_upload.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' forms/filestorage_upload.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(340), xhr.responseText, [
                [getlocalmsg(197), function() {
                    filestorage_upload();
                }],
                [getlocalmsg(227), null]
            ]);

            $.ajax({
                type: 'POST',
                cache: 'false',
                url: '../api/api.filestorage.php',
                data: 'cmd=getfile&id=' + id,
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

                        $('#upload_info').val($.trim($(xml).find('info').text()));
                        $('#display_filename').val($.trim($(xml).find('filename').text()));
                        $('#upload_protected').val(Number($(xml).find('protected').text()));
                        $('#upload_id').val($(xml).find('id').text());
                        preloader(false);
                        showdialog(popup_id);
                    }
                }
            });
        }
    });
}

function filestorage_search() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/filestorage_search.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' forms/filestorage_search.html');
                return false;
            }
            preloader(false);
            showdialog(createdialog(getlocalmsg(341), xhr.responseText, [
                [getlocalmsg(197), function() {
                    filestorage_perform_search();
                }],
                [getlocalmsg(227), null]
            ]));
        }
    });
}

function filestorage_perform_search() {
    var search = $('#filestorage_search').val();
    hidelastdialog();
    preloader(false);
    loadfiles(null, null, null, search);
}

function addfile(editor) {

    if ($('#filestorage_list').val() == null) {
        errormsg(getlocalmsg(342));
        return false;
    }

    localStorage.setItem('filestorage_insertmode_target', $('#filestorage_target').val());
    localStorage.setItem('filestorage_insertmode_align', $('#filestorage_align').val());
    localStorage.setItem('filestorage_insertmode_icon', $('#filestorage_icon').val());

    var div = $('<div></div>');
    if ($('#filestorage_align').val() == 0) div.attr('class', 'filestorage_link');
    if ($('#filestorage_align').val() == 1) div.attr('class', 'filestorage_link filestorage_link_left');
    if ($('#filestorage_align').val() == 2) div.attr('class', 'filestorage_link filestorage_link_right');
    
    div.append($('<a></a>', { 'href': 'api/api.download.php?id='+$('#filestorage_list').val(), 
                              'target': $('#filestorage_target').val() 
                            }));
    
    if ($('#filestorage_icon').val() != 0) {
      var mime = $('#filestorage_list').find('option:selected').attr('mime');
      mime = mime.replace(/\//g, '-');
      var img = $('<img />', { 'src': '../pic/fileicons/'+$('#filestorage_icon').val()+'/' + mime + '.png',
                               'class': 'filestorage_icon'
                              });
      div.find('a:last').append(img);
    }
    
    if ($('#filestorage_link').val() != '') {
      if ($('#filestorage_icon').val() != 0 && $('#filestorage_align').val() != 0) div.find('a:last').append($('<br />'));
      div.find('a:last').append($.trim($('#filestorage_link').val()));
    }
    
    var html = div.prop('outerHTML');

    editor.selection_replace_with(html);
    hidelastdialog();
}