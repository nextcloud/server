// Override download path to files_sharing/public.php
function fileDownloadPath(dir, file) {
	var url = $('#downloadURL').val();
	if (url.indexOf('&path=') != -1) {
		url += '/'+file;
	}
	return url;
}

$(document).ready(function() {

	if (typeof FileActions !== 'undefined') {
		var mimetype = $('#mimetype').val();
		// Show file preview if previewer is available, images are already handled by the template
		if (mimetype.substr(0, mimetype.indexOf('/')) != 'image' && $('.publicpreview').length === 0) {
			// Trigger default action if not download TODO
			var action = FileActions.getDefault(mimetype, 'file', OC.PERMISSION_READ);
			if (typeof action !== 'undefined') {
				action($('#filename').val());
			}
		}
		FileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function(filename) {
			var tr = FileList.findFileEl(filename);
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href');
			}
		});
		FileActions.register('file', 'Download', OC.PERMISSION_READ, function() {
			return OC.imagePath('core', 'actions/download');
		}, function(filename) {
			var tr = FileList.findFileEl(filename);
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href');
			}
		});
		FileActions.register('dir', 'Download', OC.PERMISSION_READ, function() {
			return OC.imagePath('core', 'actions/download');
		}, function(filename) {
			var tr = FileList.findFileEl(filename);
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href')+'&download';
			}
		});
	}

	var file_upload_start = $('#file_upload_start');
	file_upload_start.on('fileuploadadd', function(e, data) {
		// Add custom data to the upload handler
		data.formData = {
			requesttoken: $('#publicUploadRequestToken').val(),
			dirToken: $('#dirToken').val(),
			subdir: $('input#dir').val()
		};
	});

	$(document).on('click', '#directLink', function() {
		$(this).focus();
		$(this).select();
	});

});
