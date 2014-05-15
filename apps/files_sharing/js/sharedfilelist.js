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

		_createRow: function(fileData) {
			// TODO: hook earlier and render the whole row here
			var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
			$tr.find('.filesize').remove();
			var $sharedWith = $('<td class="sharedWith"></td>')
				.text(fileData.shareColumnInfo);
			$tr.find('td.date').before($sharedWith);
			$tr.find('td.filename input:checkbox').remove();
			$tr.attr('data-path', fileData.path);
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
				// cOnvert share data to file data
				.map(function(share) {
					/* jshint camelcase: false */
					var file = {
						id: share.file_source,
						mtime: share.stime * 1000,
						permissions: share.permissions
					};
					if (share.item_type === 'folder') {
						file.type = 'dir';
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
						target: share.share_with
					};
					if (self._sharedWithUser) {
						file.share.ownerDisplayName = share.displayname_owner;
						file.name = OC.basename(share.file_target);
						file.path = OC.dirname(share.file_target);
					}
					else {
						file.share.targetDisplayName = share.share_with_displayname;
						file.name = OC.basename(share.path);
						file.path = OC.dirname(share.path);
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
					if (!data) {
						data = memo[file.id] = file;
						data.shares = [file.share];
					}
					else {
						data.shares.push(file.share);
					}
					// format the share column info output string
					if (!data.shareColumnInfo) {
						data.shareColumnInfo = '';
					}
					else {
						data.shareColumnInfo += ', ';
					}
					// TODO. more accurate detection of name based on type
					// TODO: maybe better formatting, like "link + 3 users" when more than 1 user
					data.shareColumnInfo += (file.share.ownerDisplayName || file.share.targetDisplayName || 'link');
					delete file.share;
					return memo;
				}, {})
				// Retrieve only the values of the returned hash
				.values()
				// Sort by expected sort comparator
				.sortBy(this._sortComparator)
				// Finish the chain by getting the result
				.value();

			return files;
		}
	});

	OCA.Sharing.FileList = FileList;
})();
