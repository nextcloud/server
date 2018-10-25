/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function (OCA) {
	/**
	 * Registers the recent file list from the files app sidebar.
	 *
	 * @namespace OCA.Files.RecentPlugin
	 */
	OCA.Files.RecentPlugin = {
		name: 'Recent',

		/**
		 * @type OCA.Files.RecentFileList
		 */
		recentFileList: null,

		attach: function () {
			var self = this;
			$('#app-content-recent').on('show.plugin-recent', function (e) {
				self.showFileList($(e.target));
			});
			$('#app-content-recent').on('hide.plugin-recent', function () {
				self.hideFileList();
			});
		},

		detach: function () {
			if (this.recentFileList) {
				this.recentFileList.destroy();
				OCA.Files.fileActions.off('setDefault.plugin-recent', this._onActionsUpdated);
				OCA.Files.fileActions.off('registerAction.plugin-recent', this._onActionsUpdated);
				$('#app-content-recent').off('.plugin-recent');
				this.recentFileList = null;
			}
		},

		showFileList: function ($el) {
			if (!this.recentFileList) {
				this.recentFileList = this._createRecentFileList($el);
			}
			return this.recentFileList;
		},

		hideFileList: function () {
			if (this.recentFileList) {
				this.recentFileList.$fileList.empty();
			}
		},

		/**
		 * Creates the recent file list.
		 *
		 * @param $el container for the file list
		 * @return {OCA.Files.RecentFileList} file list
		 */
		_createRecentFileList: function ($el) {
			var fileActions = this._createFileActions();
			// register recent list for sidebar section
			return new OCA.Files.RecentFileList(
				$el, {
					fileActions: fileActions,
					// The file list is created when a "show" event is handled,
					// so it should be marked as "shown" like it would have been
					// done if handling the event with the file list already
					// created.
					shown: true
				}
			);
		},

		_createFileActions: function () {
			// inherit file actions from the files app
			var fileActions = new OCA.Files.FileActions();
			// note: not merging the legacy actions because legacy apps are not
			// compatible with the sharing overview and need to be adapted first
			fileActions.registerDefaultActions();
			fileActions.merge(OCA.Files.fileActions);

			if (!this._globalActionsInitialized) {
				// in case actions are registered later
				this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
				OCA.Files.fileActions.on('setDefault.plugin-recent', this._onActionsUpdated);
				OCA.Files.fileActions.on('registerAction.plugin-recent', this._onActionsUpdated);
				this._globalActionsInitialized = true;
			}

			// when the user clicks on a folder, redirect to the corresponding
			// folder in the files app instead of opening it directly
			fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
				OCA.Files.App.setActiveView('files', {silent: true});
				var path = OC.joinPaths(context.$file.attr('data-path'), filename);
				OCA.Files.App.fileList.changeDirectory(path, true, true);
			});
			fileActions.setDefault('dir', 'Open');
			return fileActions;
		},

		_onActionsUpdated: function (ev) {
			if (ev.action) {
				this.recentFileList.fileActions.registerAction(ev.action);
			} else if (ev.defaultAction) {
				this.recentFileList.fileActions.setDefault(
					ev.defaultAction.mime,
					ev.defaultAction.name
				);
			}
		}
	};

})(OCA);

OC.Plugins.register('OCA.Files.App', OCA.Files.RecentPlugin);

