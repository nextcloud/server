// override reload with own ajax call
FileList.reload = function(){
	FileList.showMask();
	if (FileList._reloadCall){
		FileList._reloadCall.abort();
	}
	$.ajax({
		url: OC.filePath('files_trashbin','ajax','list.php'),
		data: {
			dir : $('#dir').val(),
			breadcrumb: true
		},
		error: function(result) {
			FileList.reloadCallback(result);
		},
		success: function(result) {
			FileList.reloadCallback(result);
		}
	});
}

FileList.linkTo = function(dir){
	return OC.linkTo('files_trashbin', 'index.php')+"?dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
}

FileList.updateEmptyContent = function(){
	var $fileList = $('#fileList');
	var exists = $fileList.find('tr:first').exists();
	$('#emptycontent').toggleClass('hidden', exists);
	$('#filestable th').toggleClass('hidden', !exists);
}
