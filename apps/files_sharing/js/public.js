// Override download path to files_sharing/public.php
function fileDownloadPath(dir, file) {
	var url = $('#downloadURL').val();
	if (url.indexOf('&path=') != -1) {
		url += '/'+file;
	}
	return url;
}

$(document).ready(function() {

	$('#data-upload-form').tipsy({gravity:'ne', fade:true});

	if (typeof FileActions !== 'undefined') {
		var mimetype = $('#mimetype').val();
		// Show file preview if previewer is available, images are already handled by the template
		if (mimetype.substr(0, mimetype.indexOf('/')) != 'image' && $('.publicpreview').length === 0) {
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
			var tr = $('tr').filterAttr('data-file', filename);
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href');
			}
		});
		FileActions.register('file', 'Download', OC.PERMISSION_READ, '', function(filename) {
			var tr = $('tr').filterAttr('data-file', filename);
			if (tr.length > 0) {
				window.location = $(tr).find('a.name').attr('href');
			}
		});
		FileActions.register('dir', 'Download', OC.PERMISSION_READ, '', function(filename) {
			var tr = $('tr').filterAttr('data-file', filename);
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

	// Add Uploadprogress Wrapper to controls bar
	$('#controls').append($('#additional_controls div#uploadprogresswrapper'));

	// Cancel upload trigger
	$('#cancel_upload_button').click(function() {
		OC.Upload.cancelUploads();
		procesSelection();
	});

	$('#directLink').focus();

});
