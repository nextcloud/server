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

	var oldRenderRow = FileList._renderRow;
	FileList._renderRow = function(fileData, options) {
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
		return oldRenderRow.call(this, fileData, options);
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

	var oldInit = FileList.initialize;
	FileList.initialize = function() {
		var result = oldInit.apply(this, arguments);
		$('.undelete').click('click', FileList._onClickRestoreSelected);
		return result;
	};

	FileList._removeCallback = function(result) {
		if (result.status !== 'success') {
			OC.dialogs.alert(result.data.message, t('files_trashbin', 'Error'));
		}

		var files = result.data.success;
		var $el;
		for (var i = 0; i < files.length; i++) {
			$el = FileList.remove(OC.basename(files[i].filename), {updateSummary: false});
			FileList.fileSummary.remove({type: $el.attr('data-type'), size: $el.attr('data-size')});
		}
		FileList.fileSummary.update();
		FileList.updateEmptyContent();
		enableActions();
	}

	FileList._onClickRestoreSelected = function(event) {
		event.preventDefault();
		var allFiles = $('#select_all').is(':checked');
		var files = [];
		var params = {};
		disableActions();
		if (allFiles) {
			FileList.showMask();
			params = {
				allfiles: true,
				dir: FileList.getCurrentDirectory()
			};
		}
		else {
			files = _.pluck(FileList.getSelectedFiles(), 'name');
			for (var i = 0; i < files.length; i++) {
				var deleteAction = FileList.findFileEl(files[i]).children("td.date").children(".action.delete");
				deleteAction.removeClass('delete-icon').addClass('progress-icon');
			}
			params = {
				files: JSON.stringify(files),
				dir: FileList.getCurrentDirectory()
			};
		}

		$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'),
			params,
			function(result) {
				if (allFiles) {
					if (result.status !== 'success') {
						OC.dialogs.alert(result.data.message, t('files_trashbin', 'Error'));
					}
					FileList.hideMask();
					// simply remove all files
					FileList.update('');
					enableActions();
				}
				else {
					FileList._removeCallback(result);
				}
			}
		);
	};

	FileList._onClickDeleteSelected = function(event) {
		event.preventDefault();
		var allFiles = $('#select_all').is(':checked');
		var files = [];
		var params = {};
		if (allFiles) {
			params = {
				allfiles: true,
				dir: FileList.getCurrentDirectory()
			};
		}
		else {
			files = _.pluck(FileList.getSelectedFiles(), 'name');
			params = {
				files: JSON.stringify(files),
				dir: FileList.getCurrentDirectory()
			};
		}

		disableActions();
		if (allFiles) {
			FileList.showMask();
		}
		else {
			for (var i = 0; i < files.length; i++) {
				var deleteAction = FileList.findFileEl(files[i]).children("td.date").children(".action.delete");
				deleteAction.removeClass('delete-icon').addClass('progress-icon');
			}
		}

		$.post(OC.filePath('files_trashbin', 'ajax', 'delete.php'),
				params,
				function(result) {
					if (allFiles) {
						if (result.status !== 'success') {
							OC.dialogs.alert(result.data.message, t('files_trashbin', 'Error'));
						}
						FileList.hideMask();
						// simply remove all files
						FileList.setFiles([]);
						enableActions();
					}
					else {
						FileList._removeCallback(result);
					}
				}
		);
	};

	var oldClickFile = FileList._onClickFile;
	FileList._onClickFile = function(event) {
		var mime = $(this).parent().parent().data('mime');
		if (mime !== 'httpd/unix-directory') {
			event.preventDefault();
		}
		return oldClickFile.apply(this, arguments);
	};

})();
