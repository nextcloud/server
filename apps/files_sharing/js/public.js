// Override download path to files_sharing/public.php
function fileDownloadPath(dir, file) {
	return $('#downloadURL').val();
}

$(document).ready(function() {

	if (typeof FileActions !== 'undefined') {
		var mimetype = $('#mimetype').val();
		// Show file preview if previewer is available, images are already handled by the template
		if (mimetype.substr(0, mimetype.indexOf('/')) != 'image') {
			// Trigger default action if not download TODO
			var action = FileActions.getDefault(mimetype, 'file', FileActions.PERMISSION_READ);
			action($('#filename').val());
		}
	}

});