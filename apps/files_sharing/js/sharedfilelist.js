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
	 * Sharing file list
	 *
	 * Contains both "shared with others" and "shared with you" modes.
	 */
	var FileList = function($el, options) {
		this.initialize($el, options);
	};

	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype, {
		appName: 'Shares',

		/**
		 * Whether the list shows the files shared with the user (true) or
		 * the files that the user shared with others (false).
		 */
		_sharedWithUser: false,

		initialize: function($el, options) {
			OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			if (this.initialized) {
				return;
			}

			if (options && options.sharedWithUser) {
				this._sharedWithUser = true;
			}
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
			var self = this;
			this.showMask();
			if (this._reloadCall) {
				this._reloadCall.abort();
			}
			this._reloadCall = $.ajax({
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
				error: function(result) {
					self.reloadCallback(result);
				},
				success: function(result) {
					self.reloadCallback(result);
				}
			});
		},

		reloadCallback: function(result) {
			delete this._reloadCall;
			this.hideMask();

			this.$el.find('#headerSharedWith').text(
				t('files_sharing', this._sharedWithUser ? 'Shared by' : 'Shared with')
			);
			if (result.ocs && result.ocs.data) {
				this.setFiles(this._makeFilesFromShares(result.ocs.data));
			}
			else {
				// TODO: error handling
			}
		},

		/**
		 * Converts the OCS API share response data to a file info
		 * list
		 * @param OCS API share array
		 * @return array of file info maps
		 */
		_makeFilesFromShares: function(data) {
			var self = this;
			// OCS API uses non-camelcased names
			var files = _.chain(data)
				// convert share data to file data
				.map(function(share) {
					/* jshint camelcase: false */
					var file = {
						id: share.file_source,
						mimetype: share.mimetype
					};
					if (share.item_type === 'folder') {
						file.type = 'dir';
						file.mimetype = 'httpd/unix-directory';
					}
					else {
						file.type = 'file';
						// force preview retrieval as we don't have mime types,
						// the preview endpoint will fall back to the mime type
						// icon if no preview exists
						file.isPreviewAvailable = true;
						file.icon = true;
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
					}
					else {
						file.share.targetDisplayName = share.share_with_displayname;
						file.name = OC.basename(share.path);
						file.path = OC.dirname(share.path);
						file.permissions = OC.PERMISSION_ALL;
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

					delete file.share;
					return memo;
				}, {})
				// Retrieve only the values of the returned hash
				.values()
				// Clean up
				.each(function(data) {
					// convert the recipients map to a flat
					// array of sorted names
					data.recipients = _.keys(data.recipients);
					data.recipientsDisplayName = OCA.Sharing.Util.formatRecipients(
						data.recipients,
						data.recipientsCount
					);
					delete data.recipientsCount;
				})
				// Sort by expected sort comparator
				.sortBy(this._sortComparator)
				// Finish the chain by getting the result
				.value();

			return files;
		}
	});

	OCA.Sharing.FileList = FileList;
})();
