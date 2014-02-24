/* globals OC, FileList, t */
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
};

FileList.appName = t('files_trashbin', 'Deleted files');

FileList._deletedRegExp = new RegExp(/^(.+)\.d[0-9]+$/);

/**
 * Convert a file name in the format filename.d12345 to the real file name.
 * This will use basename.
 * The name will not be changed if it has no ".d12345" suffix.
 * @param name file name
 * @return converted file name
 */
FileList.getDeletedFileName = function(name) {
	name = OC.basename(name);
	var match = FileList._deletedRegExp.exec(name);
	if (match && match.length > 1) {
		name = match[1];
	}
	return name;
};
var oldSetCurrentDir = FileList.setCurrentDir;
FileList.setCurrentDir = function(targetDir) {
	oldSetCurrentDir.apply(this, arguments);

	var baseDir = OC.basename(targetDir);
	if (baseDir !== '') {
		FileList.setPageTitle(FileList.getDeletedFileName(baseDir));
	}
};

FileList.linkTo = function(dir){
	return OC.linkTo('files_trashbin', 'index.php')+"?dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
}

FileList.updateEmptyContent = function(){
	var $fileList = $('#fileList');
	var exists = $fileList.find('tr:first').exists();
	$('#emptycontent').toggleClass('hidden', exists);
	$('#filestable th').toggleClass('hidden', !exists);
}
