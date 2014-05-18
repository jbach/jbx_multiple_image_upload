(function($, window, document, jbx_variables, undefined) {
	if(typeof jbx_variables === 'undefined'){
		return;
	}
	var markup = $('<div id="jbx_wrapper"><h2>' + jbx_variables.strings.upload_multiple + '</h2><p>' + jbx_variables.strings.cat_help + '</p><label>' + jbx_variables.strings.cat_title + ' <input type="text" id="jbx_category" name="jbx_category" /></label><div id="jbx_upload_wrapper"><input type="file" name="file_upload" id="jbx_upload" /></div><button type="button" id="jbx_submit" class="publish">'+ jbx_variables.strings.upload_button +'</button><h2>' + jbx_variables.strings.upload_single + '</h2></div>');
	
	$(function() {
		var $target = $('.upload-form');

		if($target.length === 0){
			return;
		}

		// insert markup
		markup.insertBefore($target);
		var $submit = $('#jbx_submit');
		var $upload = $('#jbx_upload');

		// init uploadify
		$upload.uploadify({
			'swf'		: jbx_variables.paths.swf,
			'uploader'	: jbx_variables.paths.upload,
			'auto'		: false,
			'buttonText': jbx_variables.strings.browse,
			'fileSizeLimit': jbx_variables.fileSizeLimit,
			'fileTypeDesc': jbx_variables.strings.images,
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
	});
})(jQuery, window, document, jbx_variables);