Files={
	// file space size sync
	_updateStorageStatistics: function() {
		Files._updateStorageStatisticsTimeout = null;
		var currentDir = FileList.getCurrentDirectory(),
			state = Files.updateStorageStatistics;
		if (state.dir){
			if (state.dir === currentDir) {
				return;
			}
			// cancel previous call, as it was for another dir
			state.call.abort();
		}
		state.dir = currentDir;
		state.call = $.getJSON(OC.filePath('files','ajax','getstoragestats.php') + '?dir=' + encodeURIComponent(currentDir),function(response) {
			state.dir = null;
			state.call = null;
			Files.updateMaxUploadFilesize(response);
		});
	},
	updateStorageStatistics: function(force) {
		if (!OC.currentUser) {
			return;
		}

		// debounce to prevent calling too often
		if (Files._updateStorageStatisticsTimeout) {
			clearTimeout(Files._updateStorageStatisticsTimeout);
		}
		if (force) {
			Files._updateStorageStatistics();
		}
		else {
			Files._updateStorageStatisticsTimeout = setTimeout(Files._updateStorageStatistics, 250);
		}
	},

	updateMaxUploadFilesize:function(response) {
		if (response === undefined) {
			return;
		}
		if (response.data !== undefined && response.data.uploadMaxFilesize !== undefined) {
			$('#max_upload').val(response.data.uploadMaxFilesize);
			$('#upload.button').attr('original-title', response.data.maxHumanFilesize);
			$('#usedSpacePercent').val(response.data.usedSpacePercent);
			Files.displayStorageWarnings();
		}
		if (response[0] === undefined) {
			return;
		}
		if (response[0].uploadMaxFilesize !== undefined) {
			$('#max_upload').val(response[0].uploadMaxFilesize);
			$('#upload.button').attr('original-title', response[0].maxHumanFilesize);
			$('#usedSpacePercent').val(response[0].usedSpacePercent);
			Files.displayStorageWarnings();
		}

	},

	/**
	 * Fix path name by removing double slash at the beginning, if any
	 */
	fixPath: function(fileName) {
		if (fileName.substr(0, 2) == '//') {
			return fileName.substr(1);
		}
		return fileName;
	},

	isFileNameValid:function (name) {
		if (name === '.') {
			throw t('files', '\'.\' is an invalid file name.');
		} else if (name.length === 0) {
			throw t('files', 'File name cannot be empty.');
		}

		// check for invalid characters
		var invalid_characters = ['\\', '/', '<', '>', ':', '"', '|', '?', '*'];
		for (var i = 0; i < invalid_characters.length; i++) {
			if (name.indexOf(invalid_characters[i]) !== -1) {
				throw t('files', "Invalid name, '\\', '/', '<', '>', ':', '\"', '|', '?' and '*' are not allowed.");
			}
		}
		return true;
	},
	displayStorageWarnings: function() {
		if (!OC.Notification.isHidden()) {
			return;
		}

		var usedSpacePercent = $('#usedSpacePercent').val();
		if (usedSpacePercent > 98) {
			OC.Notification.show(t('files', 'Your storage is full, files can not be updated or synced anymore!'));
			return;
		}
		if (usedSpacePercent > 90) {
			OC.Notification.show(t('files', 'Your storage is almost full ({usedSpacePercent}%)', {usedSpacePercent: usedSpacePercent}));
		}
	},

	displayEncryptionWarning: function() {

		if (!OC.Notification.isHidden()) {
			return;
		}

		var encryptedFiles = $('#encryptedFiles').val();
		var initStatus = $('#encryptionInitStatus').val();
		if (initStatus === '0') { // enc not initialized, but should be
			OC.Notification.show(t('files_encryption', 'Encryption App is enabled but your keys are not initialized, please log-out and log-in again'));
			return;
		}
		if (initStatus === '1') { // encryption tried to init but failed
			OC.Notification.showHtml(t('files_encryption', 'Invalid private key for Encryption App. Please update your private key password in your personal settings to recover access to your encrypted files.'));
			return;
		}
		if (encryptedFiles === '1') {
			OC.Notification.show(t('files_encryption', 'Encryption was disabled but your files are still encrypted. Please go to your personal settings to decrypt your files.'));
			return;
		}
	},

	setupDragAndDrop: function() {
		var $fileList = $('#fileList');

		//drag/drop of files
		$fileList.find('tr td.filename').each(function(i,e) {
			if ($(e).parent().data('permissions') & OC.PERMISSION_DELETE) {
				$(e).draggable(dragOptions);
			}
		});

		$fileList.find('tr[data-type="dir"] td.filename').each(function(i,e) {
			if ($(e).parent().data('permissions') & OC.PERMISSION_CREATE) {
				$(e).droppable(folderDropOptions);
			}
		});
	},

	lastWidth: 0,

	initBreadCrumbs: function () {
		var $controls = $('#controls');

		Files.lastWidth = 0;
		Files.breadcrumbs = [];

		// initialize with some extra space
		Files.breadcrumbsWidth = 64;
		if ( document.getElementById("navigation") ) {
			Files.breadcrumbsWidth += $('#navigation').get(0).offsetWidth;
		}
		Files.hiddenBreadcrumbs = 0;

		$.each($('.crumb'), function(index, breadcrumb) {
			Files.breadcrumbs[index] = breadcrumb;
			Files.breadcrumbsWidth += $(breadcrumb).get(0).offsetWidth;
		});

		$.each($('#controls .actions>div'), function(index, action) {
			Files.breadcrumbsWidth += $(action).get(0).offsetWidth;
		});

		// event handlers for breadcrumb items
		$controls.find('.crumb a').on('click', onClickBreadcrumb);

		// setup drag and drop
		$controls.find('.crumb:not(.last)').droppable(crumbDropOptions);
	},

	resizeBreadcrumbs: function (width, firstRun) {
		if (width !== Files.lastWidth) {
			if ((width < Files.lastWidth || firstRun) && width < Files.breadcrumbsWidth) {
				if (Files.hiddenBreadcrumbs === 0) {
					Files.breadcrumbsWidth -= $(Files.breadcrumbs[1]).get(0).offsetWidth;
					$(Files.breadcrumbs[1]).find('a').hide();
					$(Files.breadcrumbs[1]).append('<span>...</span>');
					Files.breadcrumbsWidth += $(Files.breadcrumbs[1]).get(0).offsetWidth;
					Files.hiddenBreadcrumbs = 2;
				}
				var i = Files.hiddenBreadcrumbs;
				while (width < Files.breadcrumbsWidth && i > 1 && i < Files.breadcrumbs.length - 1) {
					Files.breadcrumbsWidth -= $(Files.breadcrumbs[i]).get(0).offsetWidth;
					$(Files.breadcrumbs[i]).hide();
					Files.hiddenBreadcrumbs = i;
					i++;
				}
			} else if (width > Files.lastWidth && Files.hiddenBreadcrumbs > 0) {
				var i = Files.hiddenBreadcrumbs;
				while (width > Files.breadcrumbsWidth && i > 0) {
					if (Files.hiddenBreadcrumbs === 1) {
						Files.breadcrumbsWidth -= $(Files.breadcrumbs[1]).get(0).offsetWidth;
						$(Files.breadcrumbs[1]).find('span').remove();
						$(Files.breadcrumbs[1]).find('a').show();
						Files.breadcrumbsWidth += $(Files.breadcrumbs[1]).get(0).offsetWidth;
					} else {
						$(Files.breadcrumbs[i]).show();
						Files.breadcrumbsWidth += $(Files.breadcrumbs[i]).get(0).offsetWidth;
						if (Files.breadcrumbsWidth > width) {
							Files.breadcrumbsWidth -= $(Files.breadcrumbs[i]).get(0).offsetWidth;
							$(Files.breadcrumbs[i]).hide();
							break;
						}
					}
					i--;
					Files.hiddenBreadcrumbs = i;
				}
			}
			Files.lastWidth = width;
		}
	}
};
$(document).ready(function() {
	// FIXME: workaround for trashbin app
	if (window.trashBinApp) {
		return;
	}
	Files.displayEncryptionWarning();
	Files.bindKeyboardShortcuts(document, jQuery);

	FileList.postProcessList();
	Files.setupDragAndDrop();

	$('#file_action_panel').attr('activeAction', false);

	// allow dropping on the "files" app icon
	$('ul#apps li:first-child').data('dir','').droppable(crumbDropOptions);

	// Triggers invisible file input
	$('#upload a').on('click', function() {
		$(this).parent().children('#file_upload_start').trigger('click');
		return false;
	});

	// Trigger cancelling of file upload
	$('#uploadprogresswrapper .stop').on('click', function() {
		OC.Upload.cancelUploads();
		procesSelection();
	});

	// Show trash bin
	$('#trash').on('click', function() {
		window.location=OC.filePath('files_trashbin', '', 'index.php');
	});

	var lastChecked;

	// Sets the file link behaviour :
	$('#fileList').on('click','td.filename a',function(event) {
		if (event.ctrlKey || event.shiftKey) {
			event.preventDefault();
			if (event.shiftKey) {
				var last = $(lastChecked).parent().parent().prevAll().length;
				var first = $(this).parent().parent().prevAll().length;
				var start = Math.min(first, last);
				var end = Math.max(first, last);
				var rows = $(this).parent().parent().parent().children('tr');
				for (var i = start; i < end; i++) {
					$(rows).each(function(index) {
						if (index === i) {
							var checkbox = $(this).children().children('input:checkbox');
							$(checkbox).attr('checked', 'checked');
							$(checkbox).parent().parent().addClass('selected');
						}
					});
				}
			}
			var checkbox = $(this).parent().children('input:checkbox');
			lastChecked = checkbox;
			if ($(checkbox).attr('checked')) {
				$(checkbox).removeAttr('checked');
				$(checkbox).parent().parent().removeClass('selected');
				$('#select_all').removeAttr('checked');
			} else {
				$(checkbox).attr('checked', 'checked');
				$(checkbox).parent().parent().toggleClass('selected');
				var selectedCount = $('td.filename input:checkbox:checked').length;
				if (selectedCount === $('td.filename input:checkbox').length) {
					$('#select_all').attr('checked', 'checked');
				}
			}
			procesSelection();
		} else {
			var filename=$(this).parent().parent().attr('data-file');
			var tr=$('tr[data-file="'+filename+'"]');
			var renaming=tr.data('renaming');
			if (!renaming && !FileList.isLoading(filename)) {
				FileActions.currentFile = $(this).parent();
				var mime=FileActions.getCurrentMimeType();
				var type=FileActions.getCurrentType();
				var permissions = FileActions.getCurrentPermissions();
				var action=FileActions.getDefault(mime,type, permissions);
				if (action) {
					event.preventDefault();
					action(filename);
				}
			}
		}

	});

	// Sets the select_all checkbox behaviour :
	$('#select_all').click(function() {
		if ($(this).attr('checked')) {
			// Check all
			$('td.filename input:checkbox').attr('checked', true);
			$('td.filename input:checkbox').parent().parent().addClass('selected');
		} else {
			// Uncheck all
			$('td.filename input:checkbox').attr('checked', false);
			$('td.filename input:checkbox').parent().parent().removeClass('selected');
		}
		procesSelection();
	});

	$('#fileList').on('change', 'td.filename input:checkbox',function(event) {
		if (event.shiftKey) {
			var last = $(lastChecked).parent().parent().prevAll().length;
			var first = $(this).parent().parent().prevAll().length;
			var start = Math.min(first, last);
			var end = Math.max(first, last);
			var rows = $(this).parent().parent().parent().children('tr');
			for (var i = start; i < end; i++) {
				$(rows).each(function(index) {
					if (index === i) {
						var checkbox = $(this).children().children('input:checkbox');
						$(checkbox).attr('checked', 'checked');
						$(checkbox).parent().parent().addClass('selected');
					}
				});
			}
		}
		var selectedCount=$('td.filename input:checkbox:checked').length;
		$(this).parent().parent().toggleClass('selected');
		if (!$(this).attr('checked')) {
			$('#select_all').attr('checked',false);
		} else {
			if (selectedCount===$('td.filename input:checkbox').length) {
				$('#select_all').attr('checked',true);
			}
		}
		procesSelection();
	});

	$('.download').click('click',function(event) {
		var files=getSelectedFilesTrash('name');
		var fileslist = JSON.stringify(files);
		var dir=$('#dir').val()||'/';
		OC.Notification.show(t('files','Your download is being prepared. This might take some time if the files are big.'));
		// use special download URL if provided, e.g. for public shared files
		var downloadURL = document.getElementById("downloadURL");
		if ( downloadURL ) {
			window.location = downloadURL.value+"&download&files=" + encodeURIComponent(fileslist);
		} else {
			window.location = OC.filePath('files', 'ajax', 'download.php') + '?'+ $.param({ dir: dir, files: fileslist });
		}
		return false;
	});

	$('.delete-selected').click(function(event) {
		var files=getSelectedFilesTrash('name');
		event.preventDefault();
		FileList.do_delete(files);
		return false;
	});

	// drag&drop support using jquery.fileupload
	// TODO use OC.dialogs
	$(document).bind('drop dragover', function (e) {
			e.preventDefault(); // prevent browser from doing anything, if file isn't dropped in dropZone
	});

	//do a background scan if needed
	scanFiles();

	Files.initBreadCrumbs();

	$(window).resize(function() {
		var width = $(this).width();
		Files.resizeBreadcrumbs(width, false);
	});

	var width = $(this).width();
	Files.resizeBreadcrumbs(width, true);

	// display storage warnings
	setTimeout ( "Files.displayStorageWarnings()", 100 );
	OC.Notification.setDefault(Files.displayStorageWarnings);

	// only possible at the moment if user is logged in
	if (OC.currentUser) {
		// start on load - we ask the server every 5 minutes
		var updateStorageStatisticsInterval = 5*60*1000;
		var updateStorageStatisticsIntervalId = setInterval(Files.updateStorageStatistics, updateStorageStatisticsInterval);

		// Use jquery-visibility to de-/re-activate file stats sync
		if ($.support.pageVisibility) {
			$(document).on({
				'show.visibility': function() {
					if (!updateStorageStatisticsIntervalId) {
						updateStorageStatisticsIntervalId = setInterval(Files.updateStorageStatistics, updateStorageStatisticsInterval);
					}
				},
				'hide.visibility': function() {
					clearInterval(updateStorageStatisticsIntervalId);
					updateStorageStatisticsIntervalId = 0;
				}
			});
		}
	}

	//scroll to and highlight preselected file
	if (getURLParameter('scrollto')) {
		FileList.scrollTo(getURLParameter('scrollto'));
	}
});

