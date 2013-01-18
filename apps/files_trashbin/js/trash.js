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
			console.log("tr: " + tr.attr('data-timestamp') + " name: " + name);
			$.post(OC.filePath('files_trashbin','ajax','undelete.php'),
				{timestamp:tr.attr('data-timestamp'),filename:tr.attr('data-filename')},
				function(result){
					if (result.status == 'success') {
						return;
						var date=new Date();
						FileList.addFile(name,0,date,false,hidden);
						var tr=$('tr').filterAttr('data-file',name);
						tr.data('mime','text/plain').data('id',result.data.id);
						tr.attr('data-id', result.data.id);
						getMimeIcon('text/plain',function(path){
							tr.find('td.filename').attr('style','background-image:url('+path+')');
						});
					} else {
						OC.dialogs.alert(result.data.message, 'Error');
					}
				});
			
			});
		};

});