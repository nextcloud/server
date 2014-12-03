/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

if (!OCA.External) {
	/**
	 * @namespace
	 */
	OCA.External = {};
}
/**
 * @namespace
 */
OCA.External.App = {

	fileList: null,

	initList: function($el) {
		if (this.fileList) {
			return this.fileList;
		}

		this.fileList = new OCA.External.FileList(
			$el,
			{
				scrollContainer: $('#app-content'),
				fileActions: this._createFileActions()
			}
		);

		this._extendFileList(this.fileList);
		this.fileList.appName = t('files_external', 'External storage');
		return this.fileList;
	},

	removeList: function() {
		if (this.fileList) {
			this.fileList.$fileList.empty();
		}
	},

	_createFileActions: function() {
		// inherit file actions from the files app
		var fileActions = new OCA.Files.FileActions();
		fileActions.registerDefaultActions();

		// when the user clicks on a folder, redirect to the corresponding
		// folder in the files app instead of opening it directly
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
			OCA.Files.App.setActiveView('files', {silent: true});
			OCA.Files.App.fileList.changeDirectory(context.$file.attr('data-path') + '/' + filename, true, true);
		});
		fileActions.setDefault('dir', 'Open');
		return fileActions;
	},

	_extendFileList: function(fileList) {
		// remove size column from summary
		fileList.fileSummary.$el.find('.filesize').remove();
	}
};

$(document).ready(function() {
	$('#app-content-extstoragemounts').on('show', function(e) {
		OCA.External.App.initList($(e.target));
	});
	$('#app-content-extstoragemounts').on('hide', function() {
		OCA.External.App.removeList();
	});
});

