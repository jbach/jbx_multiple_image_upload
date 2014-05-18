(function($, window, document, jbx_variables, undefined) {
	if(typeof jbx_variables === 'undefined'){
		return;
	}
	var $markup = $('<div class="jbx_wrapper"><h2>' + jbx_variables.strings.upload_multiple + '</h2><p>' + jbx_variables.strings.cat_help + '</p><label>' + jbx_variables.strings.cat_title + ' <select class="jbx_category" name="jbx_category"></select></label><div class="jbx_upload_wrapper"><input type="file" name="file_upload" class="jbx_upload" /></div><button type="button" class="jbx_submit publish">'+ jbx_variables.strings.upload_button +'</button><h2>' + jbx_variables.strings.upload_single + '</h2></div>');

	// add categories
	var cats = '<option value=""></option>';
	$.each(jbx_variables.categories, function(i, cat){
		cats += '<option value="'+ cat.name +'">'+ cat.title +'</option>';
	});
	$markup.find('.jbx_category').html(cats);
	
	var randomId = function(){
		return 'jbx_upload_' + (Math.floor(Math.random() * 999999));
	};

	var initUploadify = function(){
		var $target = $(this);
		var $el = $markup.clone();

		// insert markup
		$el.insertBefore($target);

		var $submit = $el.find('.jbx_submit');
		var $upload_wrapper = $el.find('.jbx_upload_wrapper');
		var $upload = $el.find('.jbx_upload').attr('id', randomId());
		var $cat = $el.find('.jbx_category');
		var errors = [];

		// init uploadify
		$upload.uploadify({
			'swf'		: jbx_variables.paths.swf,
			'uploader'	: jbx_variables.paths.upload,
			'auto'		: false,
			'formData'  : {
				'_txp_token': jbx_variables.token
			},
			'buttonText': jbx_variables.strings.browse,
			'fileSizeLimit': jbx_variables.fileSizeLimit,
			'fileTypeDesc': jbx_variables.strings.images,
			'fileTypeExts': '*.gif; *.jpg; *.png',
			'buttonClass': 'navlink',
			'removeCompleted': false,
			'onSelect' : function(file){
				$submit.show();
			},
			'onUploadError': function(file, errorCode, errorMsg, errorString){
				errors.push({
					file: file,
					msg: errorMsg,
				});
			},
			'onUploadSuccess': function(file, data){
				if(data !== '1'){
					// error
					$el.find('#'+file.id).addClass('uploadify-error');
					$el.find('#'+file.id).find('.uploadify-progress-bar').css('width','1px');
					$el.find('#'+file.id).find('.data').html(' - ' + data);
					errors.push({
						file: file,
						msg: data
					});
				}
			},
			'onQueueComplete': function(queue){
				if(errors.length > 0){
					$('<div class="jbx_error alert-block error">'+jbx_variables.strings.errors+'</div>').insertBefore($submit);
				}else{
					window.location.reload();
				}
			}
		});

		// start upload
		$submit.on('click', function(e){
			e.preventDefault();
			errors = [];
			$upload.uploadify('settings', 'formData', {'category': $cat.val()});
			$upload.uploadify('upload', '*');
			$submit.hide();
			$upload.uploadify('disable', true);
		});
	};

	// on domReady
	$(function() {
		$(jbx_variables.targets).each(initUploadify);
	});
})(jQuery, window, document, jbx_variables);