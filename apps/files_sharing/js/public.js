// Override download path to files_sharing/public.php
function fileDownloadPath(dir, file) {
	var url = $('#downloadURL').val();
	if (url.indexOf('&path=') != -1) {
		url += '/'+file;
	}
	return url;
}

var form_data;

$(document).ready(function() {

	if (typeof FileActions !== 'undefined') {
		var mimetype = $('#mimetype').val();
		// Show file preview if previewer is available, images are already handled by the template
		if (mimetype.substr(0, mimetype.indexOf('/')) != 'image') {
			// Trigger default action if not download TODO
			var action = FileActions.getDefault(mimetype, 'file', OC.PERMISSION_READ);
			if (typeof action !== 'undefined') {
				action($('#filename').val());
			}
		}
		FileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function(filename) {
			var tr = $('tr').filterAttr('data-file', filename)
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href');
			}
		});
		FileActions.register('file', 'Download', OC.PERMISSION_READ, '', function(filename) {
			var tr = $('tr').filterAttr('data-file', filename)
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href');
			}
		});
		FileActions.register('dir', 'Download', OC.PERMISSION_READ, '', function(filename) {
			var tr = $('tr').filterAttr('data-file', filename)
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href')+'&download';
			}
		});
	}

  // Add some form data to the upload handler
  file_upload_param.formData = {
    MAX_FILE_SIZE: $('#uploadMaxFilesize').val(),
    requesttoken: $('#publicUploadRequestToken').val(),
    dirToken: $('#dirToken').val(),
    appname: 'files_sharing',
    subdir: $('input#dir').val()
  };

	$(document).on('click', '#directLink', function() {
		$(this).focus();
		$(this).select();
	});

});
