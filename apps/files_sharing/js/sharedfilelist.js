/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function() {

	/**
	 * @class OCA.Sharing.FileList
	 * @augments OCA.Files.FileList
	 *
	 * @classdesc Sharing file list.
	 * Contains both "shared with others" and "shared with you" modes.
	 *
	 * @param $el container element with existing markup for the #controls
	 * and a table
	 * @param [options] map of options, see other parameters
	 * @param {boolean} [options.sharedWithUser] true to return files shared with
	 * the current user, false to return files that the user shared with others.
	 * Defaults to false.
	 * @param {boolean} [options.linksOnly] true to return only link shares
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.Sharing.FileList.prototype */ {
		appName: 'Shares',

		/**
		 * Whether the list shows the files shared with the user (true) or
		 * the files that the user shared with others (false).
		 */
		_sharedWithUser: false,
		_linksOnly: false,
		_clientSideSort: true,
		_allowSelection: false,

		/**
		 * @private
		 */
		initialize: function($el, options) {
			OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			if (this.initialized) {
				return;
			}

			// TODO: consolidate both options
			if (options && options.sharedWithUser) {
				this._sharedWithUser = true;
			}
			if (options && options.linksOnly) {
				this._linksOnly = true;
			}
			OC.Plugins.attach('OCA.Sharing.FileList', this);
		},

		_renderRow: function() {
			// HACK: needed to call the overridden _renderRow
			// this is because at the time this class is created
			// the overriding hasn't been done yet...
			return OCA.Files.FileList.prototype._renderRow.apply(this, arguments);
		},

		_createRow: function(fileData) {
			// TODO: hook earlier and render the whole row here
			var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
			$tr.find('.filesize').remove();
			$tr.find('td.date').before($tr.children('td:first'));
			$tr.find('td.filename input:checkbox').remove();
			$tr.attr('data-share-id', _.pluck(fileData.shares, 'id').join(','));
			if (this._sharedWithUser) {
				$tr.attr('data-share-owner', fileData.shareOwner);
				$tr.attr('data-mounttype', 'shared-root');
				var permission = parseInt($tr.attr('data-permissions')) | OC.PERMISSION_DELETE;
				$tr.attr('data-permissions', permission);
			}
			return $tr;
		},

		/**
		 * Set whether the list should contain outgoing shares
		 * or incoming shares.
		 *
		 * @param state true for incoming shares, false otherwise
		 */
		setSharedWithUser: function(state) {
			this._sharedWithUser = !!state;
		},

		updateEmptyContent: function() {
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

		getDirectoryPermissions: function() {
			return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
		},

		updateStorageStatistics: function() {
			// no op because it doesn't have
			// storage info like free space / used space
		},

		reload: function() {
			this.showMask();
			if (this._reloadCall) {
				this._reloadCall.abort();
			}

			// there is only root
			this._setCurrentDir('/', false);

			var promises = [];
			var shares = $.ajax({
				url: OC.linkToOCS('apps/files_sharing/api/v1') + 'shares',
				/* jshint camelcase: false */
				data: {
					format: 'json',
					shared_with_me: !!this._sharedWithUser
				},
				type: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('OCS-APIREQUEST', 'true');
				},
			});
			promises.push(shares);

			if (!!this._sharedWithUser) {
				var remoteShares = $.ajax({
					url: OC.linkToOCS('apps/files_sharing/api/v1') + 'remote_shares',
					/* jshint camelcase: false */
					data: {
						format: 'json'
					},
					type: 'GET',
					beforeSend: function(xhr) {
						xhr.setRequestHeader('OCS-APIREQUEST', 'true');
					},
				});
				promises.push(remoteShares);
			} else {
				//Push empty promise so callback gets called the same way
				promises.push($.Deferred().resolve());
			}

			this._reloadCall = $.when.apply($, promises);
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack, callBack);
		},

		reloadCallback: function(shares, remoteShares) {
			delete this._reloadCall;
			this.hideMask();

			this.$el.find('#headerSharedWith').text(
				t('files_sharing', this._sharedWithUser ? 'Shared by' : 'Shared with')
			);

			var files = [];

			if (shares[0].ocs && shares[0].ocs.data) {
				files = files.concat(this._makeFilesFromShares(shares[0].ocs.data));
			}

			if (remoteShares && remoteShares[0].ocs && remoteShares[0].ocs.data) {
				files = files.concat(this._makeFilesFromRemoteShares(remoteShares[0].ocs.data));
			}

			this.setFiles(files);
			return true;
		},

		_makeFilesFromRemoteShares: function(data) {
			var self = this;
			var files = data;

			files = _.chain(files)
				// convert share data to file data
				.map(function(share) {
					var file = {
						shareOwner: share.owner + '@' + share.remote.replace(/.*?:\/\//g, ""),
						name: OC.basename(share.mountpoint),
						mtime: share.mtime * 1000,
						mimetype: share.mimetype,
						type: share.type,
						id: share.file_id,
						path: OC.dirname(share.mountpoint),
						permissions: share.permissions
					};

					file.shares = [{
						id: share.id,
						type: OC.Share.SHARE_TYPE_REMOTE
					}];
					return file;
				})
				.value();
			return files;
		},

		/**
		 * Converts the OCS API share response data to a file info
		 * list
		 * @param {Array} data OCS API share array
		 * @return {Array.<OCA.Sharing.SharedFileInfo>} array of shared file info
		 */
		_makeFilesFromShares: function(data) {
			/* jshint camelcase: false */
			var self = this;
			var files = data;

			if (this._linksOnly) {
				files = _.filter(data, function(share) {
					return share.share_type === OC.Share.SHARE_TYPE_LINK;
				});
			}

			// OCS API uses non-camelcased names
			files = _.chain(files)
				// convert share data to file data
				.map(function(share) {
					// TODO: use OC.Files.FileInfo
					var file = {
						id: share.file_source,
						icon: OC.MimeType.getIconUrl(share.mimetype),
						mimetype: share.mimetype
					};
					if (share.item_type === 'folder') {
						file.type = 'dir';
						file.mimetype = 'httpd/unix-directory';
					}
					else {
						file.type = 'file';
					}
					file.share = {
						id: share.id,
						type: share.share_type,
						target: share.share_with,
						stime: share.stime * 1000,
					};
					if (self._sharedWithUser) {
						file.shareOwner = share.displayname_owner;
						file.name = OC.basename(share.file_target);
						file.path = OC.dirname(share.file_target);
						file.permissions = share.permissions;
						if (file.path) {
							file.extraData = share.file_target;
						}
					}
					else {
						if (share.share_type !== OC.Share.SHARE_TYPE_LINK) {
							file.share.targetDisplayName = share.share_with_displayname;
						}
						file.name = OC.basename(share.path);
						file.path = OC.dirname(share.path);
						file.permissions = OC.PERMISSION_ALL;
						if (file.path) {
							file.extraData = share.path;
						}
					}
					return file;
				})
				// Group all files and have a "shares" array with
				// the share info for each file.
				//
				// This uses a hash memo to cumulate share information
				// inside the same file object (by file id).
				.reduce(function(memo, file) {
					var data = memo[file.id];
					var recipient = file.share.targetDisplayName;
					if (!data) {
						data = memo[file.id] = file;
						data.shares = [file.share];
						// using a hash to make them unique,
						// this is only a list to be displayed
						data.recipients = {};
						// share types
						data.shareTypes = {};
						// counter is cheaper than calling _.keys().length
						data.recipientsCount = 0;
						data.mtime = file.share.stime;
					}
					else {
						// always take the most recent stime
						if (file.share.stime > data.mtime) {
							data.mtime = file.share.stime;
						}
						data.shares.push(file.share);
					}

					if (recipient) {
						// limit counterparts for output
						if (data.recipientsCount < 4) {
							// only store the first ones, they will be the only ones
							// displayed
							data.recipients[recipient] = true;
						}
						data.recipientsCount++;
					}

					data.shareTypes[file.share.type] = true;

					delete file.share;
					return memo;
				}, {})
				// Retrieve only the values of the returned hash
				.values()
				// Clean up
				.each(function(data) {
					// convert the recipients map to a flat
					// array of sorted names
					data.mountType = 'shared';
					data.recipients = _.keys(data.recipients);
					data.recipientsDisplayName = OCA.Sharing.Util.formatRecipients(
						data.recipients,
						data.recipientsCount
					);
					delete data.recipientsCount;
					if (self._sharedWithUser) {
						// only for outgoing shres
						delete data.shareTypes;
					} else {
						data.shareTypes = _.keys(data.shareTypes);
					}
				})
				// Finish the chain by getting the result
				.value();

			// Sort by expected sort comparator
			return files.sort(this._sortComparator);
		}
	});

	/**
	 * Share info attributes.
	 *
	 * @typedef {Object} OCA.Sharing.ShareInfo
	 *
	 * @property {int} id share ID
	 * @property {int} type share type
	 * @property {String} target share target, either user name or group name
	 * @property {int} stime share timestamp in milliseconds
	 * @property {String} [targetDisplayName] display name of the recipient
	 * (only when shared with others)
	 *
	 */

	/**
	 * Shared file info attributes.
	 *
	 * @typedef {OCA.Files.FileInfo} OCA.Sharing.SharedFileInfo
	 *
	 * @property {Array.<OCA.Sharing.ShareInfo>} shares array of shares for
	 * this file
	 * @property {int} mtime most recent share time (if multiple shares)
	 * @property {String} shareOwner name of the share owner
	 * @property {Array.<String>} recipients name of the first 4 recipients
	 * (this is mostly for display purposes)
	 * @property {String} recipientsDisplayName display name
	 */

	OCA.Sharing.FileList = FileList;
})();
