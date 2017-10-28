function loadgallery(popup_id, year, month, search) {

    if (typeof search == 'undefined' || search == null) search = '';
    if (year == null || month == null || typeof year == 'undefined' || typeof month == 'undefined') {
        var date = new Date();
        year = date.getFullYear();
        month = date.getMonth() + 1;
    }

    $('#gallery_year').val(year);
    $('#gallery_month').val(month);

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.gallery.php',
        data: 'cmd=getimage&' +
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

                $('#gallery_list').empty();
                $('#gallery_preview').attr('src', '');
                $('#gallery_caption_preview').html(getlocalmsg(72));
                $('#gallery_caption').val('');
                $('[id^=gallery_info]').html(getlocalmsg(74));

                $(xml).find('image').each(function() {
                    var option = createnewoption($(this));
                    $('#gallery_list').append(option);
                });

                $('#gallery_list').focus();
                $('#gallery_list option:first-child').attr('selected', 'selected');
                $('#gallery_list option:first-child').mouseup();

                if (localStorage.getItem('gallery_insertmode_size') != null) $('#gallery_insertmode_size').val(localStorage.getItem('gallery_insertmode_size'));
                if (localStorage.getItem('gallery_insertmode_align') != null) $('#gallery_insertmode_align').val(localStorage.getItem('gallery_insertmode_align'));
                if (localStorage.getItem('gallery_caption_enable') != null) $('#gallery_caption_enable').prop('checked', localStorage.getItem('gallery_caption_enable'));

                preloader(false);
                if ($('#popup_' + popup_id).css('visibility') == 'hidden') showdialog(popup_id);

            }
        }
    });
}

function createnewoption(xml) {

    var text = $.trim(xml.find('info').text());
    if (text.length > 40) text = text.substr(0, 40) + '...';

    var option = $('<option></option>', {
        'value': xml.find('id').text(),
        'text': text,
        'filename': xml.find('filename').text(),
        'filesize': $.trim(xml.find('filesize').text()),
        'picwidth': xml.find('width').text(),
        'picheight': xml.find('height').text(),
        'created': $.trim(xml.find('created_formatted').text()),
        'user_name': $.trim(xml.find('user_name').text()),
        'caption': $.trim(xml.find('caption').text()),
        'style': 'max-height: 1em'
    });

    option.unbind('mouseup');
    option.mouseup(function() {

        preloader(true);
        console.log('triggered!');

        $('#gallery_preview_holder').html('<img class="gallery_preview" id="gallery_preview" src="../userdata/gallery/' + $(this).attr('filename') + '?rnd='+$.now()+'" />');

        if ($(this).attr('caption') != '') {
            $('#gallery_caption_preview').html($(this).attr('caption'));
            $('#gallery_caption').val($(this).attr('caption'));
            $('#gallery_insertmode_caption').prop('checked', true);
            $('#gallery_insertmode_caption').prop('disabled', false);
        } else {
            $('#gallery_caption_preview').html(getlocalmsg(72));
            $('#gallery_caption').val('');
            $('#gallery_insertmode_caption').prop('checked', false);
            $('#gallery_insertmode_caption').prop('disabled', true);
        }

        $('#gallery_info_1').html($(this).attr('filename'));
        $('#gallery_info_2').html($(this).attr('filesize'));
        $('#gallery_info_3').html($(this).attr('picwidth') + ' &#215; ' + $(this).attr('picheight'));
        $('#gallery_info_4').html($(this).attr('created'));
        $('#gallery_info_5').html($(this).attr('user_name'));
        preloader(false);

    });

    return option;
}

function imagegallery(editor) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/gallery.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' forms/gallery.html');
                return false;
            }

            if (typeof editor == 'undefined') {
                var popup_id = createdialog(getlocalmsg(224), xhr.responseText, [
                    [getlocalmsg(225), null]
                ]);
                
                $('#gallery_insertparams').remove();
                $('#gallery_list').css('height', '320px'); 

            } else {
                var popup_id = createdialog(getlocalmsg(224), xhr.responseText, [
                    [getlocalmsg(226), function() {
                        addimage(editor);
                    }],
                    [getlocalmsg(227), null]
                ]);
                $('#gallery_list').css('height', '180px');
                
                if (typeof localStorage.getItem('gallery_insertmode_size') !== 'undefined') $('#gallery_insertmode_size').val(localStorage.getItem('gallery_insertmode_size'));
                if (typeof localStorage.getItem('gallery_insertmode_align') !== 'undefined') $('#gallery_insertmode_align').val(localStorage.getItem('gallery_insertmode_align'));
                if (typeof localStorage.getItem('gallery_caption_enable') !== 'undefined') $('#gallery_caption_enable').prop(localStorage.getItem('gallery_caption_enable'));
            }

            for (var t = 2015; t < 2025; t++) {
                $('#gallery_year').append($('<option></option>', {
                    'value': t,
                    'text': t
                }));
            }
            preloader(false);

            loadgallery(popup_id, null, null, null);
        }
    });
}