function scanFiles(force, dir, users) {
	if (!OC.currentUser) {
		return;
	}

	if (!dir) {
		dir = '';
	}
	force = !!force; //cast to bool
	scanFiles.scanning = true;
	var scannerEventSource;
	if (users) {
		var usersString;
		if (users === 'all') {
			usersString = users;
		} else {
			usersString = JSON.stringify(users);
		}
		scannerEventSource = new OC.EventSource(OC.filePath('files','ajax','scan.php'),{force: force,dir: dir, users: usersString});
	} else {
		scannerEventSource = new OC.EventSource(OC.filePath('files','ajax','scan.php'),{force: force,dir: dir});
	}
	scanFiles.cancel = scannerEventSource.close.bind(scannerEventSource);
	scannerEventSource.listen('count',function(count) {
		console.log(count + ' files scanned');
	});
	scannerEventSource.listen('folder',function(path) {
		console.log('now scanning ' + path);
	});
	scannerEventSource.listen('done',function(count) {
		scanFiles.scanning=false;
		console.log('done after ' + count + ' files');
		Files.updateStorageStatistics();
	});
	scannerEventSource.listen('user',function(user) {
		console.log('scanning files for ' + user);
	});
}
scanFiles.scanning=false;

function boolOperationFinished(data, callback) {
	result = jQuery.parseJSON(data.responseText);
	Files.updateMaxUploadFilesize(result);
	if (result.status === 'success') {
		callback.call();
	} else {
		alert(result.data.message);
	}
}

