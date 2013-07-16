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

	$('#data-upload-form').tipsy({gravity:'ne', fade:true});

	if (typeof FileActions !== 'undefined') {
		var mimetype = $('#mimetype').val();
		// Show file preview if previewer is available, images are already handled by the template
		if (mimetype.substr(0, mimetype.indexOf('/')) != 'image') {
			// Trigger default action if not download TODO
			var action = FileActions.getDefault(mimetype, 'file', OC.PERMISSION_READ);
			if (typeof action === 'undefined') {
				$('#noPreview').show();
				if (mimetype != 'httpd/unix-directory') {
					// NOTE: Remove when a better file previewer solution exists
					$('#content').remove();
					$('table').remove();
				}
			} else {
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

  // Add Uploadprogress Wrapper to controls bar
  $('#controls').append($('#additional_controls div#uploadprogresswrapper'));

  // Cancel upload trigger
  $('#cancel_upload_button').click(Files.cancelUploads);

});
