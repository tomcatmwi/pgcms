$(document).ready(function() {
    $.ajaxSetup({
        cache: false
    });
    loadlocalization();
});

function load_data() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/login.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' admin/forms/login.html');
                return false;
            }
            var popup_id = createdialog(getlocalmsg(254), xhr.responseText, [
                [getlocalmsg(197), function() {
                    login();
                }]
            ]);

            if (localStorage.getItem('login_username') != '') {
                $('#username').val(localStorage.getItem('login_username'));
                $('#password').val(localStorage.getItem('login_password'));
                $('#popup').find('input:visible:first').focus();
                $('#popup').find('input:visible:first').select();
                $('#remember').prop('checked', true);
            } else {
                $('#remember').prop('checked', false);
            }

            showdialog(popup_id);
            preloader(false);

        }
    });

}

function login() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=login&' +
            'username=' + encodeURIComponent($('#username').val()) + '&' +
            'password=' + encodeURIComponent($('#password').val()),
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
                if ($('#remember').prop('checked') == true) {
                    localStorage.setItem('login_username', $('#username').val());
                    localStorage.setItem('login_password', $('#password').val());
                } else {
                    localStorage.removeItem('login_username');
                    localStorage.removeItem('login_password');
                }

                preloader(false);
                hidelastdialog();
                document.location.href = 'index.php';
            }
        }
    });
}

function forgotpsw() {

    preloader(true);
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/login_forgotpsw.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' admin/forms/login_forgotpsw.html');
                return false;
            }
            hidedialog();
            preloader(false);
            showdialog(createdialog(getlocalmsg(255), xhr.responseText, [
                [getlocalmsg(197), function() {
                    forgotpsw_send();
                }],
                [getlocalmsg(227), function() {
                    login_show();
                }]
            ]));
        }
    });

}

function forgotpsw_send() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=forgotpassword&' +
            'email=' + encodeURIComponent($('#forgotpsw_email').val()),
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
            })

            if (error == 0) {
                preloader(false);
                errormsg(getlocalmsg(256), 'info');
                login_show();
            }
        }
    });

}