var createDragShadow = function(event) {
	//select dragged file
	var isDragSelected = $(event.target).parents('tr').find('td input:first').prop('checked');
	if (!isDragSelected) {
		//select dragged file
		$(event.target).parents('tr').find('td input:first').prop('checked',true);
	}

	var selectedFiles = getSelectedFilesTrash();

	if (!isDragSelected && selectedFiles.length === 1) {
		//revert the selection
		$(event.target).parents('tr').find('td input:first').prop('checked',false);
	}

	//also update class when we dragged more than one file
	if (selectedFiles.length > 1) {
		$(event.target).parents('tr').addClass('selected');
	}

	// build dragshadow
	var dragshadow = $('<table class="dragshadow"></table>');
	var tbody = $('<tbody></tbody>');
	dragshadow.append(tbody);

	var dir=$('#dir').val();

	$(selectedFiles).each(function(i,elem) {
		var newtr = $('<tr/>').attr('data-dir', dir).attr('data-filename', elem.name);
		newtr.append($('<td/>').addClass('filename').text(elem.name));
		newtr.append($('<td/>').addClass('size').text(humanFileSize(elem.size)));
		tbody.append(newtr);
		if (elem.type === 'dir') {
			newtr.find('td.filename').attr('style','background-image:url('+OC.imagePath('core', 'filetypes/folder.png')+')');
		} else {
			var path = getPathForPreview(elem.name);
			Files.lazyLoadPreview(path, elem.mime, function(previewpath) {
				newtr.find('td.filename').attr('style','background-image:url('+previewpath+')');
			}, null, null, elem.etag);
		}
	});

	return dragshadow;
};

