/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

OCA.Trashbin = {};
OCA.Trashbin.App = {
	_initialized: false,

	initialize: function($el) {
		if (this._initialized) {
			return;
		}
		this._initialized = true;
		this.fileList = new OCA.Trashbin.FileList($el);
		this.registerFileActions(this.fileList);
	},

	registerFileActions: function(fileList) {
		var self = this;
		var fileActions = _.extend({}, OCA.Files.FileActions);
		fileActions.clear();
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename) {
			var dir = fileList.getCurrentDirectory();
			if (dir !== '/') {
				dir = dir + '/';
			}
			fileList.changeDirectory(dir + filename);
		});

		fileActions.setDefault('dir', 'Open');

		fileActions.register('all', 'Restore', OC.PERMISSION_READ, OC.imagePath('core', 'actions/history'), function(filename) {
			var tr = fileList.findFileEl(filename);
			var deleteAction = tr.children("td.date").children(".action.delete");
			deleteAction.removeClass('delete-icon').addClass('progress-icon');
			fileList.disableActions();
			$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'), {
					files: JSON.stringify([filename]),
					dir: fileList.getCurrentDirectory()
				},
				_.bind(fileList._removeCallback, fileList)
			);
		}, t('files_trashbin', 'Restore'));

		fileActions.register('all', 'Delete', OC.PERMISSION_READ, function() {
			return OC.imagePath('core', 'actions/delete');
		}, function(filename) {
			$('.tipsy').remove();
			var tr = fileList.findFileEl(filename);
			var deleteAction = tr.children("td.date").children(".action.delete");
			deleteAction.removeClass('delete-icon').addClass('progress-icon');
			fileList.disableActions();
			$.post(OC.filePath('files_trashbin', 'ajax', 'delete.php'), {
					files: JSON.stringify([filename]),
					dir: fileList.getCurrentDirectory()
				},
				_.bind(fileList._removeCallback, fileList)
			);
		});
		fileList.setFileActions(fileActions);
	}
};

$(document).ready(function() {
	$('#app-content-trashbin').on('show', function() {
		var App = OCA.Trashbin.App;
		App.initialize($('#app-content-trashbin'));
		// force breadcrumb init
		// App.fileList.changeDirectory(App.fileList.getCurrentDirectory(), false, true);
	});
});

