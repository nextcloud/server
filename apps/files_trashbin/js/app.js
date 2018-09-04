/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * @namespace OCA.Trashbin
 */
OCA.Trashbin = {};
/**
 * @namespace OCA.Trashbin.App
 */
OCA.Trashbin.App = {
	_initialized: false,
	/** @type {OC.Files.Client} */
	client: null,

	initialize: function ($el) {
		if (this._initialized) {
			return;
		}
		this._initialized = true;

		this.client = new OC.Files.Client({
			host: OC.getHost(),
			port: OC.getPort(),
			root: OC.linkToRemoteBase('dav') + '/trashbin/' + OC.getCurrentUser().uid,
			useHTTPS: OC.getProtocol() === 'https'
		});
		var urlParams = OC.Util.History.parseUrlQuery();
		this.fileList = new OCA.Trashbin.FileList(
			$('#app-content-trashbin'), {
				fileActions: this._createFileActions(),
				detailsViewEnabled: false,
				scrollTo: urlParams.scrollto,
				config: OCA.Files.App.getFilesConfig(),
				multiSelectMenu: [
					{
						name: 'restore',
						displayName: t('files', 'Restore'),
						iconClass: 'icon-history',
					},
					{
						name: 'delete',
						displayName: t('files', 'Delete'),
						iconClass: 'icon-delete',
					}
				],
				client: this.client
			}
		);
	},

	_createFileActions: function () {
		var client = this.client;
		var fileActions = new OCA.Files.FileActions();
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
			var dir = context.fileList.getCurrentDirectory();
			context.fileList.changeDirectory(OC.joinPaths(dir, filename));
		});

		fileActions.setDefault('dir', 'Open');

		fileActions.registerAction({
			name: 'Restore',
			displayName: t('files_trashbin', 'Restore'),
			type: OCA.Files.FileActions.TYPE_INLINE,
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			iconClass: 'icon-history',
			actionHandler: function (filename, context) {
				var fileList = context.fileList;
				var tr = fileList.findFileEl(filename);
				var deleteAction = tr.children("td.date").children(".action.delete");
				deleteAction.removeClass('icon-delete').addClass('icon-loading-small');
				$.post(OC.filePath('files_trashbin', 'ajax', 'undelete.php'), {
						files: JSON.stringify([filename]),
						dir: fileList.getCurrentDirectory()
					},
					_.bind(fileList._removeCallback, fileList)
				);
			}
		});

		fileActions.registerAction({
			name: 'Delete',
			displayName: t('files', 'Delete'),
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			iconClass: 'icon-delete',
			render: function (actionSpec, isDefault, context) {
				var $actionLink = fileActions._makeActionLink(actionSpec, context);
				$actionLink.attr('original-title', t('files_trashbin', 'Delete permanently'));
				$actionLink.children('img').attr('alt', t('files_trashbin', 'Delete permanently'));
				context.$file.find('td:last').append($actionLink);
				return $actionLink;
			},
			actionHandler: function (filename, context) {
				var fileList = context.fileList;
				$('.tipsy').remove();
				var tr = fileList.findFileEl(filename);
				var deleteAction = tr.children("td.date").children(".action.delete");
				deleteAction.removeClass('icon-delete').addClass('icon-loading-small');
				client.remove('trash/' + filename)
					.then(
						fileList._removeCallback.bind(fileList, [filename]),
						function () {
							OC.Notification.show(t('files_trashbin', 'Error while removing file from trashbin'));
						}
					);
			}
		});
		return fileActions;
	}
};

$(document).ready(function () {
	$('#app-content-trashbin').one('show', function () {
		var App = OCA.Trashbin.App;
		App.initialize($('#app-content-trashbin'));
		// force breadcrumb init
		// App.fileList.changeDirectory(App.fileList.getCurrentDirectory(), false, true);
	});
});

