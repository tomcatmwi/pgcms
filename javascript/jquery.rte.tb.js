/*
 * Lightweight RTE - jQuery Plugin, v1.2
 * Basic Toolbars
 * Copyright (c) 2009 Andrey Gayvoronsky - http://www.gayvoronsky.com
 * Heavily modified by pixeldog studios in 2015
 * WARNING: tigers around 
*/

var rte_tag		= '-rte-tmp-tag-';

var	rte_toolbar = {
	s1				: {separator: true},
	bold			: {command: 'bold', tags:['b', 'strong']},
	italic			: {command: 'italic', tags:['i', 'em']},
	strikeThrough	: {command: 'strikethrough', tags: ['s', 'strike'] },
	underline		: {command: 'underline', tags: ['u']},
	s2				: {separator: true },
	justifyLeft   	: {command: 'justifyleft'},
	justifyCenter	: {command: 'justifycenter'},
	justifyRight	: {command: 'justifyright'},
	justifyFull		: {command: 'justifyfull'},
	s3				: {separator : true},
	indent			: {command: 'indent'},
	outdent			: {command: 'outdent'},
	s4				: {separator : true},
	subscript		: {command: 'subscript', tags: ['sub']},
	superscript		: {command: 'superscript', tags: ['sup']},
	s5				: {separator : true },
	orderedList		: {command: 'insertorderedlist', tags: ['ol'] },
	unorderedList	: {command: 'insertunorderedlist', tags: ['ul'] },
	s6				: {separator : true },
	block			: {command: 'formatblock', select: '\
<select>\
	<option value="">- Szövegelemek -</option>\
	<option value="<p>">Paragraph</option>\
	<option value="<h1>">Header 1</option>\
	<option value="<h2>">Header 2</options>\
	<option value="<h3>">Header 3</option>\
	<option value="<h4>">Header 4</options>\
	<option value="<h5>">Header 5</option>\
	<option value="<h6>">Header 6</options>\
</select>\
	', tag_cmp: lwrte_block_compare, tags: ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']},
	font			: {command: 'fontname', select: '\
<select>\
	<option value="">- Betűtípus -</option>\
	<option value="arial">Arial</option>\
	<option value="comic sans ms">Comic Sans</option>\
	<option value="courier new">Courier New</options>\
	<option value="georgia">Georgia</option>\
	<option value="helvetica">Helvetica</options>\
	<option value="impact">Impact</option>\
	<option value="times new roman">Times</options>\
	<option value="trebuchet ms">Trebuchet</options>\
	<option value="verdana">Verdana</options>\
</select>\
	', tags: ['font']},
	size			: {command: 'fontsize', select: '\
<select>\
	<option value="">- Betűméret -</option>\
	<option value="1">1 (8pt)</option>\
	<option value="2">2 (10pt)</option>\
	<option value="3">3 (12pt)</options>\
	<option value="4">4 (14pt)</option>\
	<option value="5">5 (16pt)</options>\
	<option value="6">6 (18pt)</option>\
	<option value="7">7 (20pt)</options>\
</select>\
	', tags: ['font']},
	color			: {exec: lwrte_color},
	image			: {exec: lwrte_image, tags: ['img'] },
	youtube		: {exec: lwrte_youtube },
	directpic	: {exec: lwrte_directpic },
	filestorage	: {exec: lwrte_filestorage },
	link			: {exec: lwrte_link, tags: ['a'] },
	unlink			: {command: 'unlink'},
	s8				: {separator : true },
	removeFormat	: {exec: lwrte_unformat},
	word			: {exec: lwrte_cleanup_word},
	clear			: {exec: lwrte_clear}
};

var html_toolbar = {
	s1				: {separator: true},
	word			: {exec: lwrte_cleanup_word},
	clear			: {exec: lwrte_clear}
};

/*** tag compare callbacks ***/
function lwrte_block_compare(node, tag) {
	tag = tag.replace(/<([^>]*)>/, '$1');
	return (tag.toLowerCase() == node.nodeName.toLowerCase());
}

function lwrte_color(){

    if (this.get_selected_text().length <= 0) {
      errormsg('Nincs kiválasztva szöveg.');
      return false;
    }
    
    var editor = el[this.arrayIndex][this.id];

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/rte-color.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('Nem tölthető be: forms/rte-color.html');
                return false;
            }
            showdialog(createdialog('Szín kiválasztása', xhr.responseText, [['OK', function() { lwrte_color_perform(editor) }], ['Mégsem', null]]));
        }
    });
}

