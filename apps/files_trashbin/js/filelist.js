/* global OC, t, FileList */
(function() {
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

	var oldSetCurrentDir = FileList._setCurrentDir;
	FileList._setCurrentDir = function(targetDir) {
		oldSetCurrentDir.apply(this, arguments);

		var baseDir = OC.basename(targetDir);
		if (baseDir !== '') {
			FileList.setPageTitle(FileList.getDeletedFileName(baseDir));
		}
	};

	var oldCreateRow = FileList._createRow;
	FileList._createRow = function() {
		// FIXME: MEGAHACK until we find a better solution
		var tr = oldCreateRow.apply(this, arguments);
		tr.find('td.filesize').remove();
		return tr;
	};

	FileList._onClickBreadCrumb = function(e) {
		var $el = $(e.target).closest('.crumb'),
			index = $el.index(),
			$targetDir = $el.data('dir');
		// first one is home, let the link makes it default action
		if (index !== 0) {
			e.preventDefault();
			FileList.changeDirectory($targetDir);
		}
	};

	var oldAdd = FileList.add;
	FileList.add = function(fileData, options) {
		options = options || {};
		var dir = FileList.getCurrentDirectory();
		var dirListing = dir !== '' && dir !== '/';
		// show deleted time as mtime
		if (fileData.mtime) {
			fileData.mtime = parseInt(fileData.mtime, 10);
		}
		if (!dirListing) {
			fileData.displayName = fileData.name;
			fileData.name = fileData.name + '.d' + Math.floor(fileData.mtime / 1000);
		}
		return oldAdd.call(this, fileData, options);
	};

	FileList.linkTo = function(dir){
		return OC.linkTo('files_trashbin', 'index.php')+"?dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
	};

	FileList.updateEmptyContent = function(){
		var $fileList = $('#fileList');
		var exists = $fileList.find('tr:first').exists();
		$('#emptycontent').toggleClass('hidden', exists);
		$('#filestable th').toggleClass('hidden', !exists);
	};
})();
