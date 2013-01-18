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