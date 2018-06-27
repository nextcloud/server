/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	/**
	 * @memberof OCA.Versions
	 */
	var VersionModel = OC.Backbone.Model.extend({
		sync: OC.Backbone.davSync,

		davProperties: {
			'size': OC.Files.Client.PROPERTY_GETCONTENTLENGTH,
			'mimetype': OC.Files.Client.PROPERTY_GETCONTENTTYPE,
		},

		/**
		 * Restores the original file to this revision
		 */
		revert: function(options) {
			options = options ? _.clone(options) : {};

			options = _.extend(options,
				{
					type: 'MOVE',
					url: OC.linkToRemote('dav') + '/versions/' + OC.getCurrentUser().uid + '/versions/' + this.collection.getFileInfo().id + '/' + this.get('timestamp'),
					headers: {
						Destination: OC.linkToRemote('dav') + '/versions/' + OC.getCurrentUser().uid + '/restore/' + this.get('timestamp')
					}
				}
			);

			OC.Backbone.davCall(
				options,
				this
			);
		},

		getFullPath: function() {
			return this.get('fullPath');
		},

		getPreviewUrl: function() {
			var url = OC.generateUrl('/apps/files_versions/preview');
			var params = {
				file: this.get('fullPath'),
				version: this.get('timestamp')
			};
			return url + '?' + OC.buildQueryString(params);
		},

		getDownloadUrl: function() {
			return OC.linkToRemote('dav') + '/versions/' + OC.getCurrentUser().uid + '/versions/' + this.collection.getFileInfo().id + '/' + this.get('timestamp');
		}
	});

	OCA.Versions = OCA.Versions || {};

	OCA.Versions.VersionModel = VersionModel;
})();

