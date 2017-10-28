$(document).ready(function() {
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();
});

function load_data() {

    hidedialog();
    preloader(true);

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: '../xmldata/languages.xml?rnd='+$.now(),
        dataType: 'xml',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' xmldata/languages.xml');
                return false;
            }

            var xml = jQuery.parseXML(xhr.responseText);
            $('#main_table_body').empty();

            $(xml).find('language').each(function() {
                $(this).append('<flag_pic><![CDATA[<center><img src="../pic/flags/' + $(this).find('flag').text() + '" class="flag" /></center>]]></flag_pic>');
            });

            datatable(
                [
                    [237, 'id'],
                    [247, 'name'],
                    [246, 'nameeng'],
                    [252, 'flag_pic'],
                    [250, 'selectable', 'boolean']
                ], [],
                $(xml),
                'language',
                new Object({
                    translatable: false,
                    deletable: false,
                    paginator: false
                })
            );
            preloader(false);
        }
    });
}


function editrecord(id) {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/languages.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/languages.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(253), xhr.responseText, [
                [getlocalmsg(197), function() {
                    editrecord_perform(id);
                }],
                [getlocalmsg(227), null]
            ]);

            $.ajax({
                type: 'GET',
                cache: 'false',
                url: '../xmldata/languages.xml?rnd='+$.now(),
                dataType: 'xml',

                complete: function(xhr) {
                    if (xhr.status != 200) {
                        errormsg(getlocalmsg(220)+' xmldata/languages.xml');
                        return false;
                    }

                    var xml = jQuery.parseXML(xhr.responseText);

                    $(xml).find('language').each(function() {

                        if ($(this).attr('id') == id) {
                            $('#id').val($(this).find('id').text());
                            $('#name').val($.trim($(this).find('name').text()));
                            $('#nameeng').val($.trim($(this).find('nameeng').text()));
                            $('#date').val($(this).find('date').text());
                            $('#encoding').val($(this).find('encoding').text());
                            $('#langcode').val($(this).find('langcode').text());
                            $('#googlecode').val($(this).find('googlecode').text());
                            $('#thousandsseparator').val($(this).find('thousandsseparator').text());
                            $('#decimalpoint').val($(this).find('decimalpoint').text());
                            $('#selectable').val($(this).find('selectable').text());
                        }
                    });
                    preloader(false);

                    showdialog(popup_id);
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
        data: 'cmd=updatelanguage&' +
            'id=' + id + '&' +
            'name=' + encodeURIComponent($('#name').val()) + '&' +
            'nameeng=' + encodeURIComponent($('#nameeng').val()) + '&' +
            'date=' + encodeURIComponent($('#date').val()) + '&' +
            'encoding=' + encodeURIComponent($('#encoding').val()) + '&' +
            'langcode=' + encodeURIComponent($('#langcode').val()) + '&' +
            'googlecode=' + encodeURIComponent($('#googlecode').val()) + '&' +
            'thousandsseparator=' + encodeURIComponent($('#thousandsseparator').val()) + '&' +
            'decimalpoint=' + encodeURIComponent($('#decimalpoint').val()) + '&' +
            'selectable=' + encodeURIComponent($('#selectable').val()),
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
                $('#datarow_' + id).find('td:nth-child(2)').text($('#name').val());
                $('#datarow_' + id).find('td:nth-child(3)').text($('#nameeng').val());
                $('#datarow_' + id).find('td:nth-child(5)').text($('#selectable').find(':selected').text());
                hidedialog();
                preloader(false);
            }
        }
    });
}