//options for file drag/drop
var dragOptions={
	revert: 'invalid', revertDuration: 300,
	opacity: 0.7, zIndex: 100, appendTo: 'body', cursorAt: { left: 24, top: 18 },
	helper: createDragShadow, cursor: 'move',
	stop: function(event, ui) {
		$('#fileList tr td.filename').addClass('ui-draggable');
	}
};
// sane browsers support using the distance option
if ( $('html.ie').length === 0) {
	dragOptions['distance'] = 20;
}

var folderDropOptions={
	drop: function( event, ui ) {
		//don't allow moving a file into a selected folder
		if ($(event.target).parents('tr').find('td input:first').prop('checked') === true) {
			return false;
		}

		var target = $(this).closest('tr').data('file');

		var files = ui.helper.find('tr');
		$(files).each(function(i,row) {
			var dir = $(row).data('dir');
			var file = $(row).data('filename');
			$.post(OC.filePath('files', 'ajax', 'move.php'), { dir: dir, file: file, target: dir+'/'+target }, function(result) {
				if (result) {
					if (result.status === 'success') {
						//recalculate folder size
						var oldSize = $('#fileList tr[data-file="'+target+'"]').data('size');
						var newSize = oldSize + $('#fileList tr[data-file="'+file+'"]').data('size');
						$('#fileList tr[data-file="'+target+'"]').data('size', newSize);
						$('#fileList tr[data-file="'+target+'"]').find('td.filesize').text(humanFileSize(newSize));

						FileList.remove(file);
						procesSelection();
						$('#notification').hide();
					} else {
						$('#notification').hide();
						$('#notification').text(result.data.message);
						$('#notification').fadeIn();
					}
				} else {
					OC.dialogs.alert(t('files', 'Error moving file'), t('files', 'Error'));
				}
			});
		});
	},
	tolerance: 'pointer'
};

