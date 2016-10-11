/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC, OCA) {

	/**
	 * @class OC.Files.FileInfo
	 * @classdesc File information
	 *
	 * @param {Object} attributes file data
	 * @param {int} attributes.id file id
	 * @param {string} attributes.name file name
	 * @param {string} attributes.path path leading to the file,
	 * without the file name and with a leading slash
	 * @param {int} attributes.size size
	 * @param {string} attributes.mimetype mime type
	 * @param {string} attributes.icon icon URL
	 * @param {int} attributes.permissions permissions
	 * @param {Date} attributes.mtime modification time
	 * @param {string} attributes.etag etag
	 * @param {string} mountType mount type
	 *
	 * @since 8.2
	 */
	var FileInfoModel = OC.Backbone.Model.extend({

		defaults: {
			mimetype: 'application/octet-stream',
			path: ''
		},

		initialize: function(data) {
			if (!_.isUndefined(data.id)) {
				data.id = parseInt(data.id, 10);
			}
		},

		/**
		 * Returns whether this file is a directory
		 *
		 * @return {boolean} true if this is a directory, false otherwise
		 */
		isDirectory: function() {
			return this.get('mimetype') === 'httpd/unix-directory';
		},

		/**
		 * Returns whether this file is an image
		 *
		 * @return {boolean} true if this is an image, false otherwise
		 */
		isImage: function() {
			if (!this.has('mimetype')) {
				return false;
			}
			return this.get('mimetype').substr(0, 6) === 'image/'
				|| this.get('mimetype') === 'application/postscript'
				|| this.get('mimetype') === 'application/illustrator'
				|| this.get('mimetype') === 'application/x-photoshop';
		},

		/**
		 * Returns the full path to this file
		 *
		 * @return {string} full path
		 */
		getFullPath: function() {
			return OC.joinPaths(this.get('path'), this.get('name'));
		}
	});

	if (!OCA.Files) {
		OCA.Files = {};
	}
	OCA.Files.FileInfoModel = FileInfoModel;

})(OC, OCA);

