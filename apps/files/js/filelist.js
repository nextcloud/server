/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC, t, n, FileList, FileActions, Files, BreadCrumb */
/* global procesSelection, dragOptions, SVGSupport */
window.FileList = {
	appName: t('files', 'Files'),
	isEmpty: true,
	useUndo:true,
	$el: $('#filestable'),
	$fileList: $('#fileList'),
	breadcrumb: null,
	initialized: false,

	/**
	 * Initialize the file list and its components
	 */
	initialize: function() {
		var self = this;
		if (this.initialized) {
			return;
		}

		// TODO: FileList should not know about global elements
		this.$el = $('#filestable');
		this.$fileList = $('#fileList');

		this.breadcrumb = new BreadCrumb({
			onClick: this._onClickBreadCrumb,
			onDrop: this._onDropOnBreadCrumb,
			getCrumbUrl: function(part, index) {
				return self.linkTo(part.dir);
			}
		});

		$('#controls').prepend(this.breadcrumb.$el);

		$(window).resize(function() {
			// TODO: debounce this ?
			var width = $(this).width();
			FileList.breadcrumb.resize(width, false);
		});
	},

	/**
	 * Event handler when clicking on a bread crumb
	 */
	_onClickBreadCrumb: function(e) {
		var $el = $(e.target).closest('.crumb'),
			$targetDir = $el.data('dir');

		if ($targetDir !== undefined) {
			e.preventDefault();
			FileList.changeDirectory($targetDir);
		}
	},

	/**
	 * Event handler when dropping on a breadcrumb
	 */
	_onDropOnBreadCrumb: function( event, ui ) {
		var target=$(this).data('dir');
		var dir = FileList.getCurrentDirectory();
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
			//slapdash selector, tracking down our original element that the clone budded off of.
			var origin = $('tr[data-id=' + $(row).data('origin') + ']');
			var td = origin.children('td.filename');
			var oldBackgroundImage = td.css('background-image');
			td.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
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
				td.css('background-image', oldBackgroundImage);
			});
		});
	},

	/**
	 * Sets a new page title
	 */
	setPageTitle: function(title){
		if (title) {
			title += ' - ';
		} else {
			title = '';
		}
		title += FileList.appName;
		// Sets the page title with the " - ownCloud" suffix as in templates
		window.document.title = title + ' - ' + oc_defaults.title;

		return true;
	},
	/**
	 * Returns the tr element for a given file name
	 * @param fileName file name
	 */
	findFileEl: function(fileName){
		// use filterAttr to avoid escaping issues
		return this.$fileList.find('tr').filterAttr('data-file', fileName);
	},
	/**
	 * Sets the files to be displayed in the list.
	 * This operation will rerender the list and update the summary.
	 * @param filesArray array of file data (map)
	 */
	setFiles:function(filesArray) {
		// detach to make adding multiple rows faster
		this.$fileList.detach();

		this.$fileList.empty();

		this.isEmpty = filesArray.length === 0;
		for (var i = 0; i < filesArray.length; i++) {
			this.add(filesArray[i], {updateSummary: false});
		}

		this.$el.find('thead').after(this.$fileList);

		this.updateEmptyContent();
		this.$fileList.trigger(jQuery.Event("fileActionsReady"));
		// "Files" might not be loaded in extending apps
		if (window.Files) {
			Files.setupDragAndDrop();
		}
		this.updateFileSummary();
		procesSelection();
		$(window).scrollTop(0);

		this.$fileList.trigger(jQuery.Event("updated"));
	},
	/**
	 * If SVG is not supported, replaces the given images's extension
	 * from ".svg" to ".png".
	 * If SVG is supported, return the image path as is.
	 * @param icon image path
	 * @return fixed image path
	 */
	_replaceSVG: function(icon) {
		if (!SVGSupport()) {
			var i = icon.lastIndexOf('.svg');
			if (i >= 0) {
				icon = icon.substr(0, i) + '.png' + icon.substr(i+4);
			}
		}
		return icon;
	},
	/**
	 * Creates a new table row element using the given file data.
	 * @param fileData map of file attributes
	 * @param options map of attribute "loading" whether the entry is currently loading
	 * @return new tr element (not appended to the table)
	 */
	_createRow: function(fileData, options) {
		var td, simpleSize, basename, extension, sizeColor,
			icon = FileList._replaceSVG(fileData.icon),
			name = fileData.name,
			type = fileData.type || 'file',
			mtime = parseInt(fileData.mtime, 10) || new Date().getTime(),
			mime = fileData.mimetype,
			linkUrl;
		options = options || {};

		if (type === 'dir') {
			mime = mime || 'httpd/unix-directory';
		}
		//containing tr
		var tr = $('<tr></tr>').attr({
			"data-id" : fileData.id,
			"data-type": type,
			"data-size": fileData.size,
			"data-file": name,
			"data-mime": mime,
			"data-mtime": mtime,
			"data-etag": fileData.etag,
			"data-permissions": fileData.permissions || this.getDirectoryPermissions()
		});

		if (type === 'dir') {
			// use default folder icon
			icon = icon || OC.imagePath('core', 'filetypes/folder');
		}
		else {
			icon = icon || OC.imagePath('core', 'filetypes/file');
		}

		// filename td
		td = $('<td></td>').attr({
			"class": "filename",
			"style": 'background-image:url(' + icon + '); background-size: 32px;'
		});

		// linkUrl
		if (type === 'dir') {
			linkUrl = FileList.linkTo(FileList.getCurrentDirectory() + '/' + name);
		}
		else {
			linkUrl = Files.getDownloadUrl(name, FileList.getCurrentDirectory());
		}
		td.append('<input id="select-' + fileData.id + '" type="checkbox" /><label for="select-' + fileData.id + '"></label>');
		var link_elem = $('<a></a>').attr({
			"class": "name",
			"href": linkUrl
		});

		// from here work on the display name
		name = fileData.displayName || name;

		// split extension from filename for non dirs
		if (type !== 'dir' && name.indexOf('.') !== -1) {
			basename = name.substr(0, name.lastIndexOf('.'));
			extension = name.substr(name.lastIndexOf('.'));
		} else {
			basename = name;
			extension = false;
		}
		var name_span=$('<span></span>').addClass('nametext').text(basename);
		link_elem.append(name_span);
		if (extension) {
			name_span.append($('<span></span>').addClass('extension').text(extension));
		}
		// dirs can show the number of uploaded files
		if (type === 'dir') {
			link_elem.append($('<span></span>').attr({
				'class': 'uploadtext',
				'currentUploads': 0
			}));
		}
		td.append(link_elem);
		tr.append(td);

		// size column
		if (typeof(fileData.size) !== 'undefined' && fileData.size >= 0) {
			simpleSize = humanFileSize(parseInt(fileData.size, 10));
			sizeColor = Math.round(160-Math.pow((fileData.size/(1024*1024)),2));
		} else {
			simpleSize = t('files', 'Pending');
		}
		var lastModifiedTime = Math.round(mtime / 1000);
		td = $('<td></td>').attr({
			"class": "filesize",
			"style": 'color:rgb(' + sizeColor + ',' + sizeColor + ',' + sizeColor + ')'
		}).text(simpleSize);
		tr.append(td);

		// date column
		var modifiedColor = Math.round((Math.round((new Date()).getTime() / 1000) - mtime)/60/60/24*5);
		td = $('<td></td>').attr({ "class": "date" });
		td.append($('<span></span>').attr({
			"class": "modified",
			"title": formatDate(mtime),
			"style": 'color:rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')'
		}).text( relative_modified_date(mtime / 1000) ));
		tr.find('.filesize').text(simpleSize);
		tr.append(td);
		return tr;
	},
	/**
	 * Adds an entry to the files table using the data from the given file data
	 * @param fileData map of file attributes
	 * @param options map of attributes:
	 * - "insert" true to insert in a sorted manner, false to append (default)
	 * - "updateSummary" true to update the summary after adding (default), false otherwise
	 * @return new tr element (not appended to the table)
	 */
	add: function(fileData, options) {
		options = options || {};
		var type = fileData.type || 'file',
			mime = fileData.mimetype,
			permissions = parseInt(fileData.permissions, 10) || 0;

		if (type === 'dir') {
			mime = mime || 'httpd/unix-directory';
		}
		var tr = this._createRow(
			fileData,
			options
		);
		var filenameTd = tr.find('td.filename');

		// sorted insert is expensive, so needs to be explicitly
		// requested
		if (options.insert) {
			this.insertElement(fileData.name, type, tr);
		}
		else {
			this.$fileList.append(tr);
		}
		FileList.isEmpty = false;

		// TODO: move dragging to FileActions ?
		// enable drag only for deletable files
		if (permissions & OC.PERMISSION_DELETE) {
			filenameTd.draggable(dragOptions);
		}
		// allow dropping on folders
		if (fileData.type === 'dir') {
			filenameTd.droppable(folderDropOptions);
		}

		if (options.hidden) {
			tr.addClass('hidden');
		}

		// display actions
		FileActions.display(filenameTd, false);

		if (fileData.isPreviewAvailable) {
			// lazy load / newly inserted td ?
			if (!fileData.icon) {
				Files.lazyLoadPreview(getPathForPreview(fileData.name), mime, function(url) {
					filenameTd.css('background-image', 'url(' + url + ')');
				}, null, null, fileData.etag);
			}
			else {
				// set the preview URL directly
				var urlSpec = {
						file: FileList.getCurrentDirectory() + '/' + fileData.name,
						c: fileData.etag
					};
				var previewUrl = Files.generatePreviewUrl(urlSpec);
				previewUrl = previewUrl.replace('(', '%28').replace(')', '%29');
				filenameTd.css('background-image', 'url(' + previewUrl + ')');
			}
		}

		// defaults to true if not defined
		if (typeof(options.updateSummary) === 'undefined' || !!options.updateSummary) {
			this.updateFileSummary();
			this.updateEmptyContent();
		}
		return tr;
	},
	/**
	 * Returns the current directory
	 * @return current directory
	 */
	getCurrentDirectory: function(){
		return $('#dir').val() || '/';
	},
	/**
	 * Returns the directory permissions
	 * @return permission value as integer
	 */
	getDirectoryPermissions: function() {
		return parseInt($('#permissions').val(), 10);
	},
	/**
	 * @brief Changes the current directory and reload the file list.
	 * @param targetDir target directory (non URL encoded)
	 * @param changeUrl false if the URL must not be changed (defaults to true)
	 * @param {boolean} force set to true to force changing directory
	 */
	changeDirectory: function(targetDir, changeUrl, force) {
		var $dir = $('#dir'),
			url,
			currentDir = $dir.val() || '/';
		targetDir = targetDir || '/';
		if (!force && currentDir === targetDir) {
			return;
		}
		FileList._setCurrentDir(targetDir, changeUrl);
		$('#fileList').trigger(
			jQuery.Event('changeDirectory', {
				dir: targetDir,
				previousDir: currentDir
			}
		));
		FileList.reload();
	},
	linkTo: function(dir) {
		return OC.linkTo('files', 'index.php')+"?dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
	},

	/**
	 * Sets the current directory name and updates the breadcrumb.
	 * @param targetDir directory to display
	 * @param changeUrl true to also update the URL, false otherwise (default)
	 */
	_setCurrentDir: function(targetDir, changeUrl) {
		var url,
			baseDir = OC.basename(targetDir);

		if (baseDir !== '') {
			FileList.setPageTitle(baseDir);
		}
		else {
			FileList.setPageTitle();
		}

		$('#dir').val(targetDir);
		if (changeUrl !== false) {
			if (window.history.pushState && changeUrl !== false) {
				url = FileList.linkTo(targetDir);
				window.history.pushState({dir: targetDir}, '', url);
			}
			// use URL hash for IE8
			else{
				window.location.hash = '?dir='+ encodeURIComponent(targetDir).replace(/%2F/g, '/');
			}
		}
		this.breadcrumb.setDirectory(this.getCurrentDirectory());
	},
	/**
	 * @brief Reloads the file list using ajax call
	 */
	reload: function() {
		FileList.showMask();
		if (FileList._reloadCall) {
			FileList._reloadCall.abort();
		}
		FileList._reloadCall = $.ajax({
			url: Files.getAjaxUrl('list'),
			data: {
				dir : $('#dir').val()
			},
			error: function(result) {
				FileList.reloadCallback(result);
			},
			success: function(result) {
				FileList.reloadCallback(result);
			}
		});
	},
	reloadCallback: function(result) {
		var $controls = $('#controls');

		delete this._reloadCall;
		this.hideMask();

		if (!result || result.status === 'error') {
			OC.Notification.show(result.data.message);
			return;
		}

		if (result.status === 404) {
			// go back home
			this.changeDirectory('/');
			return;
		}
		// aborted ?
		if (result.status === 0){
			return;
		}

		// TODO: should rather return upload file size through
		// the files list ajax call
		Files.updateStorageStatistics(true);

		if (result.data.permissions) {
			this.setDirectoryPermissions(result.data.permissions);
		}

		this.setFiles(result.data.files);
	},
	setDirectoryPermissions: function(permissions) {
		var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
		$('#permissions').val(permissions);
		$('.creatable').toggleClass('hidden', !isCreatable);
		$('.notCreatable').toggleClass('hidden', isCreatable);
	},
	/**
	 * Shows/hides action buttons
	 *
	 * @param show true for enabling, false for disabling
	 */
	showActions: function(show){
		$('.actions,#file_action_panel').toggleClass('hidden', !show);
		if (show){
			// make sure to display according to permissions
			var permissions = this.getDirectoryPermissions();
			var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
			$('.creatable').toggleClass('hidden', !isCreatable);
			$('.notCreatable').toggleClass('hidden', isCreatable);
			// remove old style breadcrumbs (some apps might create them)
			$('#controls .crumb').remove();
			// refresh breadcrumbs in case it was replaced by an app
			this.breadcrumb.render();
		}
		else{
			$('.creatable, .notCreatable').addClass('hidden');
		}
	},
	/**
	 * Enables/disables viewer mode.
	 * In viewer mode, apps can embed themselves under the controls bar.
	 * In viewer mode, the actions of the file list will be hidden.
	 * @param show true for enabling, false for disabling
	 */
	setViewerMode: function(show){
		this.showActions(!show);
		$('#filestable').toggleClass('hidden', show);
	},
	/**
	 * Removes a file entry from the list
	 * @param name name of the file to remove
	 * @param options optional options as map:
	 * "updateSummary": true to update the summary (default), false otherwise
	 */
	remove:function(name, options){
		options = options || {};
		var fileEl = FileList.findFileEl(name);
		if (fileEl.data('permissions') & OC.PERMISSION_DELETE) {
			// file is only draggable when delete permissions are set
			fileEl.find('td.filename').draggable('destroy');
		}
		fileEl.remove();
		// TODO: improve performance on batch update
		FileList.isEmpty = !this.$fileList.find('tr:not(.summary)').length;
		if (typeof(options.updateSummary) === 'undefined' || !!options.updateSummary) {
			FileList.updateEmptyContent();
			FileList.updateFileSummary();
		}
		return fileEl;
	},
	insertElement:function(name, type, element) {
		// find the correct spot to insert the file or folder
		var pos,
			fileElements = this.$fileList.find('tr[data-file][data-type="'+type+'"]:not(.hidden)');
		if (name.localeCompare($(fileElements[0]).attr('data-file')) < 0) {
			pos = -1;
		} else if (name.localeCompare($(fileElements[fileElements.length-1]).attr('data-file')) > 0) {
			pos = fileElements.length - 1;
		} else {
			for(pos = 0; pos<fileElements.length-1; pos++) {
				if (name.localeCompare($(fileElements[pos]).attr('data-file')) > 0
					&& name.localeCompare($(fileElements[pos+1]).attr('data-file')) < 0)
				{
					break;
				}
			}
		}
		if (fileElements.exists()) {
			if (pos === -1) {
				$(fileElements[0]).before(element);
			} else {
				$(fileElements[pos]).after(element);
			}
		} else if (type === 'dir' && !FileList.isEmpty) {
			this.$fileList.find('tr[data-file]:first').before(element);
		} else if (type === 'file' && !FileList.isEmpty) {
			this.$fileList.find('tr[data-file]:last').before(element);
		} else {
			this.$fileList.append(element);
		}
		FileList.isEmpty = false;
		FileList.updateEmptyContent();
		FileList.updateFileSummary();
	},
	rename: function(oldname) {
		var tr, td, input, form;
		tr = FileList.findFileEl(oldname);
		tr.data('renaming',true);
		td = tr.children('td.filename');
		input = $('<input type="text" class="filename"/>').val(oldname);
		form = $('<form></form>');
		form.append(input);
		td.children('a.name').hide();
		td.append(form);
		input.focus();
		//preselect input
		var len = input.val().lastIndexOf('.');
		if (len === -1) {
			len = input.val().length;
		}
		input.selectRange(0, len);
		var checkInput = function () {
			var filename = input.val();
			if (filename !== oldname) {
				// Files.isFileNameValid(filename) throws an exception itself
				Files.isFileNameValid(filename, FileList.getCurrentDirectory());
				if (FileList.inList(filename)) {
					throw t('files', '{new_name} already exists', {new_name: filename});
				}
			}
			return true;
		};

		form.submit(function(event) {
			event.stopPropagation();
			event.preventDefault();
			try {
				var newname = input.val();
				var directory = FileList.getCurrentDirectory();
				if (newname !== oldname) {
					checkInput();
					// save background image, because it's replaced by a spinner while async request
					var oldBackgroundImage = td.css('background-image');
					// mark as loading
					td.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
					$.ajax({
						url: OC.filePath('files','ajax','rename.php'),
						data: {
							dir : $('#dir').val(),
							newname: newname,
							file: oldname
						},
						success: function(result) {
							if (!result || result.status === 'error') {
								OC.dialogs.alert(result.data.message, t('core', 'Could not rename file'));
								// revert changes
								newname = oldname;
								tr.attr('data-file', newname);
								var path = td.children('a.name').attr('href');
								td.children('a.name').attr('href', path.replace(encodeURIComponent(oldname), encodeURIComponent(newname)));
								var basename = newname;
								if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
									basename = newname.substr(0,newname.lastIndexOf('.'));
								}
								td.find('a.name span.nametext').text(basename);
								if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
									if ( ! td.find('a.name span.extension').exists() ) {
										td.find('a.name span.nametext').append('<span class="extension"></span>');
									}
									td.find('a.name span.extension').text(newname.substr(newname.lastIndexOf('.')));
								}
								tr.find('.fileactions').effect('highlight', {}, 5000);
								tr.effect('highlight', {}, 5000);
								// remove loading mark and recover old image
								td.css('background-image', oldBackgroundImage);
							}
							else {
								var fileInfo = result.data;
								tr.attr('data-mime', fileInfo.mime);
								tr.attr('data-etag', fileInfo.etag);
								if (fileInfo.isPreviewAvailable) {
									Files.lazyLoadPreview(directory + '/' + fileInfo.name, result.data.mime, function(previewpath) {
										tr.find('td.filename').attr('style','background-image:url('+previewpath+')');
									}, null, null, result.data.etag);
								}
								else {
									tr.find('td.filename').removeClass('preview').attr('style','background-image:url('+FileList._replaceSVG(fileInfo.icon)+')');
								}
							}
							// reinsert row
							tr.detach();
							FileList.insertElement( tr.attr('data-file'), tr.attr('data-type'),tr );
							// update file actions in case the extension changed
							FileActions.display( tr.find('td.filename'), true);
						}
					});
				}
				input.tipsy('hide');
				tr.data('renaming',false);
				tr.attr('data-file', newname);
				var path = td.children('a.name').attr('href');
				// FIXME this will fail if the path contains the filename.
				td.children('a.name').attr('href', path.replace(encodeURIComponent(oldname), encodeURIComponent(newname)));
				var basename = newname;
				if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
					basename = newname.substr(0, newname.lastIndexOf('.'));
				}
				td.find('a.name span.nametext').text(basename);
				if (newname.indexOf('.') > 0 && tr.data('type') !== 'dir') {
					if ( ! td.find('a.name span.extension').exists() ) {
						td.find('a.name span.nametext').append('<span class="extension"></span>');
					}
					td.find('a.name span.extension').text(newname.substr(newname.lastIndexOf('.')));
				}
				form.remove();
				FileActions.display( tr.find('td.filename'), true);
				td.children('a.name').show();
			} catch (error) {
				input.attr('title', error);
				input.tipsy({gravity: 'w', trigger: 'manual'});
				input.tipsy('show');
				input.addClass('error');
			}
			return false;
		});
		input.keyup(function(event) {
			// verify filename on typing
			try {
				checkInput();
				input.tipsy('hide');
				input.removeClass('error');
			} catch (error) {
				input.attr('title', error);
				input.tipsy({gravity: 'w', trigger: 'manual'});
				input.tipsy('show');
				input.addClass('error');
			}
			if (event.keyCode === 27) {
				input.tipsy('hide');
				tr.data('renaming',false);
				form.remove();
				td.children('a.name').show();
			}
		});
		input.click(function(event) {
			event.stopPropagation();
			event.preventDefault();
		});
		input.blur(function() {
			form.trigger('submit');
		});
	},
	inList:function(file) {
		return FileList.findFileEl(file).length;
	},
	/**
	 * Delete the given files from the given dir
	 * @param files file names list (without path)
	 * @param dir directory in which to delete the files, defaults to the current
	 * directory
	 */
	do_delete:function(files, dir) {
		var params;
		if (files && files.substr) {
			files=[files];
		}
		if (files) {
			for (var i=0; i<files.length; i++) {
				var deleteAction = FileList.findFileEl(files[i]).children("td.date").children(".action.delete");
				deleteAction.removeClass('delete-icon').addClass('progress-icon');
			}
		}
		// Finish any existing actions
		if (FileList.lastAction) {
			FileList.lastAction();
		}

		params = {
			dir: dir || FileList.getCurrentDirectory()
		};
		if (files) {
			params.files = JSON.stringify(files);
		}
		else {
			// no files passed, delete all in current dir
			params.allfiles = true;
		}

		$.post(OC.filePath('files', 'ajax', 'delete.php'),
				params,
				function(result) {
					if (result.status === 'success') {
						if (params.allfiles) {
							// clear whole list
							$('#fileList tr').remove();
						}
						else {
							$.each(files,function(index,file) {
								var fileEl = FileList.remove(file, {updateSummary: false});
								fileEl.find('input[type="checkbox"]').prop('checked', false);
								fileEl.removeClass('selected');
							});
						}
						procesSelection();
						checkTrashStatus();
						FileList.updateFileSummary();
						FileList.updateEmptyContent();
						Files.updateStorageStatistics();
					} else {
						if (result.status === 'error' && result.data.message) {
							OC.Notification.show(result.data.message);
						}
						else {
							OC.Notification.show(t('files', 'Error deleting file.'));
						}
						// hide notification after 10 sec
						setTimeout(function() {
							OC.Notification.hide();
						}, 10000);
						if (params.allfiles) {
							// reload the page as we don't know what files were deleted
							// and which ones remain
							FileList.reload();
						}
						else {
							$.each(files,function(index,file) {
								var deleteAction = FileList.findFileEl(file).find('.action.delete');
								deleteAction.removeClass('progress-icon').addClass('delete-icon');
							});
						}
					}
				});
	},
	createFileSummary: function() {
		if ( !FileList.isEmpty ) {
			var summary = this._calculateFileSummary();

			// Get translations
			var directoryInfo = n('files', '%n folder', '%n folders', summary.totalDirs);
			var fileInfo = n('files', '%n file', '%n files', summary.totalFiles);

			var infoVars = {
				dirs: '<span class="dirinfo">'+directoryInfo+'</span><span class="connector">',
				files: '</span><span class="fileinfo">'+fileInfo+'</span>'
			};

			var info = t('files', '{dirs} and {files}', infoVars);

			// don't show the filesize column, if filesize is NaN (e.g. in trashbin)
			if (isNaN(summary.totalSize)) {
				var fileSize = '';
			} else {
				var fileSize = '<td class="filesize">'+humanFileSize(summary.totalSize)+'</td>';
			}

			var $summary = $('<tr class="summary" data-file="undefined"><td><span class="info">'+info+'</span></td>'+fileSize+'<td></td></tr>');
			this.$fileList.append($summary);

			var $dirInfo = $summary.find('.dirinfo');
			var $fileInfo = $summary.find('.fileinfo');
			var $connector = $summary.find('.connector');

			// Show only what's necessary, e.g.: no files: don't show "0 files"
			if (summary.totalDirs === 0) {
				$dirInfo.addClass('hidden');
				$connector.addClass('hidden');
			}
			if (summary.totalFiles === 0) {
				$fileInfo.addClass('hidden');
				$connector.addClass('hidden');
			}
		}
	},
	_calculateFileSummary: function() {
		var result = {
			totalDirs: 0,
			totalFiles: 0,
			totalSize: 0
		};
		$.each($('tr[data-file]'), function(index, value) {
			var $value = $(value);
			if ($value.data('type') === 'dir') {
				result.totalDirs++;
			} else if ($value.data('type') === 'file') {
				result.totalFiles++;
			}
			if ($value.data('size') !== undefined && $value.data('id') !== -1) {
				//Skip shared as it does not count toward quota
				result.totalSize += parseInt($value.data('size'));
			}
		});
		return result;
	},
	updateFileSummary: function() {
		var $summary = this.$el.find('.summary');

		// always make it the last element
		this.$fileList.append($summary.detach());

		// Check if we should remove the summary to show "Upload something"
		if (this.isEmpty && $summary.length === 1) {
			$summary.remove();
		}
		// If there's no summary create one (createFileSummary checks if there's data)
		else if ($summary.length === 0) {
			FileList.createFileSummary();
		}
		// There's a summary and data -> Update the summary
		else if (!this.isEmpty && $summary.length === 1) {
			var fileSummary = this._calculateFileSummary();
			var $dirInfo = $('.summary .dirinfo');
			var $fileInfo = $('.summary .fileinfo');
			var $connector = $('.summary .connector');

			// Substitute old content with new translations
			$dirInfo.html(n('files', '%n folder', '%n folders', fileSummary.totalDirs));
			$fileInfo.html(n('files', '%n file', '%n files', fileSummary.totalFiles));
			$('.summary .filesize').html(humanFileSize(fileSummary.totalSize));

			// Show only what's necessary (may be hidden)
			if (fileSummary.totalDirs === 0) {
				$dirInfo.addClass('hidden');
				$connector.addClass('hidden');
			} else {
				$dirInfo.removeClass('hidden');
			}
			if (fileSummary.totalFiles === 0) {
				$fileInfo.addClass('hidden');
				$connector.addClass('hidden');
			} else {
				$fileInfo.removeClass('hidden');
			}
			if (fileSummary.totalDirs > 0 && fileSummary.totalFiles > 0) {
				$connector.removeClass('hidden');
			}
		}
	},
	updateEmptyContent: function() {
		var $fileList = $('#fileList');
		var permissions = $('#permissions').val();
		var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
		$('#emptycontent').toggleClass('hidden', !isCreatable || !FileList.isEmpty);
		$('#filestable thead th').toggleClass('hidden', FileList.isEmpty);
	},
	/**
	 * Shows the loading mask.
	 *
	 * @see #hideMask
	 */
	showMask: function() {
		// in case one was shown before
		var $mask = $('#content .mask');
		if ($mask.exists()) {
			return;
		}

		this.$el.addClass('hidden');

		$mask = $('<div class="mask transparent"></div>');

		$mask.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
		$mask.css('background-repeat', 'no-repeat');
		$('#content').append($mask);

		$mask.removeClass('transparent');
	},
	/**
	 * Hide the loading mask.
	 * @see #showMask
	 */
	hideMask: function() {
		$('#content .mask').remove();
		this.$el.removeClass('hidden');
	},
	scrollTo:function(file) {
		//scroll to and highlight preselected file
		var $scrolltorow = FileList.findFileEl(file);
		if ($scrolltorow.exists()) {
			$scrolltorow.addClass('searchresult');
			$(window).scrollTop($scrolltorow.position().top);
			//remove highlight when hovered over
			$scrolltorow.one('hover', function() {
				$scrolltorow.removeClass('searchresult');
			});
		}
	},
	filter:function(query) {
		$('#fileList tr:not(.summary)').each(function(i,e) {
			if ($(e).data('file').toString().toLowerCase().indexOf(query.toLowerCase()) !== -1) {
				$(e).addClass("searchresult");
			} else {
				$(e).removeClass("searchresult");
			}
		});
		//do not use scrollto to prevent removing searchresult css class
		var first = $('#fileList tr.searchresult').first();
		if (first.exists()) {
			$(window).scrollTop(first.position().top);
		}
	},
	unfilter:function() {
		$('#fileList tr.searchresult').each(function(i,e) {
			$(e).removeClass("searchresult");
		});
	},
	/**
	 * Returns whether all files are selected
	 * @return true if all files are selected, false otherwise
	 */
	isAllSelected: function() {
		return $('#select_all').prop('checked');
	}
};

