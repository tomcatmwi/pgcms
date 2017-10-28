//  CREATEDIALOG / SHOWDIALOG
//  Creates and shows a dialog window
//
//  title     Window title
//  text      Window contents (complex HTML is accepted)
//  buttons   Array of buttons

function createdialog(title, text, buttons) {

    if (typeof document.zindex == 'undefined' || isNaN(document.zindex) || document.zindex < 10000) document.zindex = 10000;

    document.zindex += 2;
    
    var id = Math.floor(Math.random() * 65535) + 1;
    var dialog = $('<div></div>', {
        'id': 'popup_' + id,
        'class': 'popup',
        'style': 'z-index: ' + String(document.zindex)
    });
    
    dialog.append($('<div></div>', {
        'id': 'popup_header_' + id,
        'class': 'popup_header'
    }));
    
    dialog.find('div:last').html(title);
    if (title.substr(0, 6) == 'pgcms=') { dialog.find('div:last').attr('pgcms', title.substr(6, title.length)); }

    var temp = $('<div></div>', {
        'id': 'popup_text_container_' + id,
        'class': 'popup_text_container'
    });
    temp.append($('<div></div>'), {
        'id': 'popup_text_' + id,
        'class': 'popup_text'
    });
    temp.find('div:last').attr('class', 'popup_text');
    temp.find('div:last').html(text);
    dialog.append(temp);

    dialog.append($('<div></div>', {
        'id': 'popup_footer_' + id,
        'class': 'popup_footer'
    }));

    for (t in buttons) {

        if (buttons[t][1] == null) {
            var thefunction = function() { hidelastdialog(); }
        } else {
            var thefunction = buttons[t][1];
        }
        
        var button = $('<input/>', {
                type: 'button',
                value: buttons[t][0],
                id: 'popup_' + id + '_button_' + String(t),
                click: thefunction
            })

        if (buttons[t][0].substr(0, 6) == 'pgcms=') {
          button.attr('value', buttons[t][0].substr(6, buttons[t][0].length));
          button.attr('pgcms', buttons[t][0].substr(6, buttons[t][0].length));
        } else {
          button.attr('value', buttons[t][0]);
        }
        
        dialog.find('div:last').append(button);
    }
    
    if (typeof localize === 'function') localize(dialog);

    $(document.body).prepend(dialog);
  
    if ($('#dialog_darken').css('visibility') != 'visible') {
      var darken = $('<div></div>');
      darken.attr('id', 'dialog_darken');
      darken.attr('class', 'dialog_darken');
      $(document.body).prepend(darken);
    }

    $('#dialog_darken').css('z-index', (document.zindex - 1));

    return id;
}

function showdialog(id) {
    $('#dialog_darken').css('visibility', 'visible');
    $('#popup_' + id).css('visibility', 'visible');
    $('#popup_' + id).find('input:visible:first').focus();
}

function hidedialog() {
    $('[id^=popup_]').remove();
    $('#dialog_darken').css('visibility', 'hidden');
    document.zindex = 10001;
}

function hidelastdialog() {
    var largest = 0;
    var largest_id = 0;

    $('[id^=popup_]').each(function() {
        var id = $(this).attr('id').substr(6, $(this).attr('id').length);
        if (!isNaN(id) && $(this).css('z-index') > largest) {
            largest = $(this).css('z-index');
            largest_id = $(this).attr('id').substr(6, $(this).attr('id').length);
        }
    });
    $('#popup_'+largest_id).remove();
    
    var popups = 0;
    $('[id^=popup_]').each(function() { popups++; });
    largest = 0;

    if (popups > 0) {
        $('[id^=popup_]').each(function() {
            var id = $(this).attr('id').substr(6, $(this).attr('id').length);
            if (!isNaN(id) && $(this).css('z-index') > largest) {
                largest = $(this).css('z-index');
            }
        });
        $('#dialog_darken').css('z-index', largest-2);
    } else {
        $('#dialog_darken').css('visibility', 'hidden');
    }
    
}

function disablelastdialog(state) {

    var largest = 0;
    var largest_id = 0;

    $('[id^=popup_]').each(function() {
        var id = $(this).attr('id').substr(6, $(this).attr('id').length);
        if (!isNaN(id) && $(this).css('z-index') > largest) {
            largest = $(this).css('z-index');
            largest_id = $(this).attr('id').substr(6, $(this).attr('id').length);
        }
    });

    $('#popup_' + largest_id + ' *').prop('disabled', state);
}