var crumbDropOptions={
	drop: function( event, ui ) {
		var target=$(this).data('dir');
		var dir = $('#dir').val();
		while(dir.substr(0,1) === '/') {//remove extra leading /'s
				dir=dir.substr(1);
		}
		dir = '/' + dir;
		if (dir.substr(-1,1) !== '/') {
			dir = dir + '/';
		}
		if (target === dir || target+'/' === dir) {
			return;
		}
		var files = ui.helper.find('tr');
		$(files).each(function(i,row) {
			var dir = $(row).data('dir');
			var file = $(row).data('filename');
			$.post(OC.filePath('files', 'ajax', 'move.php'), { dir: dir, file: file, target: target }, function(result) {
				if (result) {
					if (result.status === 'success') {
						FileList.remove(file);
						procesSelection();
						$('#notification').hide();
					} else {
						$('#notification').hide();
						$('#notification').text(result.data.message);
						$('#notification').fadeIn();
					}
				} else {
					OC.dialogs.alert(t('files', 'Error moving file'), t('files', 'Error'));
				}
			});
		});
	},
	tolerance: 'pointer'
};

function procesSelection() {
	var selected = getSelectedFilesTrash();
	var selectedFiles = selected.filter(function(el) {
		return el.type==='file';
	});
	var selectedFolders = selected.filter(function(el) {
		return el.type==='dir';
	});
	if (selectedFiles.length === 0 && selectedFolders.length === 0) {
		$('#headerName>span.name').text(t('files','Name'));
		$('#headerSize').text(t('files','Size'));
		$('#modified').text(t('files','Modified'));
		$('table').removeClass('multiselect');
		$('.selectedActions').hide();
	}
	else {
		$('.selectedActions').show();
		var totalSize = 0;
		for(var i=0; i<selectedFiles.length; i++) {
			totalSize+=selectedFiles[i].size;
		};
		for(var i=0; i<selectedFolders.length; i++) {
			totalSize+=selectedFolders[i].size;
		};
		$('#headerSize').text(humanFileSize(totalSize));
		var selection = '';
		if (selectedFolders.length > 0) {
			selection += n('files', '%n folder', '%n folders', selectedFolders.length);
			if (selectedFiles.length > 0) {
				selection += ' & ';
			}
		}
		if (selectedFiles.length>0) {
			selection += n('files', '%n file', '%n files', selectedFiles.length);
		}
		$('#headerName span.name').text(selection);
		$('#modified').text('');
		$('table').addClass('multiselect');
	}
}