$(document).ready(function() {
	FileList.initialize();

	// handle upload events
	var file_upload_start = $('#file_upload_start');

	file_upload_start.on('fileuploaddrop', function(e, data) {
		OC.Upload.log('filelist handle fileuploaddrop', e, data);

		var dropTarget = $(e.originalEvent.target).closest('tr, .crumb');
		if (dropTarget && (dropTarget.data('type') === 'dir' || dropTarget.hasClass('crumb'))) { // drag&drop upload to folder

			// remember as context
			data.context = dropTarget;

			var dir = dropTarget.data('file');
			// if from file list, need to prepend parent dir
			if (dir) {
				var parentDir = $('#dir').val() || '/';
				if (parentDir[parentDir.length - 1] !== '/') {
					parentDir += '/';
				}
				dir = parentDir + dir;
			}
			else{
				// read full path from crumb
				dir = dropTarget.data('dir') || '/';
			}

			// update folder in form
			data.formData = function(form) {
				return [
					{name: 'dir', value: dir},
					{name: 'requesttoken', value: oc_requesttoken}
				];
			};
		} else {
			// cancel uploads to current dir if no permission
			var isCreatable = (FileList.getDirectoryPermissions() & OC.PERMISSION_CREATE) !== 0;
			if (!isCreatable) {
				return false;
			}
		}
	});
	file_upload_start.on('fileuploadadd', function(e, data) {
		OC.Upload.log('filelist handle fileuploadadd', e, data);

		//finish delete if we are uploading a deleted file
		if (FileList.deleteFiles && FileList.deleteFiles.indexOf(data.files[0].name)!==-1) {
			FileList.finishDelete(null, true); //delete file before continuing
		}

		// add ui visualization to existing folder
		if (data.context && data.context.data('type') === 'dir') {
			// add to existing folder

			// update upload counter ui
			var uploadtext = data.context.find('.uploadtext');
			var currentUploads = parseInt(uploadtext.attr('currentUploads'));
			currentUploads += 1;
			uploadtext.attr('currentUploads', currentUploads);

			var translatedText = n('files', 'Uploading %n file', 'Uploading %n files', currentUploads);
			if (currentUploads === 1) {
				var img = OC.imagePath('core', 'loading.gif');
				data.context.find('td.filename').attr('style','background-image:url('+img+')');
				uploadtext.text(translatedText);
				uploadtext.show();
			} else {
				uploadtext.text(translatedText);
			}
		}

	});
	/*
	 * when file upload done successfully add row to filelist
	 * update counter when uploading to sub folder
	 */
	file_upload_start.on('fileuploaddone', function(e, data) {
		OC.Upload.log('filelist handle fileuploaddone', e, data);

		var response;
		if (typeof data.result === 'string') {
			response = data.result;
		} else {
			// fetch response from iframe
			response = data.result[0].body.innerText;
		}
		var result=$.parseJSON(response);

		if (typeof result[0] !== 'undefined' && result[0].status === 'success') {
			var file = result[0];

			if (data.context && data.context.data('type') === 'dir') {

				// update upload counter ui
				var uploadtext = data.context.find('.uploadtext');
				var currentUploads = parseInt(uploadtext.attr('currentUploads'));
				currentUploads -= 1;
				uploadtext.attr('currentUploads', currentUploads);
				var translatedText = n('files', 'Uploading %n file', 'Uploading %n files', currentUploads);
				if (currentUploads === 0) {
					var img = OC.imagePath('core', 'filetypes/folder');
					data.context.find('td.filename').attr('style','background-image:url('+img+')');
					uploadtext.text(translatedText);
					uploadtext.hide();
				} else {
					uploadtext.text(translatedText);
				}

				// update folder size
				var size = parseInt(data.context.data('size'));
				size += parseInt(file.size);
				data.context.attr('data-size', size);
				data.context.find('td.filesize').text(humanFileSize(size));

			} else {
				// only append new file if uploaded into the current folder
				if (file.directory !== FileList.getCurrentDirectory()) {
					return;
				}

				// add as stand-alone row to filelist
				var size=t('files', 'Pending');
				if (data.files[0].size>=0) {
					size=data.files[0].size;
				}
				//should the file exist in the list remove it
				FileList.remove(file.name);

				// create new file context
				data.context = FileList.add(file, {insert: true});
			}
		}
	});
	file_upload_start.on('fileuploadstop', function(e, data) {
		OC.Upload.log('filelist handle fileuploadstop', e, data);

		//if user pressed cancel hide upload chrome
		if (data.errorThrown === 'abort') {
			//cleanup uploading to a dir
			var uploadtext = $('tr .uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder');
			uploadtext.parents('td.filename').attr('style','background-image:url('+img+')');
			uploadtext.fadeOut();
			uploadtext.attr('currentUploads', 0);
		}
	});
	file_upload_start.on('fileuploadfail', function(e, data) {
		OC.Upload.log('filelist handle fileuploadfail', e, data);

		//if user pressed cancel hide upload chrome
		if (data.errorThrown === 'abort') {
			//cleanup uploading to a dir
			var uploadtext = $('tr .uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder');
			uploadtext.parents('td.filename').attr('style','background-image:url('+img+')');
			uploadtext.fadeOut();
			uploadtext.attr('currentUploads', 0);
		}
	});

	$('#notification').hide();
	$('#notification:first-child').on('click', '.replace', function() {
		OC.Notification.hide(function() {
			FileList.replace($('#notification > span').attr('data-oldName'), $('#notification > span').attr('data-newName'), $('#notification > span').attr('data-isNewFile'));
		});
	});
	$('#notification:first-child').on('click', '.suggest', function() {
		var file = $('#notification > span').attr('data-oldName');
		FileList.findFileEl(file).removeClass('hidden');
		OC.Notification.hide();
	});
	$('#notification:first-child').on('click', '.cancel', function() {
		if ($('#notification > span').attr('data-isNewFile')) {
			FileList.deleteCanceled = false;
			FileList.deleteFiles = [$('#notification > span').attr('data-oldName')];
		}
	});
	FileList.useUndo=(window.onbeforeunload)?true:false;
	$(window).bind('beforeunload', function () {
		if (FileList.lastAction) {
			FileList.lastAction();
		}
	});
	$(window).unload(function () {
		$(window).trigger('beforeunload');
	});

	function decodeQuery(query) {
		return query.replace(/\+/g, ' ');
	}

	function parseHashQuery() {
		var hash = window.location.hash,
			pos = hash.indexOf('?'),
			query;
		if (pos >= 0) {
			return hash.substr(pos + 1);
		}
		return '';
	}

	function parseCurrentDirFromUrl() {
		var query = parseHashQuery(),
			params,
			dir = '/';
		// try and parse from URL hash first
		if (query) {
			params = OC.parseQueryString(decodeQuery(query));
		}
		// else read from query attributes
		if (!params) {
			params = OC.parseQueryString(decodeQuery(location.search));
		}
		return (params && params.dir) || '/';
	}

	// disable ajax/history API for public app (TODO: until it gets ported)
	// fallback to hashchange when no history support
	if (!window.history.pushState) {
		$(window).on('hashchange', function() {
			FileList.changeDirectory(parseCurrentDirFromUrl(), false);
		});
	}
	window.onpopstate = function(e) {
		var targetDir;
		if (e.state && e.state.dir) {
			targetDir = e.state.dir;
		}
		else{
			// read from URL
			targetDir = parseCurrentDirFromUrl();
		}
		if (targetDir) {
			FileList.changeDirectory(targetDir, false);
		}
	};

	var dir = parseCurrentDirFromUrl();
	// trigger ajax load, deferred to let sub-apps do their overrides first
	setTimeout(function() {
		FileList.changeDirectory(dir, false, true);
	}, 0);

	FileList.createFileSummary();
});

