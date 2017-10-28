function paginator_change() {
    load_data($('#paginator').val());
}

function load_data(start) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=getuser&' +
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
                    [237, 'id'],
                    [155, 'name'],
                    [306, 'nickname'],
                    [307, 'company'],
                    [308, 'email'],
                    [309, 'active'],
                    [310, 'admin']
                ];

                datatable(columns, [], $(xml), 'user', new Object({
                    info: true,
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
        url: '../api/api.userdata.php',
        data: 'cmd=getuser&' +
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

                if ($(xml).find('email').text() == '') {
                    errormsg(getlocalmsg(311));
                    return false;
                }

                $.ajax({
                    type: 'GET',
                    cache: 'false',
                    url: 'forms/users.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/users.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(312), xhr.responseText, [
                            [getlocalmsg(197), function() {
                                newrecord_perform();
                            }],
                            [getlocalmsg(227), null]
                        ]);

                        filllanguages('user_language', true);
                        fillcountries('country', function() {

                            $('#company_country').empty();
                            $('#country option').clone().appendTo('#company_country');
                            $('#company_country').val($('#country').val());

                            $(xml).find('user').each(function() {

                                $('#id').val($(this).find('id').text());
                                $('#name').val($(this).find('name').text());
                                $('#nickname').val($(this).find('nickname').text());
                                $('#username').val($(this).find('username').text());
                                $('#password1').val($(this).find('password').text());
                                $('#password2').val($(this).find('password').text());
                                $('#email').val($(this).find('email').text());
                                $('#user_language').val($(this).find('language').text());
                                $('#state').val($(this).find('state').text());
                                $('#zip').val($(this).find('zip').text());
                                $('#city').val($(this).find('city').text());
                                $('#address').val($(this).find('address').text());
                                $('#phone1').val($(this).find('phone1').text());
                                $('#phone2').val($(this).find('phone2').text());
                                $('#phone3').val($(this).find('phone3').text());
                                $('#admin').attr('checked', $(this).find('admin').text() == '1');
                                $('#newsletter').attr('checked', $(this).find('newsletter').text() == '1');
                                $('#active').attr('checked', $(this).find('active').text() == '1');
                                $('#country').val($(this).find('country').text());

                                if ($(this).find('company').text() != '') {
                                    $('#company').val($(this).find('company').text());
                                    $('#company_country').val($(this).find('company_country').text());
                                    $('#company_state').val($(this).find('company_state').text());
                                    $('#company_zip').val($(this).find('company_zip').text());
                                    $('#company_city').val($(this).find('company_city').text());
                                    $('#company_address').val($(this).find('company_address').text());
                                }

                            });

                            preloader(false);
                            showdialog(popup_id);

                        });
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
        url: 'forms/users.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' forms/users.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(313), xhr.responseText, [
                [getlocalmsg(197), function() {
                    newrecord_perform();
                }],
                [getlocalmsg(227), null]
            ]);
            $('#id').val(0);
            fillcountries('country', function() {

                $('#company_country').empty();
                $('#country option').clone().appendTo('#company_country');
                $('#company_country').val($('#country').val());
                filllanguages('user_language', false);
                changecountry();
                preloader(false);
                showdialog(popup_id);
            });

        }
    });
}

function newrecord_perform() {

    if ($('#company').val() == '') {
        $('#company_state').val('');
        $('#company_zip').val('');
        $('#company_city').val('');
        $('#company_address').val('');
    }

    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=register&' +
            'id=' + encodeURIComponent($('#id').val()) + '&' +
            'name=' + encodeURIComponent($('#name').val()) + '&' +
            'nickname=' + encodeURIComponent($('#nickname').val()) + '&' +
            'username=' + encodeURIComponent($('#username').val()) + '&' +
            'password1=' + encodeURIComponent($('#password1').val()) + '&' +
            'password2=' + encodeURIComponent($('#password2').val()) + '&' +
            'email=' + encodeURIComponent($('#email').val()) + '&' +
            'zip=' + encodeURIComponent($('#zip').val()) + '&' +
            'city=' + encodeURIComponent($('#city').val()) + '&' +
            'address=' + encodeURIComponent($('#address').val()) + '&' +
            'state=' + encodeURIComponent($('#state').val()) + '&' +
            'country=' + encodeURIComponent($('#country').val()) + '&' +
            'phone1=' + encodeURIComponent($('#phone1').val()) + '&' +
            'phone2=' + encodeURIComponent($('#phone2').val()) + '&' +
            'phone3=' + encodeURIComponent($('#phone3').val()) + '&' +
            'language=' + $('#user_language').val() + '&' +
            'newsletter=' + String($('#newsletter').prop('checked')) + '&' +
            'admin=' + String($('#admin').prop('checked')) + '&' +
            'active=' + String($('#active').prop('checked')) + '&' +
            'company=' + encodeURIComponent($('#company').val()) + '&' +
            'company_zip=' + encodeURIComponent($('#company_zip').val()) + '&' +
            'company_city=' + encodeURIComponent($('#company_city').val()) + '&' +
            'company_address=' + encodeURIComponent($('#company_address').val()) + '&' +
            'company_state=' + encodeURIComponent($('#company_state').val()) + '&' +
            'company_country=' + encodeURIComponent($('#company_country').val()),

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
                preloader(false);
                load_data($('#paginator').val());
                if ($('#id').val() == 0) { errormsg(getlocalmsg(314), 'info'); } else { errormsg(getlocalmsg(315), 'info'); }
            }
        }
    });

}

