//  General routines for pgCMS
pathprefix = '';
if (window.location.pathname.lastIndexOf('/admin') > -1) {
    pathprefix = '../';
}

function filetodataurl(input, callback) {

    var filesSelected = $('#' + input)[0].files;
    if (filesSelected.length > 0) {
        var fileToLoad = filesSelected[0];
        var fileReader = new FileReader();

        fileReader.onload = function(fileLoadedEvent) {
            callback.call(this, fileLoadedEvent.target.result);
        };

        fileReader.readAsDataURL(fileToLoad);
    } else {
        callback.call(this, '');
    }
}

function solvearticlechunks(xml) {

    $(xml).find('pgcms > article').each(function() {

        //  get text

        if ($(this).find('text').children().length > 0) {
            var text = '';
            $(this).find('text').children().each(function() {
                text += $.trim($(this).text());
            });
            $(this).find('text').text(text);
        }

        //  get intro

        if ($(this).find('intro').children().length > 0) {
            var text = '';
            $(this).find('intro').find('chunk').each(function() {
                text += $.trim($(this).text());
            });
            $(this).find('intro').text(text);
        }

    });

    return xml;
}

function fillcountries(id, callback) {

    $.ajax({
        type: 'GET',
        url: pathprefix + 'xmldata/countries.xml?rnd=' + $.now(),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                errormsg('Unable to load countries.xml');
                return false;
            }

            var xml = $.parseXML(xhr.responseText);
            $('#' + id).empty();

            $(xml).find('country').each(function() {
                if ($(this).find('selectable').text() == 1) {

                    if ($(this).find('nameeng').text() != $(this).find('name').text()) {
                        text = $(this).find('nameeng').text() + ' (' + $(this).find('name').text() + ')';
                    } else {
                        text = $(this).find('nameeng').text();
                    }

                    $('#' + id).append($('<option></option>', {
                        'value': $(this).attr('id'),
                        'text': text
                    }));
                }
            });

            //  get default country

            $.ajax({
                type: 'POST',
                cache: 'false',
                url: pathprefix + 'api/api.system.php',
                data: 'cmd=getsetting&token=DEFAULT_COUNTRY',
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

                        if ($('#' + id + ' [option=' + $(xml).find('value').text() + ']').length <= 0) {
                            $('#' + id).val($(xml).find('value').text());
                        } else {
                            $('#' + id + ' option:first').prop('selected', true);
                        }
                        if (typeof callback === 'function') callback.call();
                    }
                }
            });

        }
    });
}

function filllanguages(id, selectable, callback) {

    $.ajax({
        type: 'GET',
        url: pathprefix + 'xmldata/languages.xml?rnd=' + $.now(),
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                errormsg('Unable to load languages.xml');
                return false;
            }

            var xml = $.parseXML(xhr.responseText);
            document.languages_xml = xml;
            $('#' + id).empty();

            $(xml).find('language').each(function() {
                if ($(this).find('selectable').text() >= selectable) {
                    $('#' + id).append($('<option></option>', {
                        'value': $(this).attr('id'),
                        'text': $(this).find('nameeng').text() + ' (' + $(this).find('name').text() + ')'
                    }));
                }
            });

            //  get default language

            $.ajax({
                type: 'POST',
                cache: 'false',
                url: pathprefix + 'api/api.system.php',
                data: 'cmd=getsetting&token=DEFAULT_LANGUAGE',
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
                        $('#' + id).val($(xml).find('value').text());
                        if (typeof callback === 'function') callback.call();
                    }
                }
            });
        }
    });
}

