(function($, window, document, jbx_variables, undefined) {
	if(typeof jbx_variables === 'undefined'){
		return;
	}
	var markup = $('<div class="jbx_wrapper"><h2>' + jbx_variables.strings.upload_multiple + '</h2><p>' + jbx_variables.strings.cat_help + '</p><label>' + jbx_variables.strings.cat_title + ' <input type="text" class="jbx_category" name="jbx_category" /></label><div class="jbx_upload_wrapper"><input type="file" name="file_upload" class="jbx_upload" /></div><button type="button" class="jbx_submit publish">'+ jbx_variables.strings.upload_button +'</button><h2>' + jbx_variables.strings.upload_single + '</h2></div>');
	
	var initUploadify = function(){
		var $target = $(this);
		var $el = markup.clone();

		// insert markup
		$el.insertBefore($target);

		var $submit = $el.find('.jbx_submit');
		var $upload = $el.find('.jbx_upload').attr('id', 'jbx_upload_' + (Math.floor(Math.random() * 999999)));

		// init uploadify
		$upload.uploadify({
			'swf'		: jbx_variables.paths.swf,
			'uploader'	: jbx_variables.paths.upload,
			'auto'		: false,
			'buttonText': jbx_variables.strings.browse,
			'fileSizeLimit': jbx_variables.fileSizeLimit,
			'fileTypeDesc': jbx_variables.strings.images,
			'fileTypeExts': '*.gif; *.jpg; *.png',
			'buttonClass': 'navlink',
			'onSelect' : function(file){
				$submit.show();
			}
		});

		// start upload
		$submit.on('click', function(e){
			e.preventDefault();
			$upload.uploadify('upload');
		});
	};

	// on domReady
	$(function() {
		$(jbx_variables.targets).each(initUploadify);
	});
})(jQuery, window, document, jbx_variables);