(function($, window, document, jbx_variables, undefined) {
	if(typeof jbx_variables === 'undefined'){
		return;
	}
	var markup = $('<form id="jbx_wrapper" action="index.php" method="post"><h2>' + jbx_variables.strings.upload_multiple + '</h2><p>' + jbx_variables.strings.cat_help + '</p><label>' + jbx_variables.strings.cat_title + ' <input type="text" id="jbx_category" name="jbx_category" /></label><div id="jbx_upload_wrapper"><input type="file" name="file_upload" id="jbx_upload" /></div><input name="event" value="'+ jbx_variables.slug +'" type="hidden" /><input name="step" value="import" type="hidden" /><button type="button" id="jbx_submit" class="publish">'+ jbx_variables.strings.upload_button +'</button><h2>' + jbx_variables.strings.upload_single + '</h2></form>');
	
	$(function() {
		var target = $('.upload-form');
		if(target.length === 0){
			return;
		}

		// insert markup
		markup.insertBefore(target);

		// init uploadify
		$('#jbx_upload').uploadify({
			'swf'		: jbx_variables.paths.swf,
			'uploader'	: jbx_variables.paths.upload,
			'auto'		: false,
			'buttonText': jbx_variables.strings.browse,
			'buttonClass': 'navlink'
		});
	});
})(jQuery, window, document, jbx_variables);