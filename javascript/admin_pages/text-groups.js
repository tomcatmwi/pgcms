function paginator_change() {
    load_data($('#paginator').val());
}

function load_data(start) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
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
                    [6, 'token'],
                    [7, 'info']
                ];

                datatable(columns, [], $(xml), 'group', new Object({
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
        url: '../api/api.texts.php',
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
                    url: 'forms/text-groups.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/text-groups.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(295), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);
                        $(xml).find('group').each(function() {
                            $('#id').val($(this).find('id').text());
                            $('#token').val($(this).find('token').text());
                            $('#info').val($.trim($(this).find('info').text()));
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
        url: 'forms/text-groups.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/text-groups.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(296), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            $('#id').val(0);
            preloader(false);
            showdialog(popup_id);
        }
    });
}

function newrecord_perform() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.texts.php',
        data: 'cmd=addgroup&' +
            'id=' + encodeURIComponent($('#id').val()) + '&' +
            'token=' + encodeURIComponent($('#token').val()) + '&' +
            'info=' + encodeURIComponent($('#info').val()),

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
                load_data($('#paginator').val());
                preloader(false);
                if ($('#id').val() == 0) { var msg = getlocalmsg(297); } else { var msg = getlocalmsg(298); }
                msg = msg.replace(/%group%/g, $(xml).find('token').text());
                errormsg(msg, 'info');
            }
        }
    });

}

function deleterecord(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/text-groups-delete.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/text-groups-delete.html');
                return false;
            }
            showdialog(createdialog(getlocalmsg(299), xhr.responseText, [
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
        url: '../api/api.texts.php',
        data: 'cmd=deletegroup&' +
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