function gallery_new() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/gallery_upload.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' admin/forms/gallery_upload.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(228), xhr.responseText, [
                [getlocalmsg(197), function() {
                    gallery_upload();
                }],
                [getlocalmsg(227), null]
            ]);
            $('#upload_id').val(0);
            preloader(false);
            showdialog(popup_id);
        }
    });
}

function gallery_upload() {

    var max_size = (4096 * 2048);
    var img = $('#upload_preview').get(0);

    if ((img.naturalWidth * img.naturalHeight) > max_size) {
        errormsg(getlocalmsg(229));
        return false;
    }
    preloader(true, getlocalmsg(230));

    filetodataurl('upload_filename', function(dataurl) {

        $.ajax({
            type: 'POST',
            cache: 'false',
            url: '../api/api.gallery.php',
            data: 'cmd=addimage&' +
                'image=' + encodeURIComponent(dataurl) + '&' +
                'caption=' + encodeURIComponent($('#upload_caption').val()) + '&' +
                'info=' + encodeURIComponent($('#upload_info').val()) + '&' +
                'id=' + $('#upload_id').val(),
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
                    preloader(false);
                    insertimage($(xml).find('id').text());
                }
            }
        });

    });

}

function insertimage(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.gallery.php',
        data: 'cmd=getimage&id=' + id,
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

                $(xml).find('image').each(function() {
                
                    var option = createnewoption($(this));

                    if ($('#upload_id').val() == 0) {
                        $('#gallery_list').prepend(option);
                        $('#gallery_list option:first-child').attr('selected', 'selected');
                        $('#gallery_list option:first-child').mouseup();

                    } else {
                        var option = $('#gallery_list option[value='+$(this).find('id').text()+']');
                        var text = $.trim($(this).find('info').text());
                        if (text.length > 40) text = text.substr(0, 40) + '...';
                        option.text(text);
                        option.attr('filename', $(this).find('filename').text());
                        option.attr('filesize', $(this).find('filesize').text());
                        option.attr('picwidth', $(this).find('width').text());
                        option.attr('picheight', $(this).find('height').text());
                        option.attr('created', $(this).find('created_formatted').text());
                        option.attr('user_name', $(this).find('user_name').text());
                        option.attr('caption', $.trim($(this).find('caption').text()));
                        $('#gallery_list').val(id);
                        $('#gallery_list').find(':selected').mouseup();
                    }
                });

                hidelastdialog();
                preloader(false);
            }
        }
    });

}

function gallery_delete(id) {
    if ($('#gallery_list').val() == null) return false;
    showdialog(createdialog(getlocalmsg(231), getlocalmsg(232), [
        [getlocalmsg(197), function() {
            gallery_delete_perform(id);
        }],
        [getlocalmsg(227), null]
    ]));
}

function gallery_delete_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.gallery.php',
        data: 'cmd=deleteimage&' +
            'id=' + $('#gallery_list').val(),
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
                var no = $('#gallery_list').find(':selected').index() + 1;
                $('#gallery_list').find(':selected').remove();
                $('#gallery_list option:nth-child(' + no + ')').attr('selected', 'selected');
                $('#gallery_list').find(':selected').mouseup();
                preloader(false);
            }
        }
    });
}

function gallery_edit(id) {

    if ($('#gallery_list').val() == null) return false;
    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/gallery_upload.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' admin/forms/gallery_upload.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(233), xhr.responseText, [
                [getlocalmsg(197), function() {
                    gallery_upload();
                }],
                [getlocalmsg(227), null]
            ]);

            $.ajax({
                type: 'POST',
                cache: 'false',
                url: '../api/api.gallery.php',
                data: 'cmd=getimage&id=' + id,
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
                        $('#upload_caption').val($.trim($(xml).find('caption').text()));
                        $('#upload_caption_preview').html($.trim($(xml).find('caption').text()));
                        $('#upload_id').val(id);

                        $('#upload_preview').unbind('load');
                        $('#upload_preview').load(function() {
                            $('#upload_preview').attr('src', '../userdata/gallery/' + $(xml).find('filename').text());
                            $('#upload_preview').unbind('load');
                        });

                        preloader(false);

                        showdialog(popup_id);
                        $('#upload_preview').attr('src', '../pic/gallery_preview_loader.gif');
                    }
                }
            });
        }
    });
}

