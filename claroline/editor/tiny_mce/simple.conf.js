tinyMCE.init({

	//-- general
    mode : "textareas",
    editor_selector : "simpleMCE",
    // plugins must be the same as in tinyMCE_GZ.init
    plugins : "paste,safari,texformula,spoiler",
    theme : "advanced",
    browsers : "safari,msie,gecko,opera",
	directionality : text_dir,
	gecko_spellcheck : true,
	
	//-- url
    convert_urls : false,
    relative_urls : false,
    
    //-- advanced theme
    theme_advanced_buttons1 : "cut,copy,paste,pasteword,bold,italic,underline,strikethrough,separator,bullist,numlist,undo,redo,link,unlink,texformula,spoiler",
    theme_advanced_buttons2 : "",
    theme_advanced_buttons3 : "",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_path : true,
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    theme_advanced_resize_horizontal : false,
    theme_advanced_resizing_use_cookie : false,
    
    //-- cleanup/output
    forced_root_block : false,
    apply_source_formatting : true,
	cleanup_on_startup : true,
    entity_encoding : "raw",
    extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
    
    // setup
    setup : function(ed) {
        // Change Tex code to Tex img
        ed.onBeforeSetContent.add(function(ed, o) {
            o.content = o.content.replace(/\[tex\](.+?)\[\/tex\]/gi, '<img src="'+ mimeTexURL +'?$1" border="0" align="absmiddle" class="latexFormula" alt="$1" />');
        });
        // Change Tex img to Tex code
        ed.onGetContent.add(function(ed, o) {
            var content = ed.dom.getRoot();
            var texFormula = $(content).find('img.latexFormula');
            $.each(texFormula, function() {
                var src = $(texFormula).attr('src');
                var src = src.replace(/(.+?)\?(.+?)/gi, '$2');
                var latexTag = '[tex]' + src + '[/tex]';
                $(this).replaceWith(latexTag);
            });
            $(content).find('img.latexFormula').replaceWith(texFormula);
        });
        // Change Spoiler class to Spoiler code
        ed.onGetContent.add(function(ed, o) {
            var content = ed.dom.getRoot();
            var spoilers = $(content).find('div.spoiler');
            $.each(spoilers, function() {
                var title = $(this).find('a').text();
                var spoilerContent = $(this).find('div.spoilerContent').html();
                var spoilerTags = '<p>[spoiler /' + title + '/]</p>' + spoilerContent + '<p>[/spoiler]</p>';
                $(this).replaceWith(spoilerTags);
            });
            $(content).find('div.spoiler').replaceWith(spoilers);
            
        });
    }
});