/**
 * @brief get a list of selected files
 * @param {string} property (option) the property of the file requested
 * @return {array}
 *
 * possible values for property: name, mime, size and type
 * if property is set, an array with that property for each file is returnd
 * if it's ommited an array of objects with all properties is returned
 */
function getSelectedFilesTrash(property) {
	var elements=$('td.filename input:checkbox:checked').parent().parent();
	var files=[];
	elements.each(function(i,element) {
		var file={
			name:$(element).attr('data-file'),
			mime:$(element).data('mime'),
			type:$(element).data('type'),
			size:$(element).data('size'),
			etag:$(element).data('etag')
		};
		if (property) {
			files.push(file[property]);
		} else {
			files.push(file);
		}
	});
	return files;
}

Files.getMimeIcon = function(mime, ready) {
	if (Files.getMimeIcon.cache[mime]) {
		ready(Files.getMimeIcon.cache[mime]);
	} else {
		$.get( OC.filePath('files','ajax','mimeicon.php'), {mime: mime}, function(path) {
			Files.getMimeIcon.cache[mime]=path;
			ready(Files.getMimeIcon.cache[mime]);
		});
	}
}
Files.getMimeIcon.cache={};

function getPathForPreview(name) {
	var path = $('#dir').val() + '/' + name;
	return path;
}

Files.lazyLoadPreview = function(path, mime, ready, width, height, etag) {
	// get mime icon url
	Files.getMimeIcon(mime, function(iconURL) {
		var urlSpec = {};
		var previewURL;
		ready(iconURL); // set mimeicon URL

		// now try getting a preview thumbnail URL
		if ( ! width ) {
			width = $('#filestable').data('preview-x');
		}
		if ( ! height ) {
			height = $('#filestable').data('preview-y');
		}
		// note: the order of arguments must match the one
		// from the server's template so that the browser
		// knows it's the same file for caching
		urlSpec.x = width;
		urlSpec.y = height;
		urlSpec.file = Files.fixPath(path);

		if (etag){
			// use etag as cache buster
			urlSpec.c = etag;
		}
		else {
			console.warn('Files.lazyLoadPreview(): missing etag argument');
		}

		if ( $('#publicUploadButtonMock').length ) {
			urlSpec.t = $('#dirToken').val();
			previewURL = OC.Router.generate('core_ajax_public_preview', urlSpec);
		} else {
			previewURL = OC.Router.generate('core_ajax_preview', urlSpec);
		}
		previewURL = previewURL.replace('(', '%28');
		previewURL = previewURL.replace(')', '%29');

		// preload image to prevent delay
		// this will make the browser cache the image
		var img = new Image();
		img.onload = function(){
			//set preview thumbnail URL
			ready(previewURL);
		}
		img.src = previewURL;
	});
}

function getUniqueName(name) {
	if ($('tr[data-file="'+name+'"]').exists()) {
		var parts=name.split('.');
		var extension = "";
		if (parts.length > 1) {
			extension=parts.pop();
		}
		var base=parts.join('.');
		numMatch=base.match(/\((\d+)\)/);
		var num=2;
		if (numMatch && numMatch.length>0) {
			num=parseInt(numMatch[numMatch.length-1])+1;
			base=base.split('(');
			base.pop();
			base=$.trim(base.join('('));
		}
		name=base+' ('+num+')';
		if (extension) {
			name = name+'.'+extension;
		}
		return getUniqueName(name);
	}
	return name;
}

function checkTrashStatus() {
	$.post(OC.filePath('files_trashbin', 'ajax', 'isEmpty.php'), function(result) {
		if (result.data.isEmpty === false) {
			$("input[type=button][id=trash]").removeAttr("disabled");
		}
	});
}

function onClickBreadcrumb(e) {
	var $el = $(e.target).closest('.crumb'),
		$targetDir = $el.data('dir');
		isPublic = !!$('#isPublic').val();

	if ($targetDir !== undefined && !isPublic) {
		e.preventDefault();
		FileList.changeDirectory(decodeURIComponent($targetDir));
	}
}