function addimage(editor) {

    if ($('#gallery_list').val() == null) {
        errormsg(getlocalmsg(234));
        return false;
    }

    localStorage.setItem('gallery_insertmode_size', $('#gallery_insertmode_size').val());
    localStorage.setItem('gallery_insertmode_align', $('#gallery_insertmode_align').val());
    localStorage.setItem('gallery_caption_enable', $('#gallery_caption_enable').prop('checked'));

    var div = $('<div></div>', { 'style': 'text-align: center;' });
    if ($('#gallery_insertmode_align').val() == 1) div.addClass('float-left');
    if ($('#gallery_insertmode_align').val() == 2) div.addClass('float-right');

    var id = String(Math.floor(Math.random() * 65535) + 1);
    var img = $('<img />', {
        'id': 'img_' + id,
        'src': '../userdata/gallery/' + $('#gallery_list option:selected').attr('filename')
    });

    var img_class = '';
    if ($('#gallery_insertmode_size').val() == 0) img_class = 'article_image thumbnail';
    if ($('#gallery_insertmode_size').val() == 1) img_class = 'article_image photo';
    if ($('#gallery_insertmode_size').val() == 2) img_class = 'article_image';
    img.attr('class', img_class);
    
    div.append(img);

    if ($('#gallery_caption_enable').prop('checked') == true && $('#gallery_caption').val() != '') {
        div.append($('<div></div>', { 'class': 'article_caption' }));
        div.find('div:last').append($.trim($('#gallery_caption').val()));
    }

    editor.selection_replace_with(div.prop('outerHTML'));
    hidelastdialog();
}

function gallery_search() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/gallery_search.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' admin/forms/gallery_search.html');
                return false;
            }
            preloader(false);
            showdialog(createdialog(getlocalmsg(235), xhr.responseText, [
                [getlocalmsg(197), function() {
                    gallery_perform_search();
                }],
                [getlocalmsg(227), null]
            ]));
        }
    });
}

function gallery_perform_search() {
    var search = $('#gallery_search').val();
    hidelastdialog();
    preloader(false);
    loadgallery(null, null, null, search);
}

function gallery_resize() {

    if ($('#gallery_list').val() == null) return false;
    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/gallery_resize.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220) + ' admin/forms/gallery_resize.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(236), xhr.responseText, [
                [getlocalmsg(197), function() {
                    gallery_perform_resize();
                }],
                [getlocalmsg(227), null]
            ]);

            $('#resize_id').val($('#gallery_list').val());

            var img = new Image();
            $(img).load(function() {
                $('#resize_x').val($(img).get(0).naturalWidth);
                $('#resize_y').val($(img).get(0).naturalHeight);
                $('#resize_x_original').val($(img).get(0).naturalWidth);
                $('#resize_y_original').val($(img).get(0).naturalHeight);
                $('#resize_x').keyup(function(key) {
                    resize_keepratio('x', key.keyCode);
                });
                $('#resize_y').keyup(function(key) {
                    resize_keepratio('y', key.keyCode);
                });
                $(img).unbind('load');
                preloader(false);
                showdialog(popup_id);
            });
            $(img).attr('src', $('#gallery_preview').attr('src'));
        }
    });
}

function resize_keepratio(changed, keycode) {

    var originalwidth = $('#resize_x_original').val();
    var originalheight = $('#resize_y_original').val();

    if (keycode == 27) {
        $('#resize_x').val(originalwidth);
        $('#resize_y').val(originalheight);
        return false;
    }

    if (!(keycode >= 48 && keycode <= 57) && !(keycode >= 96 && keycode <= 105)) return false;

    if ($('#proportional').prop('checked') == false) return false;
    if ((isNaN($('#resize_x').val()) || isNaN($('#resize_y').val()) || $('#resize_x').val() < 10 || $('#resize_y').val() < 10)) return false;

    var xsize = Number($('#resize_x').val());
    var ysize = Number($('#resize_y').val());

    if (changed == 'x') {
        var percent = xsize / (originalwidth / 100);
        $('#resize_y').val(Math.floor(percent * (originalheight / 100)));
    }

    if (changed == 'y') {
        var percent = ysize / (originalheight / 100);
        $('#resize_x').val(Math.floor(percent * (originalwidth / 100)));
    }
}

function gallery_perform_resize() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.gallery.php',
        data: 'cmd=resizeimage&' +
            'id=' + $('#resize_id').val() + '&' +
            'rotate=' + $('#rotate').val() + '&' +
            'flip=' + $('#flip').val() + '&' +
            'resize=' + $('#resize').val() + '&' +
            'resize_x=' + $('#resize_x').val() + '&' +
            'resize_y=' + $('#resize_y').val(),
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
            }
        }
    });
}