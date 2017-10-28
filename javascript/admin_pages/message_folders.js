function paginator_change() {
    load_data($('#paginator').val());
}

function load_data(start) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=getfolder&' +
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
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {

                hidedialog();
                $('#main_table_body').empty();

                var columns = [
                    [268, 'title'],
                    [269, 'messages']
                ];

                datatable(columns, [], $(xml), 'folder', new Object({
                    translatable: false
                }));
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
        data: 'cmd=getfolder&' +
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
                    url: 'forms/message-folders.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/message-folders.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(269), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);
                        $(xml).find('folder').each(function() {
                            $('#id').val($(this).find('id').text());
                            $('#title').val($(this).find('title').text());
                        });
                        showdialog(popup_id);
                        preloader(false);
                    }
                });
            }
        }
    });
}

function newrecord() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/message-folders.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/message-folders.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(270), xhr.responseText, [
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
        url: '../api/api.messages.php',
        data: 'cmd=addfolder&' +
            'id=' + encodeURIComponent($('#id').val()) + '&' +
            'title=' + encodeURIComponent($('#title').val()),

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
                if ($('#id').val() == 0) {
                    hidedialog();
                    preloader(false);
                    load_data($('#paginator').val());
                } else {
                    hidedialog();
                    preloader(false);
                    load_data($('#paginator').val());
                }
            }
        }
    });

}

function deleterecord(id) {

    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/message-folders-delete.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/message-folders-delete.html');
                return false;
            }
            showdialog(createdialog(getlocalmsg(271), xhr.responseText, [
                [getlocalmsg(45), function() {
                    deleterecord_perform(id, $('#deleterecord_contents').prop('checked'));
                }],
                [getlocalmsg(227), null]
            ]));
            preloader(false);
        }
    });

}

function deleterecord_perform(id, deletecontent) {

    hidedialog();
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.messages.php',
        data: 'cmd=deletefolder&' +
            'id=' + id + '&' +
            'deletecontent=' + String(deletecontent),
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
                $('#datarow_' + $(xml).find('id').text()).remove();
                preloader(false);
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