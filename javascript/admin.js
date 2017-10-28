function logout_dialog() {
    showdialog(createdialog(getlocalmsg(214), getlocalmsg(219), [
        [getlocalmsg(45), function() {
            logout();
        }],
        [getlocalmsg(44), null]
    ]));
}

function logout() {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.userdata.php',
        data: 'cmd=logout',
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
                document.location.href = 'login.php';
            }
        }
    });
}

function changelanguage(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=setlanguage&id=' + encodeURI(id),
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
                $('#mainmenu_current_language_flag').attr('src', '../pic/flags/' + $(xml).find('language_flag').text());
                document.current_language = id;
                loadlanguages();
                if (typeof load_data == 'function') { load_data(0, ''); }
            }
        }
    });
}


//  LOADLOCALIZATION
//  Loads localization XML file

function loadlocalization() {

    preloader(true);

    if (typeof document.localize_xml == 'undefined' || document.localize_xml == null) {

        $.ajax({

            type: 'POST',
            cache: 'false',
            url: '../api/api.system.php',
            data: 'cmd=getsetting&token=ADMIN_UI_LANGUAGE',
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
                        url: '../xmldata/admin_' + $(xml).find('value').text() + '.xml?rnd=' + $.now(),
                        dataType: 'xml',

                        error: function() {
                            preloader(false);
                            errormsg('Error: Can\'t load xmldata/admin_' + language + '.xml');
                            return false;
                        },

                        complete: function(xhr) {

                            try {
                                document.localize_xml = jQuery.parseXML(xhr.responseText);
                                loadmainmenu();
                            } catch (err) {
                                return false;
                            }
                        }
                    });
                }
            }
        });
    } else {
      loadmainmenu();
    }
    
}

//  LOADMAINMENU
//  Loads the main menu from XML
    
function loadmainmenu() {
  
    //  Load and display main menu items

    $('#mainmenu').empty();

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: '../xmldata/admin_menu.xml?rnd=' + $.now(),
        dataType: 'xml',

        error: function() {
            preloader(false);
            errormsg(getlocalmsg(220)+' xmldata/admin_menu.xml');
            return false;
        },

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
                preloader(false);
                errormsg(getlocalmsg(221)+' xmldata/admin_menu.xml');
                return false;
            }
            var xml = $.parseXML(xhr.responseText);

            $(xml).find('menuitem').each(function() {

                if ($(this).parents().length == 1 && $(this).find('visible:first').text() == '1') {

                    var menuelement = $('<li></li>', {
                        'class': 'dropdown'
                    });
                    var mainelement = $('<a></a>', {
                        'class': 'dropdown-toggle',
                        'data-toggle': 'dropdown',
                        'role': 'button',
                        'aria-haspopup': 'true',
                        'aria-expanded': 'false',
                        'href': $(this).find('url:first').text()
                    });

                    var title = $(this).find('title:first').text();
                    if (isNaN(title)) {
                        mainelement.text(title);
                        if ($(this).find('menuitem').length > 0) {

                            mainelement.append(' ');
                            mainelement.append($('<span></span>', {
                                'class': 'caret'
                            }));
                        }
                    } else {
                        mainelement.text('');
                        mainelement.attr('pgcms', title);
                    }


                    if ($(this).find('menuitem').length > 0) {

                        var submenu_ul = $('<ul></ul>', {
                            'class': 'dropdown-menu'
                        });

                        $(this).find('menuitem').each(function() {

                            var submenu_li = $('<li></li>');
                            submenu_li.append('<a></a>');

                            if ($(this).find('title').text() != 'SEPARATOR') {
                                var title = $.trim($(this).find('title').text());
                                if (isNaN(title)) {
                                    submenu_li.find('a:first').text(title);
                                } else {
                                    submenu_li.find('a:first').text('');
                                    submenu_li.find('a:first').attr('pgcms', title);
                                }

                                if ($(this).find('url').text() != '') submenu_li.find('a:first').attr('href', $(this).find('url').text());
                                if ($(this).find('onclick').text() != '') submenu_li.find('a:first').attr('onclick', $(this).find('onclick').text());
                            } else {
                                submenu_li.addClass('divider');
                            }

                            submenu_ul.append(submenu_li);
                        });
                    }

                    menuelement.append(mainelement);
                    menuelement.append(submenu_ul);
                    $('#mainmenu').append(menuelement);
                }
            });

            //  Load flags

            $.ajax({
                type: 'GET',
                url: '../xmldata/languages.xml?rnd=' + $.now(),
                dataType: 'xml',

                complete: function(xhr) {

                    if (xhr.responseText == null || xhr.responseText == '') {
                        errormsg(getlocalmsg(220)+' languages.xml');
                        return false;
                    }

                    var xml = $.parseXML(xhr.responseText);

                    var menuelement = $('<li></li>', {
                        'class': 'dropdown'
                    });
                    var mainelement = $('<a></a>', {
                        'class': 'dropdown-toggle',
                        'data-toggle': 'dropdown',
                        'role': 'button',
                        'aria-haspopup': 'true',
                        'aria-expanded': 'false',
                        'id': 'mainmenu_current_language'
                    });

                    mainelement.append(' ');
                    mainelement.append($('<span></span>', {
                        'class': 'caret'
                    }));

                    var submenu_ul = $('<ul></ul>', {
                        'class': 'dropdown-menu',
                        'id': 'mainmenu_language_list'
                    });

                    $(xml).find('language').each(function() {
                        if ($(this).find('selectable').text() == '1') {
                            var submenu_li = $('<li></li>');
                            submenu_li.append('<a></a>');
                            submenu_li.find('a:first').text($(this).find('name').text() + ' (' + $(this).find('nameeng').text() + ')');

                            submenu_li.attr('onclick', 'changelanguage(' + $(this).attr('id') + ');');
                            submenu_li.find('a:first').prepend($('<img />', {
                                'src': '../pic/flags/' + $(this).find('flag').text(),
                                'class': 'flag'
                            }));
                            submenu_ul.append(submenu_li);
                        }
                    });

                    menuelement.append(mainelement);
                    menuelement.append(submenu_ul);
                    $('#mainmenu').append(menuelement);

                    //  get current language

                    $.ajax({
                        type: 'POST',
                        cache: 'false',
                        url: '../api/api.system.php',
                        data: 'cmd=getlanguage',
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
                                $('#mainmenu_current_language').prepend($('<img />', {
                                    'id': 'mainmenu_current_language_flag',
                                    'src': '../pic/flags/' + $(xml).find('language_flag').text(),
                                    'class': 'flag'
                                }));
                                document.current_language = $(xml).find('pgcms > language > language').text();
                                loadlanguages();
                                localize($(document));
                                if (typeof load_data === 'function') load_data(0, '');
                            }
                        }
                    });

                }
            });

            $('#mainmenu').css('visibility', 'visible');
        }
    });

}

