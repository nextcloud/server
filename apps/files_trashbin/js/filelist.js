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
	var DELETED_REGEXP = new RegExp(/^(.+)\.d[0-9]+$/);
	var FILENAME_PROP = '{http://nextcloud.org/ns}trashbin-filename';
	var DELETION_TIME_PROP = '{http://nextcloud.org/ns}trashbin-deletion-time';

	/**
	 * Convert a file name in the format filename.d12345 to the real file name.
	 * This will use basename.
	 * The name will not be changed if it has no ".d12345" suffix.
	 * @param {String} name file name
	 * @return {String} converted file name
	 */
	function getDeletedFileName(name) {
		name = OC.basename(name);
		var match = DELETED_REGEXP.exec(name);
		if (match && match.length > 1) {
			name = match[1];
		}
		return name;
	}

	/**
	 * @class OCA.Trashbin.FileList
	 * @augments OCA.Files.FileList
	 * @classdesc List of deleted files
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.Trashbin.FileList.prototype */ {
		id: 'trashbin',
		appName: t('files_trashbin', 'Deleted files'),
		/** @type {OC.Files.Client} */
		client: null,

		/**
		 * @private
		 */
		initialize: function() {
			this.client = new OC.Files.Client({
				host: OC.getHost(),
				port: OC.getPort(),
				root: OC.linkToRemoteBase('dav') + '/trashbin/' + OC.getCurrentUser().uid,
				useHTTPS: OC.getProtocol() === 'https'
			});
			this.client.addFileInfoParser(function(response, data) {
				var props = response.propStat[0].properties;
				return {
					displayName: props[FILENAME_PROP],
					mtime: parseInt(props[DELETION_TIME_PROP], 10) * 1000,
					hasPreview: true,
					path: data.path.substr(6), // remove leading /trash
				}
			});

			var result = OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			this.$el.find('.undelete').click('click', _.bind(this._onClickRestoreSelected, this));

			this.setSort('mtime', 'desc');
			/**
			 * Override crumb making to add "Deleted Files" entry
			 * and convert files with ".d" extensions to a more
			 * user friendly name.
			 */
			this.breadcrumb._makeCrumbs = function() {
				var parts = OCA.Files.BreadCrumb.prototype._makeCrumbs.apply(this, arguments);
				for (var i = 1; i < parts.length; i++) {
					parts[i].name = getDeletedFileName(parts[i].name);
				}
				return parts;
			};

			OC.Plugins.attach('OCA.Trashbin.FileList', this);
			return result;
		},

		/**
		 * Override to only return read permissions
		 */
		getDirectoryPermissions: function() {
			return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
		},

		_setCurrentDir: function(targetDir) {
			OCA.Files.FileList.prototype._setCurrentDir.apply(this, arguments);

			var baseDir = OC.basename(targetDir);
			if (baseDir !== '') {
				this.setPageTitle(getDeletedFileName(baseDir));
			}
		},

		_createRow: function() {
			// FIXME: MEGAHACK until we find a better solution
			var tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
			tr.find('td.filesize').remove();
			return tr;
		},

		getAjaxUrl: function(action, params) {
			var q = '';
			if (params) {
				q = '?' + OC.buildQueryString(params);
			}
			return OC.filePath('files_trashbin', 'ajax', action + '.php') + q;
		},

		setupUploadEvents: function() {
			// override and do nothing
		},

		linkTo: function(dir){
			return OC.linkTo('files', 'index.php')+"?view=trashbin&dir="+ encodeURIComponent(dir).replace(/%2F/g, '/');
		},

		elementToFile: function($el) {
			var fileInfo = OCA.Files.FileList.prototype.elementToFile($el);
			if (this.getCurrentDirectory() === '/') {
				fileInfo.displayName = getDeletedFileName(fileInfo.name);
			}
			// no size available
			delete fileInfo.size;
			return fileInfo;
		},

		updateEmptyContent: function(){
			var exists = this.$fileList.find('tr:first').exists();
			this.$el.find('#emptycontent').toggleClass('hidden', exists);
			this.$el.find('#filestable th').toggleClass('hidden', !exists);
		},

		_removeCallback: function(result) {
			if (result.status !== 'success') {
				OC.dialogs.alert(result.data.message, t('files_trashbin', 'Error'));
			}

			var files = result.data.success;
			var $el;
			for (var i = 0; i < files.length; i++) {
				$el = this.remove(OC.basename(files[i].filename), {updateSummary: false});
				this.fileSummary.remove({type: $el.attr('data-type'), size: $el.attr('data-size')});
			}
			this.fileSummary.update();
			this.updateEmptyContent();
		},

		_onClickRestoreSelected: function(event) {
			event.preventDefault();
			var self = this;
			var allFiles = this.$el.find('.select-all').is(':checked');
			var files = [];
			var params = {};
			this.fileMultiSelectMenu.toggleLoading('restore', true);
			if (allFiles) {
				this.showMask();
				params = {
					allfiles: true,
					dir: this.getCurrentDirectory()
				};
			}
			else {
				files = _.pluck(this.getSelectedFiles(), 'name');
				for (var i = 0; i < files.length; i++) {
					var deleteAction = this.findFileEl(files[i]).children("td.date").children(".action.delete");
					deleteAction.removeClass('icon-delete').addClass('icon-loading-small');
				}
				params = {
					files: JSON.stringify(files),
					dir: this.getCurrentDirectory()
				};
			}

			$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'),
				params,
				function(result) {
					if (allFiles) {
						if (result.status !== 'success') {
							OC.dialogs.alert(result.data.message, t('files_trashbin', 'Error'));
						}
						self.hideMask();
						// simply remove all files
						self.setFiles([]);
					}
					else {
						self._removeCallback(result);
					}
					self.fileMultiSelectMenu.toggleLoading('restore', false);
				}
			);
			event.preventDefault();
		},

		_onClickDeleteSelected: function(event) {
			event.preventDefault();
			var self = this;
			var allFiles = this.$el.find('.select-all').is(':checked');
			var files = [];
			var params = {};
			if (allFiles) {
				params = {
					allfiles: true,
					dir: this.getCurrentDirectory()
				};
			}
			else {
				files = _.pluck(this.getSelectedFiles(), 'name');
				params = {
					files: JSON.stringify(files),
					dir: this.getCurrentDirectory()
				};
			}

			this.fileMultiSelectMenu.toggleLoading('delete', true);
			if (allFiles) {
				this.showMask();
			}
			else {
				for (var i = 0; i < files.length; i++) {
					var deleteAction = this.findFileEl(files[i]).children("td.date").children(".action.delete");
					deleteAction.removeClass('icon-delete').addClass('icon-loading-small');
				}
			}

			$.post(OC.filePath('files_trashbin', 'ajax', 'delete.php'),
					params,
					function(result) {
						if (allFiles) {
							if (result.status !== 'success') {
								OC.dialogs.alert(result.data.message, t('files_trashbin', 'Error'));
							}
							self.hideMask();
							// simply remove all files
							self.setFiles([]);
						}
						else {
							self._removeCallback(result);
						}
						self.fileMultiSelectMenu.toggleLoading('delete', false);
					}
			);
		},

		_onClickFile: function(event) {
			var mime = $(this).parent().parent().data('mime');
			if (mime !== 'httpd/unix-directory') {
				event.preventDefault();
			}
			return OCA.Files.FileList.prototype._onClickFile.apply(this, arguments);
		},

		generatePreviewUrl: function(urlSpec) {
			return OC.generateUrl('/apps/files_trashbin/preview?') + $.param(urlSpec);
		},

		getDownloadUrl: function() {
			// no downloads
			return '#';
		},

		updateStorageStatistics: function() {
			// no op because the trashbin doesn't have
			// storage info like free space / used space
		},

		isSelectedDeletable: function() {
			return true;
		},

		/**
		 * Returns list of webdav properties to request
		 */
		_getWebdavProperties: function() {
			return [FILENAME_PROP, DELETION_TIME_PROP].concat(this.filesClient.getPropfindProperties());
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
			this._reloadCall = this.client.getFolderContents(
				'trash/' + this.getCurrentDirectory(), {
					includeParent: false,
					properties: this._getWebdavProperties()
				}
			);
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack, callBack);
		},
		reloadCallback: function(status, result) {
			delete this._reloadCall;
			this.hideMask();

			if (status === 401) {
				return false;
			}

			// Firewall Blocked request?
			if (status === 403) {
				// Go home
				this.changeDirectory('/');
				OC.Notification.show(t('files', 'This operation is forbidden'));
				return false;
			}

			// Did share service die or something else fail?
			if (status === 500) {
				// Go home
				this.changeDirectory('/');
				OC.Notification.show(t('files', 'This directory is unavailable, please check the logs or contact the administrator'));
				return false;
			}

			if (status === 404) {
				// go back home
				this.changeDirectory('/');
				return false;
			}
			// aborted ?
			if (status === 0){
				return true;
			}

			this.setFiles(result);
			return true;
		},

	});

	OCA.Trashbin.FileList = FileList;
})();