function lwrte_color_perform(editor) {
  if (rgb2hex($('#palette_rgb').val()) == '') { errormsg('Érvénytelen RGB érték.'); return false; }
  if (hex2rgb($('#palette_hex').val()) == '') { errormsg('Érvénytelen hexa érték.'); return false; }
  
	editor.editor_cmd('foreColor', $('#palette_hex').val());
	hidelastdialog();
}

function lwrte_image() {
  var editor = el[this.arrayIndex][this.id];
  imagegallery(editor);
}

function lwrte_filestorage() {
  var editor = el[this.arrayIndex][this.id];
  filestorage(editor);
}

function lwrte_unformat() {
	this.editor_cmd('removeFormat');
	this.editor_cmd('unlink');
}

function lwrte_clear() {
  var id = this.id;
  showdialog(createdialog('Szöveg törlése', 'Törlöd a dokumentum teljes tartalmát?', [['Igen', function() { editor.set_content(''); hidelastdialog(); }], ['Nem', null]]));
}

function lwrte_cleanup_word() {
	this.set_content(cleanup_word(this.get_content(), true, true, true)); 
	
	function cleanup_word(s, bIgnoreFont, bRemoveStyles, bCleanWordKeepsStructure) {
		s = s.replace(/<o:p>\s*<\/o:p>/g, '') ;
		s = s.replace(/<o:p>[\s\S]*?<\/o:p>/g, '&nbsp;') ;

		// Remove mso-xxx styles.
		s = s.replace( /\s*mso-[^:]+:[^;"]+;?/gi, '' ) ;

		// Remove margin styles.
		s = s.replace( /\s*MARGIN: 0cm 0cm 0pt\s*;/gi, '' ) ;
		s = s.replace( /\s*MARGIN: 0cm 0cm 0pt\s*"/gi, "\"" ) ;

		s = s.replace( /\s*TEXT-INDENT: 0cm\s*;/gi, '' ) ;
		s = s.replace( /\s*TEXT-INDENT: 0cm\s*"/gi, "\"" ) ;

		s = s.replace( /\s*TEXT-ALIGN: [^\s;]+;?"/gi, "\"" ) ;

		s = s.replace( /\s*PAGE-BREAK-BEFORE: [^\s;]+;?"/gi, "\"" ) ;

		s = s.replace( /\s*FONT-VARIANT: [^\s;]+;?"/gi, "\"" ) ;

		s = s.replace( /\s*tab-stops:[^;"]*;?/gi, '' ) ;
		s = s.replace( /\s*tab-stops:[^"]*/gi, '' ) ;

		// Remove FONT face attributes.
		if (bIgnoreFont) {
			s = s.replace( /\s*face="[^"]*"/gi, '' ) ;
			s = s.replace( /\s*face=[^ >]*/gi, '' ) ;

			s = s.replace( /\s*FONT-FAMILY:[^;"]*;?/gi, '' ) ;
		}

		// Remove Class attributes
		s = s.replace(/<(\w[^>]*) class=([^ |>]*)([^>]*)/gi, "<$1$3") ;

		// Remove styles.
		if (bRemoveStyles)
			s = s.replace( /<(\w[^>]*) style="([^\"]*)"([^>]*)/gi, "<$1$3" ) ;

		// Remove style, meta and link tags
		s = s.replace( /<STYLE[^>]*>[\s\S]*?<\/STYLE[^>]*>/gi, '' ) ;
		s = s.replace( /<(?:META|LINK)[^>]*>\s*/gi, '' ) ;

		// Remove empty styles.
		s =  s.replace( /\s*style="\s*"/gi, '' ) ;

		s = s.replace( /<SPAN\s*[^>]*>\s*&nbsp;\s*<\/SPAN>/gi, '&nbsp;' ) ;

		s = s.replace( /<SPAN\s*[^>]*><\/SPAN>/gi, '' ) ;

		// Remove Lang attributes
		s = s.replace(/<(\w[^>]*) lang=([^ |>]*)([^>]*)/gi, "<$1$3") ;

		s = s.replace( /<SPAN\s*>([\s\S]*?)<\/SPAN>/gi, '$1' ) ;

		s = s.replace( /<FONT\s*>([\s\S]*?)<\/FONT>/gi, '$1' ) ;

		// Remove XML elements and declarations
		s = s.replace(/<\\?\?xml[^>]*>/gi, '' ) ;

		// Remove w: tags with contents.
		s = s.replace( /<w:[^>]*>[\s\S]*?<\/w:[^>]*>/gi, '' ) ;

		// Remove Tags with XML namespace declarations: <o:p><\/o:p>
		s = s.replace(/<\/?\w+:[^>]*>/gi, '' ) ;

		// Remove comments [SF BUG-1481861].
		s = s.replace(/<\!--[\s\S]*?-->/g, '' ) ;

		s = s.replace( /<(U|I|STRIKE)>&nbsp;<\/\1>/g, '&nbsp;' ) ;

		s = s.replace( /<H\d>\s*<\/H\d>/gi, '' ) ;

		// Remove "display:none" tags.
		s = s.replace( /<(\w+)[^>]*\sstyle="[^"]*DISPLAY\s?:\s?none[\s\S]*?<\/\1>/ig, '' ) ;

		// Remove language tags
		s = s.replace( /<(\w[^>]*) language=([^ |>]*)([^>]*)/gi, "<$1$3") ;

		// Remove onmouseover and onmouseout events (from MS Word comments effect)
		s = s.replace( /<(\w[^>]*) onmouseover="([^\"]*)"([^>]*)/gi, "<$1$3") ;
		s = s.replace( /<(\w[^>]*) onmouseout="([^\"]*)"([^>]*)/gi, "<$1$3") ;

		if (bCleanWordKeepsStructure) {
			// The original <Hn> tag send from Word is something like this: <Hn style="margin-top:0px;margin-bottom:0px">
			s = s.replace( /<H(\d)([^>]*)>/gi, '<h$1>' ) ;

			// Word likes to insert extra <font> tags, when using MSIE. (Wierd).
			s = s.replace( /<(H\d)><FONT[^>]*>([\s\S]*?)<\/FONT><\/\1>/gi, '<$1>$2<\/$1>' );
			s = s.replace( /<(H\d)><EM>([\s\S]*?)<\/EM><\/\1>/gi, '<$1>$2<\/$1>' );
		} else {
			s = s.replace( /<H1([^>]*)>/gi, '<div$1><b><font size="6">' ) ;
			s = s.replace( /<H2([^>]*)>/gi, '<div$1><b><font size="5">' ) ;
			s = s.replace( /<H3([^>]*)>/gi, '<div$1><b><font size="4">' ) ;
			s = s.replace( /<H4([^>]*)>/gi, '<div$1><b><font size="3">' ) ;
			s = s.replace( /<H5([^>]*)>/gi, '<div$1><b><font size="2">' ) ;
			s = s.replace( /<H6([^>]*)>/gi, '<div$1><b><font size="1">' ) ;

			s = s.replace( /<\/H\d>/gi, '<\/font><\/b><\/div>' ) ;

			// Transform <P> to <DIV>
			var re = new RegExp( '(<P)([^>]*>[\\s\\S]*?)(<\/P>)', 'gi' ) ;	// Different because of a IE 5.0 error
			s = s.replace( re, '<div$2<\/div>' ) ;

			// Remove empty tags (three times, just to be sure).
			// This also removes any empty anchor
			s = s.replace( /<([^\s>]+)(\s[^>]*)?>\s*<\/\1>/g, '' ) ;
			s = s.replace( /<([^\s>]+)(\s[^>]*)?>\s*<\/\1>/g, '' ) ;
			s = s.replace( /<([^\s>]+)(\s[^>]*)?>\s*<\/\1>/g, '' ) ;
		}

		return s;
	}
}

function lwrte_link() {

    if (this.get_selected_text().length <= 0) {
      errormsg('Válaszd ki a linkké alakítani kívánt szöveget!');
      return false;
    }
    
    var editor = el[this.arrayIndex][this.id];

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/rte-link.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('Nem tölthető be: forms/rte-link.html');
                return false;
            }
            showdialog(createdialog('Link', xhr.responseText, [['Beszúrás', function() { lwrte_link_perform(editor) }], ['Mégsem', null]]));
        }
    });
}

function lwrte_link_perform(editor) {
    editor.editor_cmd('unlink');
    if ($('#link_href').length <= 0) { hidelastdialog(); return false; }
    
    var link = $('<a></a>', { 'href': $('#link_href').val(), 'target': $('#link_target').val() });
		link.html(editor.get_selected_html());
    editor.selection_replace_with(link[0].outerHTML);
    hidelastdialog();
}

function lwrte_youtube() {

    var editor = el[this.arrayIndex][this.id];
    
    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/rte-youtube.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('Nem tölthető be: forms/rte-youtube.html');
                return false;
            }
            popup_id = createdialog('YouTube videó beszúrása', xhr.responseText, [['Beszúrás', function() { lwrte_youtube_perform(editor) }], ['Mégsem', null]]);

            $.ajax({
              type:     'POST',
              cache:    'false',
              url:      '../api/api.system.php',
              data:     'cmd=getsetting&token=YOUTUBE_WIDTH,YOUTUBE_HEIGHT',
              dataType: 'xml',
              
              complete: function(xhr) {

                  if (xhr.responseText == null || xhr.responseText == '') return false;
                  var xml = $.parseXML(xhr.responseText);
                  
                  var error = 0;
                  $(xml).find('error').each(function(){
                    errormsg($(this).find('message').text());
                    error = $(this).find('id').text();
                  });
                  
                  if (error == 0) {
                      $(xml).find('setting').each(function() {
                        if ($(this).find('id').text() == 'YOUTUBE_WIDTH') $('#youtube_width').val($(this).find('value').text()); 
                        if ($(this).find('id').text() == 'YOUTUBE_HEIGHT') $('#youtube_height').val($(this).find('value').text()); 
                      });
                      showdialog(popup_id);
                  }
              }
            });
        }
    });

}

