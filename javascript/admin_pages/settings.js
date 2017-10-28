$(document).ready(function() {
    $.ajaxSetup({
        cache: false
    });
    loadlocalization();
});

function load_data() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=getallsettings',
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
                hidedialog();
                $('#main_table_body').empty();
                datatable(
                    [
                        [6, 'id'],
                        [291, 'label'],
                        [292, 'value'],
                        [293, 'explanation'],
                        [294, 'status']
                    ], [],
                    $(xml),
                    'setting',
                    new Object({
                        translatable: false,
                        deletable: false,
                        paginator: false
                    })
                );
                preloader(false);
            }
        }

    });
}

function editrecord(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/settings.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/settings.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(253), xhr.responseText, [
                [getlocalmsg(197), function() {
                    editrecord_perform(id);
                }],
                [getlocalmsg(227), null]
            ]);

            $.ajax({
                type: 'POST',
                cache: 'false',
                url: '../api/api.system.php',
                data: 'cmd=getsetting&token=' + id,
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

                        $('#id').val($(xml).find('id').text());
                        $('#label').val($(xml).find('label').text());
                        $('#value').val($(xml).find('value').text());
                        $('#explanation').val($(xml).find('explanation').text());
                        preloader(false);
                        showdialog(popup_id);

                    }
                }
            });
        }
    });
}

function editrecord_perform(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=updatesetting&' +
            'id=' + encodeURIComponent($('#id').val()) + '&' +
            'label=' + encodeURIComponent($('#label').val()) + '&' +
            'value=' + encodeURIComponent($('#value').val()) + '&' +
            'explanation=' + encodeURIComponent($('#explanation').val()),
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
                $('#datarow_' + id).find('td:nth-child(1)').text($('#id').val());
                $('#datarow_' + id).find('td:nth-child(2)').text($('#label').val());
                $('#datarow_' + id).find('td:nth-child(3)').text($('#value').val());
                $('#datarow_' + id).find('td:nth-child(4)').text($('#explanation').val());
                hidedialog();
                preloader(false);
            }
        }
    });
}