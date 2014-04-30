/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

OCA.Sharing = {};
OCA.Sharing.App = {

	_inFileList: null,
	_outFileList: null,

	initSharingIn: function($el) {
		if (this._inFileList) {
			return;
		}

		this._inFileList = new OCA.Sharing.FileList(
			$el,
			{
				scrollContainer: $('#app-content'),
				sharedWithUser: true
			}
		);

		var fileActions = _.extend({}, OCA.Files.FileActions);
		fileActions.registerDefaultActions(this._inFileList);
		this._inFileList.setFileActions(fileActions);
	},

	initSharingOut: function($el) {
		if (this._outFileList) {
			return;
		}
		this._outFileList = new OCA.Sharing.FileList(
			$el,
			{
				scrollContainer: $('#app-content'),
				sharedWithUser: false
			}
		);

		var fileActions = _.extend({}, OCA.Files.FileActions);
		fileActions.registerDefaultActions(this._outFileList);
		this._outFileList.setFileActions(fileActions);
	}
};

$(document).ready(function() {
	$('#app-content-sharingin').one('show', function(e) {
		OCA.Sharing.App.initSharingIn($(e.target));
	});
	$('#app-content-sharingout').one('show', function(e) {
		OCA.Sharing.App.initSharingOut($(e.target));
	});
});

