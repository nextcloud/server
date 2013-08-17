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

FileList.setCurrentDir = function(targetDir, changeUrl){
	$('#dir').val(targetDir);
	// Note: IE8 handling ignored for now
	if (window.history.pushState && changeUrl !== false){
		url = OC.linkTo('files_trashbin', 'index.php')+"?dir="+ encodeURIComponent(targetDir).replace(/%2F/g, '/'),
		window.history.pushState({dir: targetDir}, '', url);
	}
}
