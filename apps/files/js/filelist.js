/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	/**
	 * The FileList class manages a file list view.
	 * A file list view consists of a controls bar and
	 * a file list table.
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = {
		SORT_INDICATOR_ASC_CLASS: 'icon-triangle-n',
		SORT_INDICATOR_DESC_CLASS: 'icon-triangle-s',

		id: 'files',
		appName: t('files', 'Files'),
		isEmpty: true,
		useUndo:true,

		/**
		 * Top-level container with controls and file list
		 */
		$el: null,

		/**
		 * Files table
		 */
		$table: null,

		/**
		 * List of rows (table tbody)
		 */
		$fileList: null,

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
		 * File actions handler, defaults to OCA.Files.FileActions
		 */
		fileActions: null,

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
		 * Sort attribute
		 */
		_sort: 'name',

		/**
		 * Sort direction: 'asc' or 'desc'
		 */
		_sortDirection: 'asc',

		/**
		 * Sort comparator function for the current sort
		 */
		_sortComparator: null,

		/**
		 * Whether to do a client side sort.
		 * When false, clicking on a table header will call reload().
		 * When true, clicking on a table header will simply resort the list.
		 */
		_clientSideSort: false,

		/**
		 * Current directory
		 */
		_currentDirectory: null,

		_dragOptions: null,
		_folderDropOptions: null,

		/**
		 * Initialize the file list and its components
		 *
		 * @param $el container element with existing markup for the #controls
		 * and a table
		 * @param options map of options, see other parameters
		 * @param scrollContainer scrollable container, defaults to $(window)
		 * @param dragOptions drag options, disabled by default
		 * @param folderDropOptions folder drop options, disabled by default
		 */
		initialize: function($el, options) {
			var self = this;
			options = options || {};
			if (this.initialized) {
				return;
			}

			if (options.dragOptions) {
				this._dragOptions = options.dragOptions;
			}
			if (options.folderDropOptions) {
				this._folderDropOptions = options.folderDropOptions;
			}

			this.$el = $el;
			this.$container = options.scrollContainer || $(window);
			this.$table = $el.find('table:first');
			this.$fileList = $el.find('#fileList');
			this._initFileActions(options.fileActions);
			this.files = [];
			this._selectedFiles = {};
			this._selectionSummary = new OCA.Files.FileSummary();

			this.fileSummary = this._createSummary();

			this.setSort('name', 'asc');

			var breadcrumbOptions = {
				onClick: _.bind(this._onClickBreadCrumb, this),
				getCrumbUrl: function(part) {
					return self.linkTo(part.dir);
				}
			};
			// if dropping on folders is allowed, then also allow on breadcrumbs
			if (this._folderDropOptions) {
				breadcrumbOptions.onDrop = _.bind(this._onDropOnBreadCrumb, this);
			}
			this.breadcrumb = new OCA.Files.BreadCrumb(breadcrumbOptions);

			this.$el.find('#controls').prepend(this.breadcrumb.$el);

			this.$el.find('thead th .columntitle').click(_.bind(this._onClickHeader, this));

			this._onResize = _.debounce(_.bind(this._onResize, this), 100);
			$(window).resize(this._onResize);

			this.$el.on('show', this._onResize);

			this.$fileList.on('click','td.filename>a.name', _.bind(this._onClickFile, this));
			this.$fileList.on('change', 'td.filename>input:checkbox', _.bind(this._onClickFileCheckbox, this));
			this.$el.on('urlChanged', _.bind(this._onUrlChanged, this));
			this.$el.find('.select-all').click(_.bind(this._onClickSelectAll, this));
			this.$el.find('.download').click(_.bind(this._onClickDownloadSelected, this));
			this.$el.find('.delete-selected').click(_.bind(this._onClickDeleteSelected, this));

			this.setupUploadEvents();

			this.$container.on('scroll', _.bind(this._onScroll, this));
		},

		/**
		 * Destroy / uninitialize this instance.
		 */
		destroy: function() {
			// TODO: also unregister other event handlers
			this.fileActions.off('registerAction', this._onFileActionsUpdated);
			this.fileActions.off('setDefault', this._onFileActionsUpdated);
		},

		_initFileActions: function(fileActions) {
			this.fileActions = fileActions;
			if (!this.fileActions) {
				this.fileActions = new OCA.Files.FileActions();
				this.fileActions.registerDefaultActions();
			}
			this._onFileActionsUpdated = _.debounce(_.bind(this._onFileActionsUpdated, this), 100);
			this.fileActions.on('registerAction', this._onFileActionsUpdated);
			this.fileActions.on('setDefault', this._onFileActionsUpdated);
		},

		/**
		 * Event handler for when the window size changed
		 */
		_onResize: function() {
			var containerWidth = this.$el.width();
			var actionsWidth = 0;
			$.each(this.$el.find('#controls .actions'), function(index, action) {
				actionsWidth += $(action).outerWidth();
			});

			// substract app navigation toggle when visible
			containerWidth -= $('#app-navigation-toggle').width();

			this.breadcrumb.setMaxWidth(containerWidth - actionsWidth - 10);
		},

		/**
		 * Event handler for when the URL changed
		 */
		_onUrlChanged: function(e) {
			if (e && e.dir) {
				this.changeDirectory(e.dir, false, true);
			}
		},

		/**
		 * Selected/deselects the given file element and updated
		 * the internal selection cache.
		 *
		 * @param $tr single file row element
		 * @param state true to select, false to deselect
		 */
		_selectFileEl: function($tr, state) {
			var $checkbox = $tr.find('td.filename>input:checkbox');
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
			this.$el.find('.select-all').prop('checked', this._selectionSummary.getTotal() === this.files.length);
		},

		/**
		 * Event handler for when clicking on files to select them
		 */
		_onClickFile: function(event) {
			var $tr = $(event.target).closest('tr');
			if (event.ctrlKey || event.shiftKey) {
				event.preventDefault();
				if (event.shiftKey) {
					var $lastTr = $(this._lastChecked);
					var lastIndex = $lastTr.index();
					var currentIndex = $tr.index();
					var $rows = this.$fileList.children('tr');

					// last clicked checkbox below current one ?
					if (lastIndex > currentIndex) {
						var aux = lastIndex;
						lastIndex = currentIndex;
						currentIndex = aux;
					}

					// auto-select everything in-between
					for (var i = lastIndex + 1; i < currentIndex; i++) {
						this._selectFileEl($rows.eq(i), true);
					}
				}
				else {
					this._lastChecked = $tr;
				}
				var $checkbox = $tr.find('td.filename>input:checkbox');
				this._selectFileEl($tr, !$checkbox.prop('checked'));
				this.updateSelectionSummary();
			} else {
				var filename = $tr.attr('data-file');
				var renaming = $tr.data('renaming');
				if (!renaming) {
					this.fileActions.currentFile = $tr.find('td');
					var mime = this.fileActions.getCurrentMimeType();
					var type = this.fileActions.getCurrentType();
					var permissions = this.fileActions.getCurrentPermissions();
					var action = this.fileActions.getDefault(mime,type, permissions);
					if (action) {
						event.preventDefault();
						// also set on global object for legacy apps
						window.FileActions.currentFile = this.fileActions.currentFile;
						action(filename, {
							$file: $tr,
							fileList: this,
							fileActions: this.fileActions,
							dir: $tr.attr('data-path') || this.getCurrentDirectory()
						});
					}
				}
			}
		},

		/**
		 * Event handler for when clicking on a file's checkbox
		 */
		_onClickFileCheckbox: function(e) {
			var $tr = $(e.target).closest('tr');
			this._selectFileEl($tr, !$tr.hasClass('selected'));
			this._lastChecked = $tr;
			this.updateSelectionSummary();
		},

		/**
		 * Event handler for when selecting/deselecting all files
		 */
		_onClickSelectAll: function(e) {
			var checked = $(e.target).prop('checked');
			this.$fileList.find('td.filename>input:checkbox').prop('checked', checked)
				.closest('tr').toggleClass('selected', checked);
			this._selectedFiles = {};
			this._selectionSummary.clear();
			if (checked) {
				for (var i = 0; i < this.files.length; i++) {
					var fileData = this.files[i];
					this._selectedFiles[fileData.id] = fileData;
					this._selectionSummary.add(fileData);
				}
			}
			this.updateSelectionSummary();
		},

		/**
		 * Event handler for when clicking on "Download" for the selected files
		 */
		_onClickDownloadSelected: function(event) {
			var files;
			var dir = this.getCurrentDirectory();
			if (this.isAllSelected()) {
				files = OC.basename(dir);
				dir = OC.dirname(dir) || '/';
			}
			else {
				files = _.pluck(this.getSelectedFiles(), 'name');
			}
			OC.redirect(this.getDownloadUrl(files, dir));
			return false;
		},

		/**
		 * Event handler for when clicking on "Delete" for the selected files
		 */
		_onClickDeleteSelected: function(event) {
			var files = null;
			if (!this.isAllSelected()) {
				files = _.pluck(this.getSelectedFiles(), 'name');
			}
			this.do_delete(files);
			event.preventDefault();
			return false;
		},

		/**
		 * Event handler when clicking on a table header
		 */
		_onClickHeader: function(e) {
			var $target = $(e.target);
			var sort;
			if (!$target.is('a')) {
				$target = $target.closest('a');
			}
			sort = $target.attr('data-sort');
			if (sort) {
				if (this._sort === sort) {
					this.setSort(sort, (this._sortDirection === 'desc')?'asc':'desc', true);
				}
				else {
					if ( sort === 'name' ) {	//default sorting of name is opposite to size and mtime
						this.setSort(sort, 'asc', true);
					}
					else {
						this.setSort(sort, 'desc', true);
					}
				}
			}
		},

		/**
		 * Event handler when clicking on a bread crumb
		 */
		_onClickBreadCrumb: function(e) {
			var $el = $(e.target).closest('.crumb'),
				$targetDir = $el.data('dir');

			if ($targetDir !== undefined) {
				e.preventDefault();
				this.changeDirectory($targetDir);
			}
		},

		/**
		 * Event handler for when scrolling the list container.
		 * This appends/renders the next page of entries when reaching the bottom.
		 */
		_onScroll: function(e) {
			if (this.$container.scrollTop() + this.$container.height() > this.$el.height() - 300) {
				this._nextPage(true);
			}
		},

		/**
		 * Event handler when dropping on a breadcrumb
		 */
		_onDropOnBreadCrumb: function( event, ui ) {
			var $target = $(event.target);
			if (!$target.is('.crumb')) {
				$target = $target.closest('.crumb');
			}
			var targetPath = $(event.target).data('dir');
			var dir = this.getCurrentDirectory();
			while (dir.substr(0,1) === '/') {//remove extra leading /'s
				dir = dir.substr(1);
			}
			dir = '/' + dir;
			if (dir.substr(-1,1) !== '/') {
				dir = dir + '/';
			}
			// do nothing if dragged on current dir
			if (targetPath === dir || targetPath + '/' === dir) {
				return;
			}

			var files = this.getSelectedFiles();
			if (files.length === 0) {
				// single one selected without checkbox?
				files = _.map(ui.helper.find('tr'), this.elementToFile);
			}

			this.move(_.pluck(files, 'name'), targetPath);
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
			title += this.appName;
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
			$el = $($el);
			return {
				id: parseInt($el.attr('data-id'), 10),
				name: $el.attr('data-file'),
				mimetype: $el.attr('data-mime'),
				type: $el.attr('data-type'),
				size: parseInt($el.attr('data-size'), 10),
				etag: $el.attr('data-etag')
			};
		},

		/**
		 * Appends the next page of files into the table
		 * @param animate true to animate the new elements
		 * @return array of DOM elements of the newly added files
		 */
		_nextPage: function(animate) {
			var index = this.$fileList.children().length,
				count = this.pageSize,
				tr,
				fileData,
				newTrs = [],
				isAllSelected = this.isAllSelected();

			if (index >= this.files.length) {
				return false;
			}

			while (count > 0 && index < this.files.length) {
				fileData = this.files[index];
				tr = this._renderRow(fileData, {updateSummary: false, silent: true});
				this.$fileList.append(tr);
				if (isAllSelected || this._selectedFiles[fileData.id]) {
					tr.addClass('selected');
					tr.find('input:checkbox').prop('checked', true);
				}
				if (animate) {
					tr.addClass('appear transparent');
				}
				newTrs.push(tr);
				index++;
				count--;
			}

			// trigger event for newly added rows
			if (newTrs.length > 0) {
				this.$fileList.trigger($.Event('fileActionsReady', {fileList: this, $files: newTrs}));
			}

			if (animate) {
				// defer, for animation
				window.setTimeout(function() {
					for (var i = 0; i < newTrs.length; i++ ) {
						newTrs[i].removeClass('transparent');
					}
				}, 0);
			}
			return newTrs;
		},

		/**
		 * Event handler for when file actions were updated.
		 * This will refresh the file actions on the list.
		 */
		_onFileActionsUpdated: function() {
			var self = this;
			var $files = this.$fileList.find('tr');
			if (!$files.length) {
				return;
			}

			$files.each(function() {
				self.fileActions.display($(this).find('td.filename'), false, self);
			});
			this.$fileList.trigger($.Event('fileActionsReady', {fileList: this, $files: $files}));

		},

		/**
		 * Sets the files to be displayed in the list.
		 * This operation will re-render the list and update the summary.
		 * @param filesArray array of file data (map)
		 */
		setFiles: function(filesArray) {
			// detach to make adding multiple rows faster
			this.files = filesArray;

			this.$fileList.empty();

			// clear "Select all" checkbox
			this.$el.find('.select-all').prop('checked', false);

			this.isEmpty = this.files.length === 0;
			this._nextPage();

			this.updateEmptyContent();

			this.fileSummary.calculate(filesArray);

			this._selectedFiles = {};
			this._selectionSummary.clear();
			this.updateSelectionSummary();
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
				path = fileData.path,
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

			if (fileData.mountType) {
				tr.attr('data-mounttype', fileData.mountType);
			}

			if (!_.isUndefined(path)) {
				tr.attr('data-path', path);
			}
			else {
				path = this.getCurrentDirectory();
			}

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
				linkUrl = this.linkTo(path + '/' + name);
			}
			else {
				linkUrl = this.getDownloadUrl(name, path);
			}
			td.append('<input id="select-' + this.id + '-' + fileData.id +
				'" type="checkbox" /><label for="select-' + this.id + '-' + fileData.id + '"></label>');
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
			var nameSpan=$('<span></span>').addClass('nametext');
			var innernameSpan = $('<span></span>').addClass('innernametext').text(basename);
			nameSpan.append(innernameSpan);
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
				simpleSize = humanFileSize(parseInt(fileData.size, 10), true);
				sizeColor = Math.round(160-Math.pow((fileData.size/(1024*1024)),2));
			} else {
				simpleSize = t('files', 'Pending');
			}

			td = $('<td></td>').attr({
				"class": "filesize",
				"style": 'color:rgb(' + sizeColor + ',' + sizeColor + ',' + sizeColor + ')'
			}).text(simpleSize);
			tr.append(td);

			// date column (1000 milliseconds to seconds, 60 seconds, 60 minutes, 24 hours)
			// difference in days multiplied by 5 - brightest shade for files older than 32 days (160/5)
			var modifiedColor = Math.round(((new Date()).getTime() - mtime )/1000/60/60/24*5 );
			// ensure that the brightest color is still readable
			if (modifiedColor >= '160') {
				modifiedColor = 160;
			}
			td = $('<td></td>').attr({ "class": "date" });
			td.append($('<span></span>').attr({
				"class": "modified",
				"title": formatDate(mtime),
				"style": 'color:rgb('+modifiedColor+','+modifiedColor+','+modifiedColor+')'
			}).text(OC.Util.relativeModifiedDate(mtime)));
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
		 * - "updateSummary": true to update the summary after adding (default), false otherwise
		 * - "silent": true to prevent firing events like "fileActionsReady"
		 * - "animate": true to animate preview loading (defaults to true here)
		 * @return new tr element (not appended to the table)
		 */
		add: function(fileData, options) {
			var index = -1;
			var $tr;
			var $rows;
			var $insertionPoint;
			options = _.extend({animate: true}, options || {});

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
		 * - "animate" true to animate the preview rendering
		 * @return new tr element (not appended to the table)
		 */
		_renderRow: function(fileData, options) {
			options = options || {};
			var type = fileData.type || 'file',
				mime = fileData.mimetype,
				path = fileData.path || this.getCurrentDirectory(),
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
			if (this._dragOptions && permissions & OC.PERMISSION_DELETE) {
				filenameTd.draggable(this._dragOptions);
			}
			// allow dropping on folders
			if (this._folderDropOptions && fileData.type === 'dir') {
				filenameTd.droppable(this._folderDropOptions);
			}

			if (options.hidden) {
				tr.addClass('hidden');
			}

			// display actions
			this.fileActions.display(filenameTd, !options.silent, this);

			if (fileData.isPreviewAvailable) {
				// lazy load / newly inserted td ?
				if (options.animate) {
					this.lazyLoadPreview({
						path: path + '/' + fileData.name,
						mime: mime,
						etag: fileData.etag,
						callback: function(url) {
							filenameTd.css('background-image', 'url(' + url + ')');
						}
					});
				}
				else {
					// set the preview URL directly
					var urlSpec = {
							file: path + '/' + fileData.name,
							c: fileData.etag
						};
					var previewUrl = this.generatePreviewUrl(urlSpec);
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
			return this._currentDirectory || this.$el.find('#dir').val() || '/';
		},
		/**
		 * Returns the directory permissions
		 * @return permission value as integer
		 */
		getDirectoryPermissions: function() {
			return parseInt(this.$el.find('#permissions').val(), 10);
		},
		/**
		 * @brief Changes the current directory and reload the file list.
		 * @param targetDir target directory (non URL encoded)
		 * @param changeUrl false if the URL must not be changed (defaults to true)
		 * @param {boolean} force set to true to force changing directory
		 */
		changeDirectory: function(targetDir, changeUrl, force) {
			var self = this;
			var currentDir = this.getCurrentDirectory();
			targetDir = targetDir || '/';
			if (!force && currentDir === targetDir) {
				return;
			}
			this._setCurrentDir(targetDir, changeUrl);
			this.reload().then(function(success){
				if (!success) {
					self.changeDirectory(currentDir, true);
				}
			});
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
			var previousDir = this.getCurrentDirectory(),
				baseDir = OC.basename(targetDir);

			if (baseDir !== '') {
				this.setPageTitle(baseDir);
			}
			else {
				this.setPageTitle();
			}

			this._currentDirectory = targetDir;

			// legacy stuff
			this.$el.find('#dir').val(targetDir);

			if (changeUrl !== false) {
				this.$el.trigger(jQuery.Event('changeDirectory', {
					dir: targetDir,
					previousDir: previousDir
				}));
			}
			this.breadcrumb.setDirectory(this.getCurrentDirectory());
		},
		/**
		 * Sets the current sorting and refreshes the list
		 *
		 * @param sort sort attribute name
		 * @param direction sort direction, one of "asc" or "desc"
		 * @param update true to update the list, false otherwise (default)
		 */
		setSort: function(sort, direction, update) {
			var comparator = FileList.Comparators[sort] || FileList.Comparators.name;
			this._sort = sort;
			this._sortDirection = (direction === 'desc')?'desc':'asc';
			this._sortComparator = comparator;

			if (direction === 'desc') {
				this._sortComparator = function(fileInfo1, fileInfo2) {
					return -comparator(fileInfo1, fileInfo2);
				};
			}
			this.$el.find('thead th .sort-indicator')
				.removeClass(this.SORT_INDICATOR_ASC_CLASS)
				.removeClass(this.SORT_INDICATOR_DESC_CLASS)
				.toggleClass('hidden', true)
				.addClass(this.SORT_INDICATOR_DESC_CLASS);

			this.$el.find('thead th.column-' + sort + ' .sort-indicator')
				.removeClass(this.SORT_INDICATOR_ASC_CLASS)
				.removeClass(this.SORT_INDICATOR_DESC_CLASS)
				.toggleClass('hidden', false)
				.addClass(direction === 'desc' ? this.SORT_INDICATOR_DESC_CLASS : this.SORT_INDICATOR_ASC_CLASS);
			if (update) {
				if (this._clientSideSort) {
					this.files.sort(this._sortComparator);
					this.setFiles(this.files);
				}
				else {
					this.reload();
				}
			}
		},

		/**
		 * Reloads the file list using ajax call
		 *
		 * @return ajax call object
		 */
		reload: function() {
			this._selectedFiles = {};
			this._selectionSummary.clear();
			this.$el.find('.select-all').prop('checked', false);
			this.showMask();
			if (this._reloadCall) {
				this._reloadCall.abort();
			}
			this._reloadCall = $.ajax({
				url: this.getAjaxUrl('list'),
				data: {
					dir : this.getCurrentDirectory(),
					sort: this._sort,
					sortdirection: this._sortDirection
				}
			});
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack, callBack);
		},
		reloadCallback: function(result) {
			delete this._reloadCall;
			this.hideMask();

			if (!result || result.status === 'error') {
				// if the error is not related to folder we're trying to load, reload the page to handle logout etc
				if (result.data.error === 'authentication_error' ||
					result.data.error === 'token_expired' ||
					result.data.error === 'application_not_enabled'
				) {
					OC.redirect(OC.generateUrl('apps/files'));
				}
				OC.Notification.show(result.data.message);
				return false;
			}

			if (result.status === 404) {
				// go back home
				this.changeDirectory('/');
				return false;
			}
			// aborted ?
			if (result.status === 0){
				return true;
			}

			// TODO: should rather return upload file size through
			// the files list ajax call
			this.updateStorageStatistics(true);

			if (result.data.permissions) {
				this.setDirectoryPermissions(result.data.permissions);
			}

			this.setFiles(result.data.files);
			return true;
		},

		updateStorageStatistics: function(force) {
			OCA.Files.Files.updateStorageStatistics(this.getCurrentDirectory(), force);
		},

		getAjaxUrl: function(action, params) {
			return OCA.Files.Files.getAjaxUrl(action, params);
		},

		getDownloadUrl: function(files, dir) {
			return OCA.Files.Files.getDownloadUrl(files, dir || this.getCurrentDirectory());
		},

		/**
		 * Generates a preview URL based on the URL space.
		 * @param urlSpec map with {x: width, y: height, file: file path}
		 * @return preview URL
		 */
		generatePreviewUrl: function(urlSpec) {
			urlSpec = urlSpec || {};
			if (!urlSpec.x) {
				urlSpec.x = this.$table.data('preview-x') || 36;
			}
			if (!urlSpec.y) {
				urlSpec.y = this.$table.data('preview-y') || 36;
			}
			urlSpec.y *= window.devicePixelRatio;
			urlSpec.x *= window.devicePixelRatio;
			urlSpec.forceIcon = 0;
			return OC.generateUrl('/core/preview.png?') + $.param(urlSpec);
		},

		/**
		 * Lazy load a file's preview.
		 *
		 * @param path path of the file
		 * @param mime mime type
		 * @param callback callback function to call when the image was loaded
		 * @param etag file etag (for caching)
		 */
		lazyLoadPreview : function(options) {
			var self = this;
			var path = options.path;
			var mime = options.mime;
			var ready = options.callback;
			var etag = options.etag;

			// get mime icon url
			OCA.Files.Files.getMimeIcon(mime, function(iconURL) {
				var previewURL,
					urlSpec = {};
				ready(iconURL); // set mimeicon URL

				urlSpec.file = OCA.Files.Files.fixPath(path);

				if (etag){
					// use etag as cache buster
					urlSpec.c = etag;
				}
				else {
					console.warn('OCA.Files.FileList.lazyLoadPreview(): missing etag argument');
				}

				previewURL = self.generatePreviewUrl(urlSpec);
				previewURL = previewURL.replace('(', '%28');
				previewURL = previewURL.replace(')', '%29');

				// preload image to prevent delay
				// this will make the browser cache the image
				var img = new Image();
				img.onload = function(){
					// if loading the preview image failed (no preview for the mimetype) then img.width will < 5
					if (img.width > 5) {
						ready(previewURL);
					}
				};
				img.src = previewURL;
			});
		},

		setDirectoryPermissions: function(permissions) {
			var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
			this.$el.find('#permissions').val(permissions);
			this.$el.find('.creatable').toggleClass('hidden', !isCreatable);
			this.$el.find('.notCreatable').toggleClass('hidden', isCreatable);
		},
		/**
		 * Shows/hides action buttons
		 *
		 * @param show true for enabling, false for disabling
		 */
		showActions: function(show){
			this.$el.find('.actions,#file_action_panel').toggleClass('hidden', !show);
			if (show){
				// make sure to display according to permissions
				var permissions = this.getDirectoryPermissions();
				var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
				this.$el.find('.creatable').toggleClass('hidden', !isCreatable);
				this.$el.find('.notCreatable').toggleClass('hidden', isCreatable);
				// remove old style breadcrumbs (some apps might create them)
				this.$el.find('#controls .crumb').remove();
				// refresh breadcrumbs in case it was replaced by an app
				this.breadcrumb.render();
			}
			else{
				this.$el.find('.creatable, .notCreatable').addClass('hidden');
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
			this.$el.find('#filestable').toggleClass('hidden', show);
			this.$el.trigger(new $.Event('changeViewerMode', {viewerModeEnabled: show}));
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
			var fileEl = this.findFileEl(name);
			var index = fileEl.index();
			if (!fileEl.length) {
				return null;
			}
			if (this._selectedFiles[fileEl.data('id')]) {
				// remove from selection first
				this._selectFileEl(fileEl, false);
				this.updateSelectionSummary();
			}
			if (this._dragOptions && (fileEl.data('permissions') & OC.PERMISSION_DELETE)) {
				// file is only draggable when delete permissions are set
				fileEl.find('td.filename').draggable('destroy');
			}
			this.files.splice(index, 1);
			fileEl.remove();
			// TODO: improve performance on batch update
			this.isEmpty = !this.files.length;
			if (typeof(options.updateSummary) === 'undefined' || !!options.updateSummary) {
				this.updateEmptyContent();
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
			while (index < this.files.length && this._sortComparator(fileData, this.files[index]) > 0) {
				index++;
			}
			return index;
		},
		/**
		 * Moves a file to a given target folder.
		 *
		 * @param fileNames array of file names to move
		 * @param targetPath absolute target path
		 */
		move: function(fileNames, targetPath) {
			var self = this;
			var dir = this.getCurrentDirectory();
			var target = OC.basename(targetPath);
			if (!_.isArray(fileNames)) {
				fileNames = [fileNames];
			}
			_.each(fileNames, function(fileName) {
				var $tr = self.findFileEl(fileName);
				var $td = $tr.children('td.filename');
				var oldBackgroundImage = $td.css('background-image');
				$td.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
				// TODO: improve performance by sending all file names in a single call
				$.post(
					OC.filePath('files', 'ajax', 'move.php'),
					{
						dir: dir,
						file: fileName,
						target: targetPath
					},
					function(result) {
						if (result) {
							if (result.status === 'success') {
								// if still viewing the same directory
								if (self.getCurrentDirectory() === dir) {
									// recalculate folder size
									var oldFile = self.findFileEl(target);
									var newFile = self.findFileEl(fileName);
									var oldSize = oldFile.data('size');
									var newSize = oldSize + newFile.data('size');
									oldFile.data('size', newSize);
									oldFile.find('td.filesize').text(OC.Util.humanFileSize(newSize));

									// TODO: also update entry in FileList.files

									self.remove(fileName);
								}
							} else {
								OC.Notification.hide();
								if (result.status === 'error' && result.data.message) {
									OC.Notification.show(result.data.message);
								}
								else {
									OC.Notification.show(t('files', 'Error moving file.'));
								}
								// hide notification after 10 sec
								setTimeout(function() {
									OC.Notification.hide();
								}, 10000);
							}
						} else {
							OC.dialogs.alert(t('files', 'Error moving file'), t('files', 'Error'));
						}
						$td.css('background-image', oldBackgroundImage);
					}
				);
			});

		},

		/**
		 * Triggers file rename input field for the given file name.
		 * If the user enters a new name, the file will be renamed.
		 *
		 * @param oldname file name of the file to rename
		 */
		rename: function(oldname) {
			var self = this;
			var tr, td, input, form;
			tr = this.findFileEl(oldname);
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
					OCA.Files.Files.isFileNameValid(filename);
					if (self.inList(filename)) {
						throw t('files', '{new_name} already exists', {new_name: filename});
					}
				}
				return true;
			};

			function restore() {
				input.tipsy('hide');
				tr.data('renaming',false);
				form.remove();
				td.children('a.name').show();
			}

			form.submit(function(event) {
				event.stopPropagation();
				event.preventDefault();
				if (input.hasClass('error')) {
					return;
				}

				try {
					var newName = input.val();
					input.tipsy('hide');
					form.remove();

					if (newName !== oldname) {
						checkInput();
						// mark as loading (temp element)
						td.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
						tr.attr('data-file', newName);
						var basename = newName;
						if (newName.indexOf('.') > 0 && tr.data('type') !== 'dir') {
							basename = newName.substr(0, newName.lastIndexOf('.'));
						}
						td.find('a.name span.nametext').text(basename);
						td.children('a.name').show();
						tr.find('.fileactions, .action').addClass('hidden');

						$.ajax({
							url: OC.filePath('files','ajax','rename.php'),
							data: {
								dir : tr.attr('data-path') || self.getCurrentDirectory(),
								newname: newName,
								file: oldname
							},
							success: function(result) {
								var fileInfo;
								if (!result || result.status === 'error') {
									OC.dialogs.alert(result.data.message, t('files', 'Could not rename file'));
									fileInfo = oldFileInfo;
									if (result.data.code === 'sourcenotfound') {
										self.remove(result.data.newname, {updateSummary: true});
										return;
									}
								}
								else {
									fileInfo = result.data;
								}
								// reinsert row
								self.files.splice(tr.index(), 1);
								tr.remove();
								tr = self.add(fileInfo, {updateSummary: false, silent: true});
								self.$fileList.trigger($.Event('fileActionsReady', {fileList: self, $files: $(tr)}));
							}
						});
					} else {
						// add back the old file info when cancelled
						self.files.splice(tr.index(), 1);
						tr.remove();
						tr = self.add(oldFileInfo, {updateSummary: false, silent: true});
						self.$fileList.trigger($.Event('fileActionsReady', {fileList: self, $files: $(tr)}));
					}
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
					restore();
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
			return this.findFileEl(file).length;
		},
		/**
		 * Delete the given files from the given dir
		 * @param files file names list (without path)
		 * @param dir directory in which to delete the files, defaults to the current
		 * directory
		 */
		do_delete:function(files, dir) {
			var self = this;
			var params;
			if (files && files.substr) {
				files=[files];
			}
			if (files) {
				for (var i=0; i<files.length; i++) {
					var deleteAction = this.findFileEl(files[i]).children("td.date").children(".action.delete");
					deleteAction.removeClass('delete-icon').addClass('progress-icon');
				}
			}
			// Finish any existing actions
			if (this.lastAction) {
				this.lastAction();
			}

			params = {
				dir: dir || this.getCurrentDirectory()
			};
			if (files) {
				params.files = JSON.stringify(files);
			}
			else {
				// no files passed, delete all in current dir
				params.allfiles = true;
				// show spinner for all files
				this.$fileList.find('tr>td.date .action.delete').removeClass('delete-icon').addClass('progress-icon');
			}

			$.post(OC.filePath('files', 'ajax', 'delete.php'),
					params,
					function(result) {
						if (result.status === 'success') {
							if (params.allfiles) {
								self.setFiles([]);
							}
							else {
								$.each(files,function(index,file) {
									var fileEl = self.remove(file, {updateSummary: false});
									// FIXME: not sure why we need this after the
									// element isn't even in the DOM any more
									fileEl.find('input[type="checkbox"]').prop('checked', false);
									fileEl.removeClass('selected');
									self.fileSummary.remove({type: fileEl.attr('data-type'), size: fileEl.attr('data-size')});
								});
							}
							// TODO: this info should be returned by the ajax call!
							self.updateEmptyContent();
							self.fileSummary.update();
							self.updateSelectionSummary();
							self.updateStorageStatistics();
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
								self.reload();
							}
							else {
								$.each(files,function(index,file) {
									var deleteAction = self.findFileEl(file).find('.action.delete');
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

			return new OCA.Files.FileSummary($tr);
		},
		updateEmptyContent: function() {
			var permissions = this.getDirectoryPermissions();
			var isCreatable = (permissions & OC.PERMISSION_CREATE) !== 0;
			this.$el.find('#emptycontent').toggleClass('hidden', !isCreatable || !this.isEmpty);
			this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
		},
		/**
		 * Shows the loading mask.
		 *
		 * @see #hideMask
		 */
		showMask: function() {
			// in case one was shown before
			var $mask = this.$el.find('.mask');
			if ($mask.exists()) {
				return;
			}

			this.$table.addClass('hidden');

			$mask = $('<div class="mask transparent"></div>');

			$mask.css('background-image', 'url('+ OC.imagePath('core', 'loading.gif') + ')');
			$mask.css('background-repeat', 'no-repeat');
			this.$el.append($mask);

			$mask.removeClass('transparent');
		},
		/**
		 * Hide the loading mask.
		 * @see #showMask
		 */
		hideMask: function() {
			this.$el.find('.mask').remove();
			this.$table.removeClass('hidden');
		},
		scrollTo:function(file) {
			//scroll to and highlight preselected file
			var $scrollToRow = this.findFileEl(file);
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
			this.$fileList.find('tr').each(function(i,e) {
				if ($(e).data('file').toString().toLowerCase().indexOf(query.toLowerCase()) !== -1) {
					$(e).addClass("searchresult");
				} else {
					$(e).removeClass("searchresult");
				}
			});
			//do not use scrollto to prevent removing searchresult css class
			var first = this.$fileList.find('tr.searchresult').first();
			if (first.exists()) {
				$(window).scrollTop(first.position().top);
			}
		},
		unfilter:function() {
			this.$fileList.find('tr.searchresult').each(function(i,e) {
				$(e).removeClass("searchresult");
			});
		},
		/**
		 * Update UI based on the current selection
		 */
		updateSelectionSummary: function() {
			var summary = this._selectionSummary.summary;
			var canDelete;
			if (summary.totalFiles === 0 && summary.totalDirs === 0) {
				this.$el.find('#headerName a.name>span:first').text(t('files','Name'));
				this.$el.find('#headerSize a>span:first').text(t('files','Size'));
				this.$el.find('#modified a>span:first').text(t('files','Modified'));
				this.$el.find('table').removeClass('multiselect');
				this.$el.find('.selectedActions').addClass('hidden');
			}
			else {
				canDelete = (this.getDirectoryPermissions() & OC.PERMISSION_DELETE);
				this.$el.find('.selectedActions').removeClass('hidden');
				this.$el.find('#headerSize a>span:first').text(OC.Util.humanFileSize(summary.totalSize));
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
				this.$el.find('#headerName a.name>span:first').text(selection);
				this.$el.find('#modified a>span:first').text('');
				this.$el.find('table').addClass('multiselect');
				this.$el.find('.delete-selected').toggleClass('hidden', !canDelete);
			}
		},

		/**
		 * Returns whether all files are selected
		 * @return true if all files are selected, false otherwise
		 */
		isAllSelected: function() {
			return this.$el.find('.select-all').prop('checked');
		},

		/**
		 * Returns the file info of the selected files
		 *
		 * @return array of file names
		 */
		getSelectedFiles: function() {
			return _.values(this._selectedFiles);
		},

		getUniqueName: function(name) {
			if (this.findFileEl(name).exists()) {
				var numMatch;
				var parts=name.split('.');
				var extension = "";
				if (parts.length > 1) {
					extension=parts.pop();
				}
				var base=parts.join('.');
				numMatch=base.match(/\((\d+)\)/);
				var num=2;
				if (numMatch && numMatch.length>0) {
					num=parseInt(numMatch[numMatch.length-1], 10)+1;
					base=base.split('(');
					base.pop();
					base=$.trim(base.join('('));
				}
				name=base+' ('+num+')';
				if (extension) {
					name = name+'.'+extension;
				}
				// FIXME: ugly recursion
				return this.getUniqueName(name);
			}
			return name;
		},

		/**
		 * Setup file upload events related to the file-upload plugin
		 */
		setupUploadEvents: function() {
			var self = this;

			// handle upload events
			var fileUploadStart = this.$el.find('#file_upload_start');

			// detect the progress bar resize
			fileUploadStart.on('resized', this._onResize);

			fileUploadStart.on('fileuploaddrop', function(e, data) {
				OC.Upload.log('filelist handle fileuploaddrop', e, data);

				var dropTarget = $(e.originalEvent.target);
				// check if dropped inside this container and not another one
				if (dropTarget.length && !self.$el.is(dropTarget) && !self.$el.has(dropTarget).length) {
					return false;
				}

				// find the closest tr or crumb to use as target
				dropTarget = dropTarget.closest('tr, .crumb');

				// if dropping on tr or crumb, drag&drop upload to folder
				if (dropTarget && (dropTarget.data('type') === 'dir' ||
					dropTarget.hasClass('crumb'))) {

					// remember as context
					data.context = dropTarget;

					var dir = dropTarget.data('file');
					// if from file list, need to prepend parent dir
					if (dir) {
						var parentDir = self.getCurrentDirectory();
						if (parentDir[parentDir.length - 1] !== '/') {
							parentDir += '/';
						}
						dir = parentDir + dir;
					}
					else{
						// read full path from crumb
						dir = dropTarget.data('dir') || '/';
					}

					// add target dir
					data.targetDir = dir;
				} else {
					// we are dropping somewhere inside the file list, which will
					// upload the file to the current directory
					data.targetDir = self.getCurrentDirectory();

					// cancel uploads to current dir if no permission
					var isCreatable = (self.getDirectoryPermissions() & OC.PERMISSION_CREATE) !== 0;
					if (!isCreatable) {
						return false;
					}
				}
			});
			fileUploadStart.on('fileuploadadd', function(e, data) {
				OC.Upload.log('filelist handle fileuploadadd', e, data);

				//finish delete if we are uploading a deleted file
				if (self.deleteFiles && self.deleteFiles.indexOf(data.files[0].name)!==-1) {
					self.finishDelete(null, true); //delete file before continuing
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
						if (file.directory !== self.getCurrentDirectory()) {
							// Uploading folders actually uploads a list of files
							// for which the target directory (file.directory) might lie deeper
							// than the current directory

							var fileDirectory = file.directory.replace('/','').replace(/\/$/, "");
							var currentDirectory = self.getCurrentDirectory().replace('/','').replace(/\/$/, "") + '/';

							if (currentDirectory !== '/') {
								// abort if fileDirectory does not start with current one
								if (fileDirectory.indexOf(currentDirectory) !== 0) {
									return;
								}

								// remove the current directory part
								fileDirectory = fileDirectory.substr(currentDirectory.length);
							}

							// only take the first section of the path
							fileDirectory = fileDirectory.split('/');

							var fd;
							// if the first section exists / is a subdir
							if (fileDirectory.length) {
								fileDirectory = fileDirectory[0];

								// See whether it is already in the list
								fd = self.findFileEl(fileDirectory);
								if (fd.length === 0) {
									var dir = {
										name: fileDirectory,
										type: 'dir',
										mimetype: 'httpd/unix-directory',
										permissions: file.permissions,
										size: 0,
										id: file.parentId
									};
									fd = self.add(dir, {insert: true});
								}

								// update folder size
								size = parseInt(fd.attr('data-size'), 10);
								size += parseInt(file.size, 10);
								fd.attr('data-size', size);
								fd.find('td.filesize').text(OC.Util.humanFileSize(size));
							}

							return;
						}

						// add as stand-alone row to filelist
						size = t('files', 'Pending');
						if (data.files[0].size>=0) {
							size=data.files[0].size;
						}
						//should the file exist in the list remove it
						self.remove(file.name);

						// create new file context
						data.context = self.add(file, {animate: true});
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
				self.updateStorageStatistics();
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
				self.updateStorageStatistics();
			});

		}
	};

	/**
	 * Sort comparators.
	 */
	FileList.Comparators = {
		/**
		 * Compares two file infos by name, making directories appear
		 * first.
		 *
		 * @param fileInfo1 file info
		 * @param fileInfo2 file info
		 * @return -1 if the first file must appear before the second one,
		 * 0 if they are identify, 1 otherwise.
		 */
		name: function(fileInfo1, fileInfo2) {
			if (fileInfo1.type === 'dir' && fileInfo2.type !== 'dir') {
				return -1;
			}
			if (fileInfo1.type !== 'dir' && fileInfo2.type === 'dir') {
				return 1;
			}
			return fileInfo1.name.localeCompare(fileInfo2.name);
		},
		/**
		 * Compares two file infos by size.
		 *
		 * @param fileInfo1 file info
		 * @param fileInfo2 file info
		 * @return -1 if the first file must appear before the second one,
		 * 0 if they are identify, 1 otherwise.
		 */
		size: function(fileInfo1, fileInfo2) {
			return fileInfo1.size - fileInfo2.size;
		},
		/**
		 * Compares two file infos by timestamp.
		 *
		 * @param fileInfo1 file info
		 * @param fileInfo2 file info
		 * @return -1 if the first file must appear before the second one,
		 * 0 if they are identify, 1 otherwise.
		 */
		mtime: function(fileInfo1, fileInfo2) {
			return fileInfo1.mtime - fileInfo2.mtime;
		}
	};

	OCA.Files.FileList = FileList;
})();

$(document).ready(function() {
	// FIXME: unused ?
	OCA.Files.FileList.useUndo = (window.onbeforeunload)?true:false;
	$(window).bind('beforeunload', function () {
		if (OCA.Files.FileList.lastAction) {
			OCA.Files.FileList.lastAction();
		}
	});
	$(window).unload(function () {
		$(window).trigger('beforeunload');
	});

});
