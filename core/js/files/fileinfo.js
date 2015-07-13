/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC) {

	/**
	 * @class OC.Files.FileInfo
	 * @classdesc File information
	 *
	 * @param {Object} data file data, see attributes for details
	 *
	 * @since 8.2
	 */
	var FileInfo = function(data) {
		if (!_.isUndefined(data.id)) {
			this.id = parseInt(data.id, 10);
		}

		// TODO: normalize path
		this.path = data.path || '';
		this.name = data.name;

		this.mtime = data.mtime;
		this.etag = data.etag;
		this.permissions = data.permissions;
		this.size = data.size;
		this.mimetype = data.mimetype || 'application/octet-stream';
		this.mountType = data.mountType;
		this.icon = data.icon;
		this._props = data._props;

		if (data.type) {
			this.type = data.type;
		} else if (this.mimetype === 'httpd/unix-directory') {
			this.type = 'dir';
		} else {
			this.type = 'file';
		}

		if (!this.mimetype) {
			if (this.type === 'dir') {
				this.mimetype = 'httpd/unix-directory';
			} else {
				this.mimetype = 'application/octet-stream';
			}
		}
	};

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
		type: 'file',

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
		mountType: null
	};

	if (!OC.Files) {
		OC.Files = {};
	}
	OC.Files.FileInfo = FileInfo;
})(OC);

