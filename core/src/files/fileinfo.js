/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */
(function(OC) {

	/**
	 * @class OC.Files.FileInfo
	 * @classdesc File information
	 *
	 * @param {Object} data file data, see attributes for details
	 *
	 * @since 8.2
	 */
	const FileInfo = function(data) {
		const self = this
		_.each(data, function(value, key) {
			if (!_.isFunction(value)) {
				self[key] = value
			}
		})

		if (!_.isUndefined(this.id)) {
			this.id = parseInt(data.id, 10)
		}

		// TODO: normalize path
		this.path = data.path || ''

		if (this.type === 'dir') {
			this.mimetype = 'httpd/unix-directory'
		} else {
			this.mimetype = this.mimetype || 'application/octet-stream'
		}

		if (!this.type) {
			if (this.mimetype === 'httpd/unix-directory') {
				this.type = 'dir'
			} else {
				this.type = 'file'
			}
		}
	}

	/**
	 * @memberof OC.Files
	 */
	FileInfo.prototype = {
		/**
		 * File id
		 *
		 * @type int
		 */
		id: null,

		/**
		 * File name
		 *
		 * @type String
		 */
		name: null,

		/**
		 * Path leading to the file, without the file name,
		 * and with a leading slash.
		 *
		 * @type String
		 */
		path: null,

		/**
		 * Mime type
		 *
		 * @type String
		 */
		mimetype: null,

		/**
		 * Icon URL.
		 *
		 * Can be used to override the mime type icon.
		 *
		 * @type String
		 */
		icon: null,

		/**
		 * File type. 'file'  for files, 'dir' for directories.
		 *
		 * @type String
		 * @deprecated rely on mimetype instead
		 */
		type: null,

		/**
		 * Permissions.
		 *
		 * @see OC#PERMISSION_ALL for permissions
		 * @type int
		 */
		permissions: null,

		/**
		 * Modification time
		 *
		 * @type int
		 */
		mtime: null,

		/**
		 * Etag
		 *
		 * @type String
		 */
		etag: null,

		/**
		 * Mount type.
		 *
		 * One of null, "external-root", "shared" or "shared-root"
		 *
		 * @type string
		 */
		mountType: null,

		/**
		 * @type boolean
		 */
		hasPreview: true,

		/**
		 * @type int
		 */
		sharePermissions: null,

		/**
		 * @type Array
		 */
		shareAttributes: [],

		quotaAvailableBytes: -1,

		canDownload: function() {
			for (const i in this.shareAttributes) {
				const attr = this.shareAttributes[i]
				if (attr.scope === 'permissions' && attr.key === 'download') {
					return attr.enabled
				}
			}

			return true
		},
	}

	if (!OC.Files) {
		OC.Files = {}
	}
	OC.Files.FileInfo = FileInfo
})(OC)