//  DATATABLE GENERATOR
//  Generates a normal data table
//  
//  columns     A two-dimensional array of header labels and corresponding XML node names
//  groups      A two-dimensional array of group ID field names and labels
//  xmlobject   A parseable JQuery XML data object
//  xmlnode     Name of XML node to parse
//  start       Page to start. Assumes 0 if not set.
//  options     A JS object telling if some buttons should be removed

function datatable(columns, groups, xmlobject, xmlnode, options) {

    options = typeof options !== 'undefined' ? options : new Object();

    //  Count buttons for last colspan

    var colspan = 3;
    if (options.editable == false) colspan--;
    if (options.deletable == false) colspan--;
    if (options.translatable == false) colspan--;
    if (options.info == true) colspan++;

    //  Generate table headers 

    var tablerow = $('<tr></tr>');
    for (t in columns) {
        var tablecell = $('<th></th>');

        if (!isNaN(columns[t][0])) {
            tablecell.html(getlocalmsg(columns[t][0]));
            tablecell.attr('pgcms', columns[t][0]);
        } else {
            tablecell.html(columns[t][0]);
            tablecell.removeAttr('pgcms');
        }
        tablerow.append(tablecell);
    }

    var tablecell = $('<th></th>', {
        'colspan': colspan
    });
    tablerow.append(tablecell);

    $('#main_table_header').empty();
    $('#main_table_header').append(tablerow);

    //  Generate rows

    var lastgroup = 0;
    xmlobject.find(xmlobject.find("*").eq(0)[0].nodeName + ' > ' + xmlnode).each(function() {

// group header

        if (groups.length > 0 && $(this).find(groups[0]).text() != lastgroup) {
            var tablerow = $('<tr></tr>');
            tablerow.append('<td></td>');
            tablerow.find('td:last').attr('colspan', columns.length + colspan);
            tablerow.find('td:last').attr('class', 'table_group_header');
            tablerow.find('td:last').html('<b>' + $.trim($(this).find(groups[1]).text()) + '</b>');
            $('#main_table_body').append(tablerow);
            lastgroup = $(this).find(groups[0]).text();
        }

// data fields

        var tablerow = $('<tr></tr>');
        if ($(this).find('id').text() != '') { id = $(this).find('id').text(); } 
        else if ($(this).attr('id') != '') { id = $(this).attr('id'); } 
        else { id = Math.floor(Math.random() * 65535) + 1; };

        tablerow.attr('id', 'datarow_' + id);

        if (isNaN(id)) {
            id = '\'' + id + '\'';
        }

        for (t in columns) {
            var tablecell = $('<td></td>', {
                'data-title': getlocalmsg(columns[t][0]),
                'style': 'text-overflow: ellipsis; max-width: 500px;'
            });

            if ($(this).find(columns[t][1]).text() != '') {
                var value = $.trim($(this).find(columns[t][1]).text());
            } else {
                var value = $.trim($(this).attr(columns[t][1]));
            }

            if (columns[t][2] == 'boolean') {
                if (value == '1') tablecell.attr('pgcms', 45);
                if (value == '0') tablecell.attr('pgcms', 44);
                tablecell.css('text-align', 'center');
            }

            if (value == '') value = '&nbsp;';
            tablecell.html(value);

            tablerow.append(tablecell);
        }

        if (options.info == true) {
            var tablecell = $('<td></td>', {
                'data-title': getlocalmsg(222),
                'class': 'table_icon'
            });
            tablecell.append($('<img/>', {
                'src': '../pic/icon_info.png',
                'title': getlocalmsg(7),
                'pgcms': 7,
                'onclick': 'inforecord(' + id + ');'
            }));
            tablerow.append(tablecell);
        }

        if (options.editable != false) {
            var tablecell = $('<td></td>', {
                'data-title': getlocalmsg(8),
                'class': 'table_icon'
            });
            tablecell.append($('<img/>', {
                'src': '../pic/icon_edit.png',
                'title': 'Szerkesztés',
                'pgcms': 8,
                'onclick': 'editrecord(' + id + ');'
            }));
            tablerow.append(tablecell);
        }

        if (options.translatable != false) {
            var tablecell = $('<td></td>', {
                'data-title': getlocalmsg(10),
                'class': 'table_icon'
            });
            tablecell.append($('<img/>', {
                'src': '../pic/icon_translate.png',
                'title': 'Fordítás',
                'pgcms': 10,
                'onclick': 'translation(' + id + ');'
            }));
            tablerow.append(tablecell);
        }

        if (options.deletable != false) {
            var tablecell = $('<td></td>', {
                'data-title': getlocalmsg(9),
                'class': 'table_icon'
            });
            tablecell.append($('<img/>', {
                'src': '../pic/icon_delete.png',
                'title': 'Törlés',
                'pgcms': 9,
                'onclick': 'deleterecord(' + id + ');'
            }));
            tablerow.append(tablecell);
        }

        $('#main_table_body').append(tablerow);
    });

    localize($('#main_table_body'));

    //  fill paginator

    if (options.paginator == false) {
        $('#paginator').css('visibility', 'hidden');
    } else {

        $('#paginator').empty();
        $('#paginator').css('visibility', 'visible');
        var page = 1;
        var records = Number(xmlobject.find('maxcount').text());
        var offset = Number(xmlobject.find('offset').text());
        var selected = 0;

        for (t = 0; t <= Number($(xmlobject).find('total').text()); t += records) {
            var option = $('<option></option>', {
                'value': t,
                'text': String(page)
            });

            if (t == offset) {
                selected = t;
            }
            $('#paginator').append(option);
            page++;
        }

        if ($('#paginator option').length == 0) {
            $('#paginator').append($('<option></option>', {
                'value': 0,
                'html': '1'
            }));
        } else $('#paginator').val(selected);
    }

}

