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

            var language_xml = jQuery.parseXML(xhr.responseText);

            $.ajax({
                type: 'GET',
                cache: 'false',
                url: '../xmldata/countries.xml?rnd='+$.now(),
                dataType: 'xml',

                complete: function(xhr) {
                    if (xhr.status != 200) {
                        errormsg(getlocalmsg(220)+' xmldata/countries.xml');
                        return false;
                    }

                    var xml = jQuery.parseXML(xhr.responseText);
                    $('#main_table_body').empty();

                    $(xml).find('country').each(function() {
                        if ($(this).find('language').text() == '0') {
                            $(this).append('<language_name><![CDATA[<span pgcms=245></span>]]></language_name>');
                        } else {
                            var langname = '<![CDATA[<span pgcms=245></span>]]>';
                            var id = $(this).find('language').text();
                            $(language_xml).find('language').each(function() {
                                if ($(this).attr('id') == id) langname = '<![CDATA[<img src="../pic/flags/' + $(this).find('flag').text() + '"> ' + $(this).find('name').text() + ' (' + $(this).find('nameeng').text() + ')]]>';
                            });

                            $(this).append('<language_name>' + langname + '</language_name>');
                        }

                    });

                    datatable(
                        [
                            [237, 'id'],
                            [246, 'nameeng'],
                            [247, 'name'],
                            [248, 'language_name'],
                            [249, 'currency'],
                            [250, 'selectable', 'boolean']
                        ], [],
                        $(xml),
                        'country',
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
    });
}


function editrecord(id) {

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/countries.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg(getlocalmsg(200)+' forms/countries.html');
                return false;
            }

            var popup_id = createdialog(getlocalmsg(251), xhr.responseText, [
                [getlocalmsg(197), function() {
                    editrecord_perform(id);
                }],
                [getlocalmsg(227), null]
            ]);
            
            filllanguages('language', false, function() {
              $('#language').prepend($('<option></option>', { 'value': 0, 'text': getlocalmsg(42) }));

              $.ajax({
                  type: 'GET',
                  cache: 'false',
                  url: '../xmldata/countries.xml?rnd='+$.now(),
                  dataType: 'xml',

                  complete: function(xhr) {
                      if (xhr.status != 200) {
                          errormsg(getlocalmsg(220)+' xmldata/countries.xml');
                          return false;
                      }

                      var xml = jQuery.parseXML(xhr.responseText);

                      $(xml).find('country').each(function() {

                          if ($(this).attr('id') == id) {
                              $('#id').val($(this).attr('id'));
                              $('#name').val($.trim($(this).find('name').text()));
                              $('#nameeng').val($.trim($(this).find('nameeng').text()));
                              $('#phonecode').val($.trim($(this).find('phonecode').text()));
                              $('#capital').val($.trim($(this).find('capital').text()));
                              $('#currency').val($.trim($(this).find('currency').text()));
                              $('#imperial').val($.trim($(this).find('imperial').text()));
                              $('#language').val($.trim($(this).find('language').text()));
                              $('#selectable').val($(this).find('selectable').text());
                          }
                      });

                      showdialog(popup_id);
                  }
              });
            });
        }
    });
}

function editrecord_perform(id) {

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=updatecountry&' +
            'id=' + id + '&' +
            'name=' + encodeURIComponent($('#name').val()) + '&' +
            'nameeng=' + encodeURIComponent($('#nameeng').val()) + '&' +
            'capital=' + encodeURIComponent($('#capital').val()) + '&' +
            'currency=' + encodeURIComponent($('#currency').val()) + '&' +
            'phonecode=' + encodeURIComponent($('#phonecode').val()) + '&' +
            'imperial=' + encodeURIComponent($('#imperial').val()) + '&' +
            'language=' + encodeURIComponent($('#language').val()) + '&' +
            'selectable=' + encodeURIComponent($('#selectable').val()),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') return false;
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                $('#datarow_' + id).find('td:nth-child(2)').text($('#nameeng').val());
                $('#datarow_' + id).find('td:nth-child(3)').text($('#name').val());
                $('#datarow_' + id).find('td:nth-child(4)').text($('#language').find(':selected').text());
                $('#datarow_' + id).find('td:nth-child(5)').text($('#currency').val());
                $('#datarow_' + id).find('td:nth-child(6)').text($('#selectable').find(':selected').text());
                hidedialog();
            }
        }
    });
}