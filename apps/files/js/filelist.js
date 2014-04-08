/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC, t, n, FileList, FileActions, Files, FileSummary, BreadCrumb */
/* global dragOptions, folderDropOptions */
window.FileList = {
	appName: t('files', 'Files'),
	isEmpty: true,
	useUndo:true,
	$el: $('#filestable'),
	$fileList: $('#fileList'),
	breadcrumb: null,

	/**
	 * Instance of FileSummary
	 */
	fileSummary: null,
	initialized: false,

	// number of files per page
	pageSize: 20,

	/**
	 * Array of files in the current folder.
	 * The entries are of file data.
	 */
	files: [],

	/**
	 * Map of file id to file data
	 */
	_selectedFiles: {},

	/**
	 * Summary of selected files.
	 * Instance of FileSummary.
	 */
	_selectionSummary: null,

	/**
	 * Compare two file info objects, sorting by
	 * folders first, then by name.
	 */
	_fileInfoCompare: function(fileInfo1, fileInfo2) {
		if (fileInfo1.type === 'dir' && fileInfo2.type !== 'dir') {
			return -1;
		}
		if (fileInfo1.type !== 'dir' && fileInfo2.type === 'dir') {
			return 1;
		}
		return fileInfo1.name.localeCompare(fileInfo2.name);
	},

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
		this.files = [];
		this._selectedFiles = {};
		this._selectionSummary = new FileSummary();

		this.fileSummary = this._createSummary();

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

		this.$fileList.on('click','td.filename a', this._onClickFile);
		this.$fileList.on('change', 'td.filename input:checkbox', this._onClickFileCheckbox);
		this.$el.find('#select_all').click(this._onClickSelectAll);
		this.$el.find('.download').click(this._onClickDownloadSelected);
		this.$el.find('.delete-selected').click(this._onClickDeleteSelected);
	},

	/**
	 * Selected/deselects the given file element and updated
	 * the internal selection cache.
	 *
	 * @param $tr single file row element
	 * @param state true to select, false to deselect
	 */
	_selectFileEl: function($tr, state) {
		var $checkbox = $tr.find('input:checkbox');
		var oldData = !!this._selectedFiles[$tr.data('id')];
		var data;
		$checkbox.prop('checked', state);
		$tr.toggleClass('selected', state);
		// already selected ?
		if (state === oldData) {
			return;
		}
		data = this.elementToFile($tr);
		if (state) {
			this._selectedFiles[$tr.data('id')] = data;
			this._selectionSummary.add(data);
		}
		else {
			delete this._selectedFiles[$tr.data('id')];
			this._selectionSummary.remove(data);
		}
		this.$el.find('#select_all').prop('checked', this._selectionSummary.getTotal() === this.files.length);
	},

	/**
	 * Event handler for when clicking on files to select them
	 */
	_onClickFile: function(event) {
		var $tr = $(this).closest('tr');
		if (event.ctrlKey || event.shiftKey) {
			event.preventDefault();
			if (event.shiftKey) {
				var $lastTr = $(FileList._lastChecked);
				var lastIndex = $lastTr.index();
				var currentIndex = $tr.index();
				var $rows = FileList.$fileList.children('tr');

				// last clicked checkbox below current one ?
				if (lastIndex > currentIndex) {
					var aux = lastIndex;
					lastIndex = currentIndex;
					currentIndex = aux;
				}

				// auto-select everything in-between
				for (var i = lastIndex + 1; i < currentIndex; i++) {
					FileList._selectFileEl($rows.eq(i), true);
				}
			}
			else {
				FileList._lastChecked = $tr;
			}
			var $checkbox = $tr.find('input:checkbox');
			FileList._selectFileEl($tr, !$checkbox.prop('checked'));
			FileList.updateSelectionSummary();
		} else {
			var filename = $tr.attr('data-file');
			var renaming = $tr.data('renaming');
			if (!renaming) {
				FileActions.currentFile = $tr.find('td');
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
	},

	/**
	 * Event handler for when clicking on a file's checkbox
	 */
	_onClickFileCheckbox: function() {
		var $tr = $(this).closest('tr');
		FileList._selectFileEl($tr, !$tr.hasClass('selected'));
		FileList._lastChecked = $tr;
		FileList.updateSelectionSummary();
	},

	/**
	 * Event handler for when selecting/deselecting all files
	 */
	_onClickSelectAll: function() {
		var checked = $(this).prop('checked');
		FileList.$fileList.find('td.filename input:checkbox').prop('checked', checked)
			.closest('tr').toggleClass('selected', checked);
		FileList._selectedFiles = {};
		if (checked) {
			for (var i = 0; i < FileList.files.length; i++) {
				var fileData = FileList.files[i];
				FileList._selectedFiles[fileData.id] = fileData;
				FileList._selectionSummary.add(fileData);
			}
		}
		else {
			FileList._selectionSummary.clear();
		}
		FileList.updateSelectionSummary();
	},

	/**
	 * Event handler for when clicking on "Download" for the selected files
	 */
	_onClickDownloadSelected: function(event) {
		var files;
		var dir = FileList.getCurrentDirectory();
		if (FileList.isAllSelected()) {
			files = OC.basename(dir);
			dir = OC.dirname(dir) || '/';
		}
		else {
			files = _.pluck(FileList.getSelectedFiles(), 'name');
		}
		OC.Notification.show(t('files','Your download is being prepared. This might take some time if the files are big.'));
		OC.redirect(Files.getDownloadUrl(files, dir));
		return false;
	},

	/**
	 * Event handler for when clicking on "Delete" for the selected files
	 */
	_onClickDeleteSelected: function(event) {
		var files = null;
		if (!FileList.isAllSelected()) {
			files = _.pluck(FileList.getSelectedFiles(), 'name');
		}
		FileList.do_delete(files);
		event.preventDefault();
		return false;
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

	_onScroll: function(e) {
		if ($(window).scrollTop() + $(window).height() > $(document).height() - 500) {
			this._nextPage(true);
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
						FileList.updateSelectionSummary();
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
	 * Returns the file data from a given file element.
	 * @param $el file tr element
	 * @return file data
	 */
	elementToFile: function($el){
		return {
			id: parseInt($el.attr('data-id'), 10),
			name: $el.attr('data-file'),
			mimetype: $el.attr('data-mime'),
			type: $el.attr('data-type'),
			size: parseInt($el.attr('data-size'), 10),
			etag: $el.attr('data-etag'),
		};
	},

	/**
	 * Appends the next page of files into the table
	 * @param animate true to animate the new elements
	 */
	_nextPage: function(animate) {
		var index = this.$fileList.children().length,
			count = this.pageSize,
			tr,
			fileData,
			newTrs = [],
			isAllSelected = this.isAllSelected();

		if (index >= this.files.length) {
			return;
		}

		while (count > 0 && index < this.files.length) {
			fileData = this.files[index];
			tr = this._renderRow(fileData, {updateSummary: false});
			this.$fileList.append(tr);
			if (isAllSelected || this._selectedFiles[fileData.id]) {
				tr.addClass('selected');
				tr.find('input:checkbox').prop('checked', true);
			}
			if (animate) {
				tr.addClass('appear transparent');
				newTrs.push(tr);
			}
			index++;
			count--;
		}

		if (animate) {
			// defer, for animation
			window.setTimeout(function() {
				for (var i = 0; i < newTrs.length; i++ ) {
					newTrs[i].removeClass('transparent');
				}
			}, 0);
		}
	},

	/**
	 * Sets the files to be displayed in the list.
	 * This operation will rerender the list and update the summary.
	 * @param filesArray array of file data (map)
	 */
	setFiles: function(filesArray) {
		// detach to make adding multiple rows faster
		this.files = filesArray;

		this.$fileList.detach();
		this.$fileList.empty();

		// clear "Select all" checkbox
		this.$el.find('#select_all').prop('checked', false);

		this.isEmpty = this.files.length === 0;
		this._nextPage();

		this.$el.find('thead').after(this.$fileList);

		this.updateEmptyContent();
		this.$fileList.trigger(jQuery.Event("fileActionsReady"));
		// "Files" might not be loaded in extending apps
		if (window.Files) {
			Files.setupDragAndDrop();
		}

		this.fileSummary.calculate(filesArray);

		FileList.updateSelectionSummary();
		$(window).scrollTop(0);

		this.$fileList.trigger(jQuery.Event("updated"));
	},
	/**
	 * Creates a new table row element using the given file data.
	 * @param fileData map of file attributes
	 * @param options map of attribute "loading" whether the entry is currently loading
	 * @return new tr element (not appended to the table)
	 */
	_createRow: function(fileData, options) {
		var td, simpleSize, basename, extension, sizeColor,
			icon = OC.Util.replaceSVGIcon(fileData.icon),
			name = fileData.name,
			type = fileData.type || 'file',
			mtime = parseInt(fileData.mtime, 10) || new Date().getTime(),
			mime = fileData.mimetype,
			linkUrl;
		options = options || {};

		if (type === 'dir') {
			mime = mime || 'httpd/unix-directory';
		}

		// user should always be able to rename a share mount point
		var allowRename = 0;
		if (fileData.isShareMountPoint) {
			allowRename = OC.PERMISSION_UPDATE;
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
			"data-permissions": fileData.permissions | allowRename || this.getDirectoryPermissions()
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
		var linkElem = $('<a></a>').attr({
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
		var nameSpan=$('<span></span>').addClass('nametext').text(basename);
		linkElem.append(nameSpan);
		if (extension) {
			nameSpan.append($('<span></span>').addClass('extension').text(extension));
		}
		// dirs can show the number of uploaded files
		if (type === 'dir') {
			linkElem.append($('<span></span>').attr({
				'class': 'uploadtext',
				'currentUploads': 0
			}));
		}
		td.append(linkElem);
		tr.append(td);

		// size column
		if (typeof(fileData.size) !== 'undefined' && fileData.size >= 0) {
			simpleSize = humanFileSize(parseInt(fileData.size, 10));
			sizeColor = Math.round(160-Math.pow((fileData.size/(1024*1024)),2));
		} else {
			simpleSize = t('files', 'Pending');
		}

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
	 * Adds an entry to the files array and also into the DOM
	 * in a sorted manner.
	 *
	 * @param fileData map of file attributes
	 * @param options map of attributes:
	 * - "updateSummary" true to update the summary after adding (default), false otherwise
	 * @return new tr element (not appended to the table)
	 */
	add: function(fileData, options) {
		var index = -1;
		var $tr;
		var $rows;
		var $insertionPoint;
		options = options || {};

		// there are three situations to cover:
		// 1) insertion point is visible on the current page
		// 2) insertion point is on a not visible page (visible after scrolling)
		// 3) insertion point is at the end of the list

		$rows = this.$fileList.children();
		index = this._findInsertionIndex(fileData);
		if (index > this.files.length) {
			index = this.files.length;
		}
		else {
			$insertionPoint = $rows.eq(index);
		}

		// is the insertion point visible ?
		if ($insertionPoint.length) {
			// only render if it will really be inserted
			$tr = this._renderRow(fileData, options);
			$insertionPoint.before($tr);
		}
		else {
			// if insertion point is after the last visible
			// entry, append
			if (index === $rows.length) {
				$tr = this._renderRow(fileData, options);
				this.$fileList.append($tr);
			}
		}

		this.isEmpty = false;
		this.files.splice(index, 0, fileData);

		if ($tr && options.animate) {
			$tr.addClass('appear transparent');
			window.setTimeout(function() {
				$tr.removeClass('transparent');
			});
		}

		// defaults to true if not defined
		if (typeof(options.updateSummary) === 'undefined' || !!options.updateSummary) {
			this.fileSummary.add(fileData, true);
			this.updateEmptyContent();
		}

		return $tr;
	},

	/**
	 * Creates a new row element based on the given attributes
	 * and returns it.
	 *
	 * @param fileData map of file attributes
	 * @param options map of attributes:
	 * - "index" optional index at which to insert the element
	 * - "updateSummary" true to update the summary after adding (default), false otherwise
	 * @return new tr element (not appended to the table)
	 */
	_renderRow: function(fileData, options) {
		options = options || {};
		var type = fileData.type || 'file',
			mime = fileData.mimetype,
			permissions = parseInt(fileData.permissions, 10) || 0;

		if (fileData.isShareMountPoint) {
			permissions = permissions | OC.PERMISSION_UPDATE;
		}

		if (type === 'dir') {
			mime = mime || 'httpd/unix-directory';
		}
		var tr = this._createRow(
			fileData,
			options
		);
		var filenameTd = tr.find('td.filename');

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
		this._selectedFiles = {};
		this._selectionSummary.clear();
		this.reload();
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
	 * @return deleted element
	 */
	remove: function(name, options){
		options = options || {};
		var fileEl = FileList.findFileEl(name);
		var index = fileEl.index();
		if (!fileEl.length) {
			return null;
		}
		if (this._selectedFiles[fileEl.data('id')]) {
			// remove from selection first
			this._selectFileEl(fileEl, false);
			this.updateSelectionSummary();
		}
		if (fileEl.data('permissions') & OC.PERMISSION_DELETE) {
			// file is only draggable when delete permissions are set
			fileEl.find('td.filename').draggable('destroy');
		}
		this.files.splice(index, 1);
		fileEl.remove();
		// TODO: improve performance on batch update
		FileList.isEmpty = !this.files.length;
		if (typeof(options.updateSummary) === 'undefined' || !!options.updateSummary) {
			FileList.updateEmptyContent();
			this.fileSummary.remove({type: fileEl.attr('data-type'), size: fileEl.attr('data-size')}, true);
		}

		var lastIndex = this.$fileList.children().length;
		// if there are less elements visible than one page
		// but there are still pending elements in the array,
		// then directly append the next page
		if (lastIndex < this.files.length && lastIndex < this.pageSize) {
			this._nextPage(true);
		}

		return fileEl;
	},
	/**
	 * Finds the index of the row before which the given
	 * fileData should be inserted, considering the current
	 * sorting
	 */
	_findInsertionIndex: function(fileData) {
		var index = 0;
		while (index < this.files.length && this._fileInfoCompare(fileData, this.files[index]) > 0) {
			index++;
		}
		return index;
	},
	rename: function(oldname) {
		var tr, td, input, form;
		tr = FileList.findFileEl(oldname);
		var oldFileInfo = this.files[tr.index()];
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
		if ( len === -1 ||
			tr.data('type') === 'dir' ) {
			len = input.val().length;
		}
		input.selectRange(0, len);
		var checkInput = function () {
			var filename = input.val();
			if (filename !== oldname) {
				// Files.isFileNameValid(filename) throws an exception itself
				Files.isFileNameValid(filename);
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
							var fileInfo;
							if (!result || result.status === 'error') {
								OC.dialogs.alert(result.data.message, t('core', 'Could not rename file'));
								fileInfo = oldFileInfo;
							}
							else {
								fileInfo = result.data;
							}
							// reinsert row
							FileList.files.splice(tr.index(), 1);
							tr.remove();
							FileList.add(fileInfo);
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
							FileList.setFiles([]);
						}
						else {
							$.each(files,function(index,file) {
								var fileEl = FileList.remove(file, {updateSummary: false});
								// FIXME: not sure why we need this after the
								// element isn't even in the DOM any more
								fileEl.find('input[type="checkbox"]').prop('checked', false);
								fileEl.removeClass('selected');
								FileList.fileSummary.remove({type: fileEl.attr('data-type'), size: fileEl.attr('data-size')});
							});
						}
						checkTrashStatus();
						FileList.updateEmptyContent();
						FileList.fileSummary.update();
						FileList.updateSelectionSummary();
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
	/**
	 * Creates the file summary section
	 */
	_createSummary: function() {
		var $tr = $('<tr class="summary"></tr>');
		this.$el.find('tfoot').append($tr);

		return new FileSummary($tr);
	},
	updateEmptyContent: function() {
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
		var $scrollToRow = FileList.findFileEl(file);
		if ($scrollToRow.exists()) {
			$scrollToRow.addClass('searchresult');
			$(window).scrollTop($scrollToRow.position().top);
			//remove highlight when hovered over
			$scrollToRow.one('hover', function() {
				$scrollToRow.removeClass('searchresult');
			});
		}
	},
	filter:function(query) {
		$('#fileList tr').each(function(i,e) {
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
	 * Update UI based on the current selection
	 */
	updateSelectionSummary: function() {
		var summary = this._selectionSummary.summary;
		if (summary.totalFiles === 0 && summary.totalDirs === 0) {
			$('#headerName span.name').text(t('files','Name'));
			$('#headerSize').text(t('files','Size'));
			$('#modified').text(t('files','Modified'));
			$('table').removeClass('multiselect');
			$('.selectedActions').addClass('hidden');
		}
		else {
			$('.selectedActions').removeClass('hidden');
			$('#headerSize').text(OC.Util.humanFileSize(summary.totalSize));
			var selection = '';
			if (summary.totalDirs > 0) {
				selection += n('files', '%n folder', '%n folders', summary.totalDirs);
				if (summary.totalFiles > 0) {
					selection += ' & ';
				}
			}
			if (summary.totalFiles > 0) {
				selection += n('files', '%n file', '%n files', summary.totalFiles);
			}
			$('#headerName span.name').text(selection);
			$('#modified').text('');
			$('table').addClass('multiselect');
		}
	},

	/**
	 * Returns whether all files are selected
	 * @return true if all files are selected, false otherwise
	 */
	isAllSelected: function() {
		return this.$el.find('#select_all').prop('checked');
	},

	/**
	 * Returns the file info of the selected files
	 *
	 * @return array of file names
	 */
	getSelectedFiles: function() {
		return _.values(this._selectedFiles);
	}
};

$(document).ready(function() {
	FileList.initialize();

	// handle upload events
	var fileUploadStart = $('#file_upload_start');

	fileUploadStart.on('fileuploaddrop', function(e, data) {
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
					{name: 'requesttoken', value: oc_requesttoken},
					{name: 'file_directory', value: data.files[0].relativePath}
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
	fileUploadStart.on('fileuploadadd', function(e, data) {
		OC.Upload.log('filelist handle fileuploadadd', e, data);

		//finish delete if we are uploading a deleted file
		if (FileList.deleteFiles && FileList.deleteFiles.indexOf(data.files[0].name)!==-1) {
			FileList.finishDelete(null, true); //delete file before continuing
		}

		// add ui visualization to existing folder
		if (data.context && data.context.data('type') === 'dir') {
			// add to existing folder

			// update upload counter ui
			var uploadText = data.context.find('.uploadtext');
			var currentUploads = parseInt(uploadText.attr('currentUploads'), 10);
			currentUploads += 1;
			uploadText.attr('currentUploads', currentUploads);

			var translatedText = n('files', 'Uploading %n file', 'Uploading %n files', currentUploads);
			if (currentUploads === 1) {
				var img = OC.imagePath('core', 'loading.gif');
				data.context.find('td.filename').attr('style','background-image:url('+img+')');
				uploadText.text(translatedText);
				uploadText.show();
			} else {
				uploadText.text(translatedText);
			}
		}

	});
	/*
	 * when file upload done successfully add row to filelist
	 * update counter when uploading to sub folder
	 */
	fileUploadStart.on('fileuploaddone', function(e, data) {
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
			var size = 0;

			if (data.context && data.context.data('type') === 'dir') {

				// update upload counter ui
				var uploadText = data.context.find('.uploadtext');
				var currentUploads = parseInt(uploadText.attr('currentUploads'), 10);
				currentUploads -= 1;
				uploadText.attr('currentUploads', currentUploads);
				var translatedText = n('files', 'Uploading %n file', 'Uploading %n files', currentUploads);
				if (currentUploads === 0) {
					var img = OC.imagePath('core', 'filetypes/folder');
					data.context.find('td.filename').attr('style','background-image:url('+img+')');
					uploadText.text(translatedText);
					uploadText.hide();
				} else {
					uploadText.text(translatedText);
				}

				// update folder size
				size = parseInt(data.context.data('size'), 10);
				size += parseInt(file.size, 10);
				data.context.attr('data-size', size);
				data.context.find('td.filesize').text(humanFileSize(size));
			} else {
				// only append new file if uploaded into the current folder
				if (file.directory !== '/' && file.directory !== FileList.getCurrentDirectory()) {

					var fileDirectory = file.directory.replace('/','').replace(/\/$/, "").split('/');

					if (fileDirectory.length === 1) {
						fileDirectory = fileDirectory[0];

						// Get the directory 
						var fd = FileList.findFileEl(fileDirectory);
						if (fd.length === 0) {
							var dir = {
								name: fileDirectory,
								type: 'dir',
								mimetype: 'httpd/unix-directory',
								permissions: file.permissions,
								size: 0,
								id: file.parentId
							};
							FileList.add(dir, {insert: true});
						}
					} else {
						fileDirectory = fileDirectory[0];
					}
					
					fileDirectory = FileList.findFileEl(fileDirectory);

					// update folder size
					size = parseInt(fileDirectory.attr('data-size'), 10);
					size += parseInt(file.size, 10);
					fileDirectory.attr('data-size', size);
					fileDirectory.find('td.filesize').text(humanFileSize(size));

					return;
				}

				// add as stand-alone row to filelist
				size = t('files', 'Pending');
				if (data.files[0].size>=0) {
					size=data.files[0].size;
				}
				//should the file exist in the list remove it
				FileList.remove(file.name);

				// create new file context
				data.context = FileList.add(file, {animate: true});
			}
		}
	});
	fileUploadStart.on('fileuploadstop', function(e, data) {
		OC.Upload.log('filelist handle fileuploadstop', e, data);

		//if user pressed cancel hide upload chrome
		if (data.errorThrown === 'abort') {
			//cleanup uploading to a dir
			var uploadText = $('tr .uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder');
			uploadText.parents('td.filename').attr('style','background-image:url('+img+')');
			uploadText.fadeOut();
			uploadText.attr('currentUploads', 0);
		}
	});
	fileUploadStart.on('fileuploadfail', function(e, data) {
		OC.Upload.log('filelist handle fileuploadfail', e, data);

		//if user pressed cancel hide upload chrome
		if (data.errorThrown === 'abort') {
			//cleanup uploading to a dir
			var uploadText = $('tr .uploadtext');
			var img = OC.imagePath('core', 'filetypes/folder');
			uploadText.parents('td.filename').attr('style','background-image:url('+img+')');
			uploadText.fadeOut();
			uploadText.attr('currentUploads', 0);
		}
	});

	$('#notification').hide();
	$('#notification:first-child').on('click', '.replace', function() {
		OC.Notification.hide(function() {
			FileList.replace(
				$('#notification > span').attr('data-oldName'),
				$('#notification > span').attr('data-newName'),
				$('#notification > span').attr('data-isNewFile'));
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
			pos = hash.indexOf('?');
		if (pos >= 0) {
			return hash.substr(pos + 1);
		}
		return '';
	}

	function parseCurrentDirFromUrl() {
		var query = parseHashQuery(),
			params;
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

	$(window).scroll(function(e) {FileList._onScroll(e);});

	var dir = parseCurrentDirFromUrl();
	// trigger ajax load, deferred to let sub-apps do their overrides first
	setTimeout(function() {
		FileList.changeDirectory(dir, false, true);
	}, 0);
});