function loadlanguages() {

    if (typeof document.langlist !== 'undefined') return false;

    //  Load languages for future use

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: '../xmldata/languages.xml?rnd=' + $.now(),
        dataType: 'xml',

        complete: function(xhr) {
            if (xhr.status != 200) {
                preloader(false);
                errormsg(getlocalmsg(220)+' xmldata/languages.xml');
                return false;
            }
            document.langlist = jQuery.parseXML(xhr.responseText);
        }
    });
}

function getsetting(id) {

    preloader(true);
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: '../api/api.system.php',
        data: 'cmd=getsetting&' +
            'id=' + encodeURIComponent(id),
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
                return ($(xml).find('setting').text());
            }
        }
    });
}

function preloader(state, msg) {

    if (typeof state === 'undefined') {
        state = $('#preloader').length <= 0;
    }
    
    if (typeof document.language_xml !== 'undefined') {
      if (typeof msg === 'undefined') {
          msg = getlocalmsg(223);
      }
    } else {
      msg = '';
    }

    if (state && $('#preloader').length > 0) return false;
    if (!state && $('#preloader').length <= 0) return false;

    if (state) {
        $(document.body).prepend($('<div></div>', {
            'id': 'preloader'
        }));
        $('#preloader').append($('<div></div>', {
            'id': 'preloader_content'
        }));

        $('#preloader_content').append($('<img />', { 'id': 'preloader_gif', 'src': '../pic/ajax-loader.gif' }));

        $('#preloader_content').append($('<br />'));
        $('#preloader_content').append(msg);
    } else {
        $('#preloader').remove();
    }
}

function localize(html) {

    html.find('[pgcms]').each(function() {
        var id = $(this).attr('pgcms');
        var text = $.trim($(document.localize_xml).find('texts > text[id=' + id + ']').find('text').text());
        if (text == '') text = 'MISSING: '+id;

        if (typeof $(this).val() !== typeof undefined && $(this)[0].nodeName.toLowerCase() != 'option') $(this).val(text);
        if (typeof $(this).html() !== typeof undefined) $(this).html(text);
        if (typeof $(this).attr('alt') !== typeof undefined) $(this).attr('alt', text);
        if (typeof $(this).attr('content') !== typeof undefined) $(this).attr('content', text);
        if (typeof $(this).attr('title') !== typeof undefined) $(this).attr('title', text);
    });
}

function getlocalmsg(id) {
  var text = $.trim($(document.localize_xml).find('texts > text[id=' + id + ']').find('text').text());
  if (text == '') text = 'MISSING: '+id;
  return text;
}