function errormsg(message, type) {

    if (typeof errortimer !== 'undefined') window.clearTimeout(errortimer);
    $('#errormsg').remove();

    if (message.substr(0, 6) == 'pgcms=') {

        $.ajax({
            type: 'POST',
            cache: 'false',
            url: 'api/api.texts.php',
            data: 'cmd=cunnilingus&' +
                'tokens=' + encodeURIComponent(message.substr(6, message.length)),
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

                    //  Send back current contents of missing tokens
                    //  Response is ignored, this runs in the background

                    if ($(xml).find('sendback').text() == '1') {

                        var sendback = '';
                        $(xml).find('pgcms > text').each(function() {
                            if ($.trim($(this).find('text').text()) == $(this).find('token').text()) {
                                sendback = $(this).find('token').text() + '|' + $(this).find('token').text();
                            }
                        });

                        if (sendback != '') {
                            $.ajax({
                                type: 'POST',
                                cache: 'false',
                                url: 'api/api.texts.php',
                                data: 'cmd=anilingus&' +
                                    'sendback=' + encodeURIComponent(sendback),
                                dataType: 'xml'
                            });
                        }
                    }

                    //  Get error message

                    $(xml).find('pgcms > text').each(function() {
                        if ($(this).find('token').text() == message.substr(6, message.length)) {
                            errormsg_perform($.trim($(this).find('text').text()), type);
                        }
                    });
                }
            }
        });

    } else {
        errormsg_perform(message, type);
    }

    function errormsg_perform(message, type) {

        if (typeof type === 'undefined') type = 'error';
        type += 'msg';
        $('#' + type).remove();

        var errormsg = $('<div></div>', {
            'id': 'errormsg',
            'class': 'errormsg ' + type,
            'opacity': 0
        });
        errormsg.css('z-index', (document.zindex + 2));
        errormsg.append($('<img />', {
            'src': pathprefix + 'pic/messagebox_warning.png',
            'style': 'margin-right: 1.5em;'
        }));
        errormsg.append($('<div></div>', {
            'id': 'errormsg_text'
        }));
        errormsg.find('div:last').html(message);
        errormsg.click(function() {
            window.clearTimeout(errortimer);
            $(this).animate({
                opacity: 0
            }, 400, function() {
                $(this).remove();
            });
        });
        $(document.body).append(errormsg);
        errortimer = setTimeout(function() {
            $('#errormsg').animate({
                opacity: 0
            }, 400, function() {
                $('#errormsg').remove();
            });
        }, 10000);
    }
}

