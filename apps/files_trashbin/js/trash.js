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
		FileActions.register('all', 'Undelete', OC.PERMISSION_READ, '', function(filename) {
			var tr=$('tr').filterAttr('data-file', filename);
			$.post(OC.filePath('files_trashbin','ajax','undelete.php'),
				{timestamp:tr.attr('data-timestamp'),filename:tr.attr('data-filename')},
				function(result){
					if (result.status == 'success') {
						var row = document.getElementById(result.data.filename+'.d'+result.data.timestamp);
						row.parentNode.removeChild(row);
					} else {
						OC.dialogs.alert(result.data.message, 'Error');
					}
				});
			
			});
		};

});