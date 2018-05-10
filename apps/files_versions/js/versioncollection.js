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
	var VersionCollection = OC.Backbone.Collection.extend({
		model: OCA.Versions.VersionModel,

		sync: OC.Backbone.davSync,

		/** @var OCA.Files.FileInfoModel */
		_fileInfo: null,

		url: function() {
			return OC.linkToRemote('dav') + '/versions/' + OC.getCurrentUser().uid + '/versions/' + this._fileInfo.id;
		},

		setFileInfo: function(fileInfo) {
			this._fileInfo = fileInfo;
			// reset
			this._endReached = false;
			this._currentIndex = 0;
		},

		getFileInfo: function() {
			return this._fileInfo;
		},

		reset: function() {
			this._currentIndex = 0;
			OC.Backbone.Collection.prototype.reset.apply(this, arguments);
		},

		parse: function(result) {
			var fullPath = this._fileInfo.getFullPath();

			var results = _.map(result, function(version) {
				var revision = parseInt(version.id, 10);
				return {
					id: revision,
					fullPath: fullPath,
					timestamp: revision,
					size: parseInt(version.size, 10),
					mimetype: version.mimetype
				};
			});

			return results;
		}
	});

	OCA.Versions = OCA.Versions || {};

	OCA.Versions.VersionCollection = VersionCollection;
})();

