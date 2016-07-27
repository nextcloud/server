/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

// HACK: this piece needs to be loaded AFTER the files app (for unit tests)
$(document).ready(function () {
	(function (OCA) {
		/**
		 * @class OCA.Files.RecentFileList
		 * @augments OCA.Files.RecentFileList
		 *
		 * @classdesc Recent file list.
		 * Displays the list of recently modified files
		 *
		 * @param $el container element with existing markup for the #controls
		 * and a table
		 * @param [options] map of options, see other parameters
		 */
		var RecentFileList = function ($el, options) {
			options.sorting = {
				mode: 'mtime',
				direction: 'desc'
			};
			this.initialize($el, options);
			this._allowSorting = false;
		};
		RecentFileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
			/** @lends OCA.Files.RecentFileList.prototype */ {
				id: 'recent',
				appName: t('files', 'Recent'),

				_clientSideSort: true,
				_allowSelection: false,

				/**
				 * @private
				 */
				initialize: function () {
					OCA.Files.FileList.prototype.initialize.apply(this, arguments);
					if (this.initialized) {
						return;
					}
					OC.Plugins.attach('OCA.Files.RecentFileList', this);
				},

				updateEmptyContent: function () {
					var dir = this.getCurrentDirectory();
					if (dir === '/') {
						// root has special permissions
						this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
						this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
					}
					else {
						OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
					}
				},

				getDirectoryPermissions: function () {
					return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
				},

				updateStorageStatistics: function () {
					// no op because it doesn't have
					// storage info like free space / used space
				},

				reload: function () {
					this.showMask();
					if (this._reloadCall) {
						this._reloadCall.abort();
					}

					// there is only root
					this._setCurrentDir('/', false);

					this._reloadCall = $.ajax({
						url: OC.generateUrl('/apps/files/api/v1/recent'),
						type: 'GET',
						dataType: 'json'
					});
					var callBack = this.reloadCallback.bind(this);
					return this._reloadCall.then(callBack, callBack);
				},

				reloadCallback: function (result) {
					delete this._reloadCall;
					this.hideMask();

					if (result.files) {
						this.setFiles(result.files.sort(this._sortComparator));
						return true;
					}
					return false;
				}
			});

		OCA.Files.RecentFileList = RecentFileList;
	})(OCA);
});