function lwrte_youtube_perform(editor) {
  var rx = /^.*(?:(?:youtu\.be\/|v\/|vi\/|u\/\w\/|embed\/)|(?:(?:watch)?\?v(?:i)?=|\&v(?:i)?=))([^#\&\?]*).*/;
  if ($('#youtube_url').val().match(rx) == null) { errormsg('Érvénytelen YouTube cím.'); return false; }
  var youtube_url = $('#youtube_url').val().match(rx)[1];

  if (isNaN($('#youtube_width').val()) || isNaN($('#youtube_height').val()) || 
      $('#youtube_width').val() < 160 || $('#youtube_width').val() > 1200 || 
      $('#youtube_height').val() < 100 || $('#youtube_height').val() > 1200) { errormsg('Helytelen méret.'); return false; }
  
  var placeholder = $('<img />', { 'class': 'article_youtube_placeholder',
                                    'src': '../pic/youtube.png',
                                    'style': 'min-width: '+$('#youtube_width').val()+'px; '+
                                             'min-height: '+$('#youtube_height').val()+'px; '+
                                             'max-width: '+$('#youtube_width').val()+'px; '+
                                             'max-height: '+$('#youtube_height').val()+'px; '+
                                             'width: '+$('#youtube_width').val()+'px; '+
                                             'height: '+$('#youtube_height').val()+'px; ',
                                    'youtube_url': 'http://youtube.com/v/'+youtube_url,
                                    'youtube_width': $('#youtube_width').val(),
                                    'youtube_height': $('#youtube_height').val(),
                                    'youtube_allowfullscreen': String($('#youtube_fullscreen').prop('checked'))
                                  });
  
  editor.selection_replace_with(placeholder[0].outerHTML);
  hidelastdialog();  
}

function lwrte_directpic() {

    var editor = el[this.arrayIndex][this.id];

    $.ajax({
        type: 'GET',
        cache: 'false',
        url: 'forms/rte-directpic.html',
        dataType: 'html',

        complete: function(xhr) {
            if (xhr.status != 200) {
                errormsg('Nem tölthető be: forms/rte-directpic.html');
                return false;
            }
            showdialog(createdialog('Kép beágyazása HTML kódba', xhr.responseText, [['Beágyazás', function() { lwrte_directpic_perform(editor) }], ['Mégsem', null]]));
        }
    });

}

function lwrte_directpic_perform(editor) {

    var img = $('#upload_preview').get(0);
    var canvas = document.createElement('canvas');
    var ctx = canvas.getContext('2d');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    ctx.drawImage(img, 0, 0);
    var dataURL = canvas.toDataURL('image/png');
    
    var img = $('<img />', { 'class': 'article_image', 'src': dataURL });
    editor.selection_replace_with(img[0].outerHTML);
    hidelastdialog();
}
