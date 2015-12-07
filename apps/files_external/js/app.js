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
			OCA.Files.App.fileList.changeDirectory(OC.joinPaths(context.$file.attr('data-path'), filename), true, true);
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

	/* Status Manager */
	if ($('#filesApp').val()) {

		$('#app-content-files')
			.add('#app-content-extstoragemounts')
			.on('changeDirectory', function(e){
				if (e.dir === '/') {
					var mount_point = e.previousDir.split('/', 2)[1];
					// Every time that we return to / root folder from a mountpoint, mount_point status is rechecked
					OCA.External.StatusManager.getMountPointList(function() {
						OCA.External.StatusManager.recheckConnectivityForMount([mount_point], true);
					});
				}
			})
			.on('fileActionsReady', function(e){
			if ($.isArray(e.$files)) {
				if (OCA.External.StatusManager.mountStatus === null ||
						OCA.External.StatusManager.mountPointList === null ||
						_.size(OCA.External.StatusManager.mountStatus) !== _.size(OCA.External.StatusManager.mountPointList)) {
					// Will be the very first check when the files view will be loaded
					OCA.External.StatusManager.launchFullConnectivityCheckOneByOne();
				} else {
					// When we change between general files view and external files view
					OCA.External.StatusManager.getMountPointList(function(){
						var fileNames = [];
						$.each(e.$files, function(key, value){
							fileNames.push(value.attr('data-file'));
						});
						// Recheck if launched but work from cache
						OCA.External.StatusManager.recheckConnectivityForMount(fileNames, false);
					});
				}
			}
		});
	}
	/* End Status Manager */
});

