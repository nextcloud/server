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

		this._initFileActions(this._inFileList);
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

		this._initFileActions(this._outFileList);
	},

	_initFileActions: function(fileList) {
		var fileActions = OCA.Files.FileActions.clone();
		// when the user clicks on a folder, redirect to the corresponding
		// folder in the files app
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
			OCA.Files.App.setActiveView('files', {silent: true});
			OCA.Files.App.fileList.changeDirectory(context.$file.attr('data-path') + '/' + filename, true, true);
		});
		fileList.setFileActions(fileActions);
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

