//  pgCMS frontend routines

function logout() {

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.userdata.php',
        data: 'cmd=logout',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') {
            return false;
            }
            var xml = $.parseXML(xhr.responseText);

            var error = 0;
            $(xml).find('error').each(function() {
                errormsg($(this).find('message').text());
                error = $(this).find('id').text();
            });

            if (error == 0) {
                document.location.href = 'index.php';
            }
        }
    });
}


function loadarticles(html, callback) {

  var tokens = '';
  $(html).find('[pgcms_article]').each(function() {
    if (tokens != '') tokens += '###FELLATIO###';
    tokens += $(this).attr('pgcms_article');
  });
  
  if (tokens == '') {
    if (typeof callback === 'function') { callback.call(); }
    return false;
  }

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.texts.php',
        data: 'cmd=fellatio&'+
              'tokens='+encodeURIComponent(tokens),
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

                xml = solvearticlechunks(xml);

                $(xml).find('pgcms > article').each(function() {
                  var text = $.trim($(this).find('text').text());
                  $(html).find('[pgcms_article="'+$(this).find('token').text()+'"]').each(function() {
                  
                    if (typeof $(this).html() !== typeof undefined) {
                        $(this).html(text);                    
                    
                        //  replace YouTube placeholders with actual YouTube videos
                        
                        $(this).find('.article_youtube_placeholder').each(function() {
                          var link = $('<iframe></iframe>', { 'src': $(this).attr('youtube_url'), 'frameborder': '0' });
                          $(link).attr('width', $(this).attr('youtube_width'));
                          $(link).attr('height', $(this).attr('youtube_height'));
                          if ($(this).attr('youtube_allowfullscreen') == 'true') link.attr('allowfullscreen', 'true');
                          $(this).before(link[0].outerHTML);
                          $(this).remove();
                        });

                        if (typeof callback === 'function') { callback.call(); }

                    }
                  });
               });
            }
       }
   });
}

function loadtexts(html, callback) {

  var tokens = '';
  $(html).find('[pgcms]').each(function() {
    if (tokens != '') tokens += ',';
    tokens += $(this).attr('pgcms');
  });

  if (tokens == '') {
    if (typeof callback === 'function') { callback.call(); }
    return false;
  }
  
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.texts.php',
        data: 'cmd=cunnilingus&'+
              'tokens='+encodeURIComponent(tokens),
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
                        if (sendback != '') sendback += '###ANILINGUS###';
                        var element = $(html).find('[pgcms="'+$(this).find('token').text()+'"]');
                        if (typeof element.val() !== typeof undefined) var text = element.val();
                        if (typeof element.html() !== typeof undefined) var text = element.html();
                        if (typeof element.attr('alt') !== typeof undefined) var text = element.attr('alt');
                        if (typeof element.attr('content') !== typeof undefined) var text = element.attr('content');
                        sendback += $(this).find('token').text()+'|'+ text;
                      }
                    });

                    $.ajax({
                        type: 'POST',
                        cache: 'false',
                        url: 'api/api.texts.php',
                        data: 'cmd=anilingus&'+
                              'sendback='+encodeURIComponent(sendback),
                        dataType: 'xml'
                    });
                }

//  Put existing tokens into place
                
                $(xml).find('pgcms > text').each(function() {
                  var text = $.trim($(this).find('text').text());
                  $(html).find('[pgcms="'+$(this).find('token').text()+'"]').each(function() {
                    if (typeof $(this).val() !== typeof undefined) $(this).val(text);
                    if (typeof $(this).html() !== typeof undefined) $(this).html(text);
                    if (typeof $(this).attr('alt') !== typeof undefined) $(this).attr('alt', text);
                    if (typeof $(this).attr('content') !== typeof undefined) $(this).attr('content', text);
                  });
               });
               
               if (typeof callback === 'function') { callback.call(); }
               
            }
        }
    });
}

function changelanguage(id) {

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.system.php',
        data: 'cmd=setlanguage&id=' + encodeURIComponent(id),
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
                $('#flag_current').attr('src', 'pic/flags/' + $(xml).find('language_flag').text());
                document.current_language = id;
                loadtexts($(document), loadarticles($(document)));
                if (typeof pageload == 'function') pageload();
            }
        }
    });
}

function filllanguageselector() {

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.system.php',
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
                $('#flag_current').attr('src', 'pic/flags/' + $(xml).find('language_flag').text());
                document.current_language = $(xml).find('user_language').text();
                loaduserdata($(xml).find('user_id').text(), $(xml).find('user_name').text());

                $.ajax({
                    type: 'GET',
                    url: 'xmldata/languages.xml?rnd='+$.now(),
                    dataType: 'xml',

                    complete: function(xhr) {

                        if (xhr.responseText == null || xhr.responseText == '') {
                            errormsg('Unable to load languages.xml');
                            return false;
                        }

                        var xml = $.parseXML(xhr.responseText);
                        $('#language_selector').empty();

                        $(xml).find('language').each(function() {
                            if ($(this).find('selectable').text() == 1) {

                                $('#language_selector').append($('<option></option>', { 
                                    'text': $(this).find('nameeng').text() + ' (' + $(this).find('name').text() + ')',
                                    'value': $(this).attr('id')
                                }));

                            }
                        });
                        
                        loadtexts($(document), loadarticles($(document)));
                    }
                });

            }
        }
    });
}

function getarticle(id, callback) {

    if (!isNaN(id)) {
      var data = 'cmd=getarticle&id='+id;
    } else {
      var data = 'cmd=getarticle&token='+id;
    }
    
    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.texts.php',
        data: data,
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

              xml = solvearticlechunks(xml);
              
              var text = $(xml).find('text').text();
              text = text.replace(/..\/userdata\//g, 'userdata/');
              $(xml).find('text').text(text);
              if (typeof callback == 'function') callback.call(this, xml);
            }
        }
    });
}

function newcaptcha(image, callback) {

    $.ajax({
        type: 'POST',
        cache: 'false',
        url: 'api/api.system.php',
        data: 'cmd=getcaptcha',
        dataType: 'xml',

        complete: function(xhr) {

            if (xhr.responseText == null || xhr.responseText == '') return false;
            var xml = $.parseXML(xhr.responseText);
            $('#' + image).attr('src', $.trim($(xml).find('captcha').text()));
            if (typeof callback === 'function') {
                callback.call();
            }
        }
    });
}