function deleterecord(id) {
    showdialog(createdialog(getlocalmsg(316), getlocalmsg(317), [
        [getlocalmsg(45), function() {
            deleterecord_perform(id, $('#deleterecord_contents').prop('checked'));
        }],
        [getlocalmsg(227), null]
    ]));
}

function deleterecord_perform(id, deletecontent) {

    hidedialog();
    preloader(true);

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=deleteuser&' +
            'id=' + id,
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

function inforecord(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=getuser&' +
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
                    url: 'forms/user_info.html',
                    dataType: 'html',

                    complete: function(xhr) {
                        if (xhr.status != 200) {
                            errormsg(getlocalmsg(220)+' forms/user_info.html');
                            return false;
                        }
                        var popup_id = createdialog(getlocalmsg(222), xhr.responseText, [
                            [getlocalmsg(225), null]
                        ]);

                        $(xml).find('pgcms > user').each(function() {

                            $('#id').html($(this).find('id').text());
                            $('#name').html($(this).find('name').text());
                            $('#nickname').html($(this).find('nickname').text());
                            $('#email').html($(this).find('email').text());

                            $('#language').append($('<img />', {
                                'src': '../pic/flags/' + $(this).find('flag').text(),
                                'style': 'margin-right: 7px; vertical-align: middle;'
                            }));
                            $('#language').append($(this).find('language_name').text() + ' (' + $(this).find('language_nameeng').text() + ')');
                            $('#phone').html('+' + $(this).find('phone1').text() + ' (' + $(this).find('phone2').text() + ') ' + $(this).find('phone3').text());

                            if ($.trim($(this).find('address').text()) != '') $('#address').html($.trim($(this).find('address').text()) + '<br />');
                            $('#address').append($(this).find('zip').text() + ' ' + $(this).find('city').text() + '<br />');
                            if ($.trim($(this).find('state').text()) != '') $('#address').append($(this).find('state').text() + '<br />');
                            $('#address').append($(this).find('country_name').text());

                            if ($(this).find('company').text() == '') {
                                $('#userinfo_company').remove();
                            } else {
                                $('#company').html($.trim($(this).find('company').text()));
                                if ($.trim($(this).find('company_address').text()) != '') $('#company_address').html($.trim($(this).find('company_address').text()) + '<br />');
                                $('#company_address').append($(this).find('company_zip').text() + ' ' + $(this).find('company_city').text() + '<br />');
                                if ($.trim($(this).find('company_state').text()) != '') $('#company_address').append($(this).find('company_state').text() + '<br />');
                                $('#company_address').append($(this).find('company_country_name').text());
                            }

                            if ($(this).find('active').text() == '1') $('#rights').append(getlocalmsg(173)+'<br />');
                            if ($(this).find('newsletter').text() == '1') $('#rights').append(getlocalmsg(174)+'<br />');
                            if ($(this).find('admin').text() == '1') $('#rights').append(getlocalmsg(175)+'<br />');
                        });
                        preloader(false);

                        showdialog(popup_id);
                    }
                });
            }
        }
    });
}

function changecountry() {

    $('#phone1').val($(document.countries_xml).find('[id=' + $('#country').val() + ']').find('phonecode').text());
    var language = $(document.countries_xml).find('[id=' + $('#country').val() + ']').find('language').text();

    if ($('#user_language option[value=' + language + ']').length > 0) {
        $('#user_language').val(language);
    } else {
        $('#user_language').val(document.default_language);
    }
}

$(document).ready(function() {
    $.ajaxSetup({
        cache: false
    });

    loadlocalization();

    //  get default language

    $.ajax({
        type: 'POST',
        url: '../api/api.system.php',
        data: 'cmd=getsetting&token=DEFAULT_LANGUAGE',
        dataType: 'xml',

        complete: function(xhr) {
            if (xhr.responseText == null || xhr.responseText == '') return false;

            var error = 0;
            $(xml).find('error').each(function() {
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                var xml = jQuery.parseXML(xhr.responseText);
                document.default_language = $(xml).find('value').text();
            }

        }
    });

    //  get list of countries

    $.ajax({
        type: 'GET',
        url: '../xmldata/countries.xml?rnd=' + $.now(),
        dataType: 'xml',

        complete: function(xhr) {
            if (xhr.responseText == null || xhr.responseText == '') return false;
            document.countries_xml = jQuery.parseXML(xhr.responseText);
        }
    });

});