function filldateselector(name, setcurrenttime) {

    //  ----------------------------------------------------------------------------------------------------------------------------
    //  Dátum comboboxok

    if (typeof setcurrenttime == 'undefined') setcurrenttime = false;

    for (var t = 1940; t <= Number(new Date().getFullYear()); t++) {
        $('#' + name + '_year').append($('<option></option>', {
            'value': t,
            'text': t
        }));
    }

    var months = ['január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];
    for (var t = 0; t <= months.length - 1; t++) {
        var tt = t + 1;
        if (t < 10) {
            tt = '0' + tt;
        }
        $('#' + name + '_month').append($('<option></option>', {
            'value': t + 1,
            'text': months[t]
        }));
    }

    for (var t = 1; t <= 31; t++) {
        var tt = t;
        if (t < 10) {
            tt = '0' + t;
        }
        $('#' + name + '_day').append($('<option></option>', {
            'value': t,
            'text': tt
        }));
    }

    for (var t = 0; t <= 24; t++) {
        var tt = t;
        if (t < 10) {
            tt = '0' + t;
        }
        $('#' + name + '_hour').append($('<option></option>', {
            'value': t,
            'text': tt
        }));
    }

    for (var t = 0; t <= 59; t++) {
        var tt = t;
        if (t < 10) {
            tt = '0' + t;
        }
        $('#' + name + '_minute').append($('<option></option>', {
            'value': t,
            'text': tt
        }));
    }

    //  Hónap változtatása

    $('#' + name + '_month').change(function() {
        var day = $('#' + name + '_day').val();
        var maxday = 31;

        $('#' + name + '_day option[value!=0]').remove();
        if ($('#' + name + '_month').val() == 2) {
            if ($('#' + name + '_year').val() % 4 == 0) maxday = 29;
            else maxday = 28;
        } else if ($('#' + name + '_month').val() == 4 || $('#' + name + '_month').val() == 6 || $('#' + name + '_month').val() == 7 || $('#' + name + '_month').val() == 9 || $('#' + name + '_month').val() == 11) maxday = 30;

        for (var t = 1; t <= maxday; t++) {
            var tt = t;
            if (t < 10) {
                tt = '0' + t;
            }
            $('#' + name + '_day').append($('<option></option>', {
                'value': t,
                'text': tt
            }));
        }
        if (day <= maxday) $('#' + name + '_day').val(day);
        else $('#' + name + '_day').val(maxday);
    });

    //  Év változtatása

    $('#' + name + '_year').change(function() {
        if ($('#' + name + '_month').val() == 2) {
            var day = $('#' + name + '_day').val();
            var maxday = 28;
            $('#' + name + '_day option[value!=0]').remove();
            if ($('#' + name + '_year').val() % 4 == 0) maxday = 29;
            else maxday = 28;
            for (var t = 1; t <= maxday; t++) {
                var tt = t;
                if (t < 10) {
                    tt = '0' + t;
                }
                $('#' + name + '_day').append($('<option></option>', {
                    'value': t,
                    'text': tt
                }));
            }
            if (day <= maxday) $('#' + name + '_day').val(day);
            else $('#' + name + '_day').val(maxday);
        }
    });

    //  Aktuális dátum és idő

    if (setcurrenttime) {

        var date = new Date();
        $('#' + name + '_year').val(date.getFullYear());
        $('#' + name + '_month').val(date.getMonth() + 1);
        $('#' + name + '_day').val(date.getDate());
        $('#' + name + '_hour').val(date.getHours());
        $('#' + name + '_minute').val(date.getMinutes());
    }

}


//  -------------------------------------------------------------------------

//  GETDATEMAX function
//  Returns the number of days in a given month.

function getdatemax(mnth) {

    switch (mnth) {
        case 1:
            return (31);
        case 2:
            return (29);
        case 3:
            return (31);
        case 4:
            return (30);
        case 5:
            return (31);
        case 6:
            return (30);
        case 7:
            return (31);
        case 8:
            return (31);
        case 9:
            return (30);
        case 10:
            return (31);
        case 11:
            return (30);
        case 12:
            return (31);
    }
    return false;
}

//  -------------------------------------------------------------------------

//  GETPARAM function
//  Returns the value of a HTTP parameter

function getparam(param) {
    var value = String(decodeURI((RegExp(param + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1]));
    if (value == 'null') {
        value = '';
    }
    return value;
}

//  -------------------------------------------------------------------------
//  UNACCENT
//  Removes Hungarian accented characters from a string and replaces them with others

function unaccent(source) {
    output = '';
    for (a = 0; a < source.length; a++) {
        added = false;
        if (source.substring(a, a + 1) == 'ő') {
            output += 'õ';
            added = true;
        }
        if (source.substring(a, a + 1) == 'ű') {
            output += 'û';
            added = true;
        }
        if (source.substring(a, a + 1) == 'Ő') {
            output += 'Õ';
            added = true;
        }
        if (source.substring(a, a + 1) == 'Ű') {
            output += 'Û';
            added = true;
        }
        if (added == false) {
            output += source.substring(a, a + 1);
        }
    }
    return (output);
}

//  -------------------------------------------------------------------------
//  RETURNDOCUMENT
//  Retrieves the filename of the current document

function returnDocument() {
    var file_name = document.location.href;
    var end = (file_name.indexOf("?") == -1) ? file_name.length : file_name.indexOf("?");
    return file_name.substring(file_name.lastIndexOf("/") + 1, end);
}

//  -------------------------------------------------------------------------
//  IN_ARRAY
//  Tells if a value is present in an array

function in_array(needle, haystack) {
    for (key in haystack) {
        if (haystack[key] == needle) {
            return key;
        }
    }
    return false;
}

//  -------------------------------------------------------------------------
//  TEST
//  Tests if this thing works at all...

function test() {
    alert('it worx');
}

function nl2br(str, is_xhtml) {
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>';
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/gi, '$1' + breakTag + '$2');
}

function br2nl(string) {
    return string.replace(/<[bB][rR]\s*[\/]?>/gi, "\n");
}

function rgb2hex(rgb) {

    if (rgb.match(/(rgb)?\(?([01]?\d\d?|2[0-4]\d|25[0-5])(\W+)([01]?\d\d?|2[0-4]\d|25[0-5])\W+(([01]?\d\d?|2[0-4]\d|25[0-5])\)?)$/) == null) return '';

    var rgb = rgb.substring(rgb.indexOf('(') + 1, rgb.indexOf(')'));
    rgb = rgb.replace(/ /gi, '');

    r = rgb.substring(0, rgb.indexOf(','));
    g = rgb.substring(rgb.indexOf(',') + 1, rgb.indexOf(',', rgb.indexOf(',') + 1));
    b = rgb.substring(rgb.indexOf(',', rgb.indexOf(',') + 1) + 1);

    if (isNaN(r) || isNaN(g) || isNaN(b)) return '';

    r = ("0" + (Number(r).toString(16))).slice(-2).toUpperCase()
    g = ("0" + (Number(g).toString(16))).slice(-2).toUpperCase()
    b = ("0" + (Number(b).toString(16))).slice(-2).toUpperCase()

    return '#' + r + g + b;
}

function hex2rgb(hex) {

    if (hex.length != 7 || hex.substring(0, 1) != '#') return '';
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result == null || result.length != 4) return '';

    r = parseInt(result[1], 16);
    g = parseInt(result[2], 16);
    b = parseInt(result[3], 16);

    if (isNaN(r) || isNaN(g) || isNaN(b)) return '';
    return ('rgb (' + r + ', ' + g + ', ' + b + ')');
}

function openpanel(name) {

    if ($('#' + name).css('display') == 'none') {
        $('#' + name).css('height', '0px');
        $('#' + name).css('display', 'block');
        $('#' + name).animate({
            'height': $('#' + name).get(0).scrollHeight
        }, 500);
    } else {
        $('#' + name).animate({
            'height': '0px'
        }, 500, function() {
            $('#' + name).css('height', '0px');
            $('#' + name).css('display', 'none');
        });
    }
}