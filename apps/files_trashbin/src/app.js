/**
 * Copyright (c) 2014
 *
 * @author Abijeet <abijeetpatro@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

OCA.Trashbin = {}
/**
 * @namespace OCA.Trashbin.App
 */
OCA.Trashbin.App = {
	_initialized: false,
	/** @type {OC.Files.Client} */
	client: null,

	initialize($el) {
		if (this._initialized) {
			return
		}
		this._initialized = true

		this.client = new OC.Files.Client({
			host: OC.getHost(),
			port: OC.getPort(),
			root: OC.linkToRemoteBase('dav') + '/trashbin/' + OC.getCurrentUser().uid,
			useHTTPS: OC.getProtocol() === 'https',
		})
		const urlParams = OC.Util.History.parseUrlQuery()
		this.fileList = new OCA.Trashbin.FileList(
			$('#app-content-trashbin'), {
				fileActions: this._createFileActions(),
				detailsViewEnabled: false,
				scrollTo: urlParams.scrollto,
				config: OCA.Files.App.getFilesConfig(),
				multiSelectMenu: [
					{
						name: 'restore',
						displayName: t('files_trashbin', 'Restore'),
						iconClass: 'icon-history',
					},
					{
						name: 'delete',
						displayName: t('files_trashbin', 'Delete permanently'),
						iconClass: 'icon-delete',
					},
				],
				client: this.client,
				// The file list is created when a "show" event is handled, so
				// it should be marked as "shown" like it would have been done
				// if handling the event with the file list already created.
				shown: true,
			}
		)
	},

	_createFileActions() {
		const client = this.client
		const fileActions = new OCA.Files.FileActions()
		fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function(filename, context) {
			const dir = context.fileList.getCurrentDirectory()
			context.fileList.changeDirectory(OC.joinPaths(dir, filename))
		})

		fileActions.setDefault('dir', 'Open')

		fileActions.registerAction({
			name: 'Restore',
			displayName: t('files_trashbin', 'Restore'),
			type: OCA.Files.FileActions.TYPE_INLINE,
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			iconClass: 'icon-history',
			actionHandler(filename, context) {
				const fileList = context.fileList
				const tr = fileList.findFileEl(filename)
				fileList.showFileBusyState(tr, true)
				const dir = context.fileList.getCurrentDirectory()
				client.move(OC.joinPaths('trash', dir, filename), OC.joinPaths('restore', filename), true)
					.then(
						fileList._removeCallback.bind(fileList, [filename]),
						function() {
							fileList.showFileBusyState(tr, false)
							OC.Notification.show(t('files_trashbin', 'Error while restoring file from trash bin'))
						}
					)
			},
		})

		fileActions.registerAction({
			name: 'Delete',
			displayName: t('files_trashbin', 'Delete permanently'),
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			iconClass: 'icon-delete',
			render(actionSpec, isDefault, context) {
				const $actionLink = fileActions._makeActionLink(actionSpec, context)
				$actionLink.attr('original-title', t('files_trashbin', 'Delete permanently'))
				$actionLink.children('img').attr('alt', t('files_trashbin', 'Delete permanently'))
				context.$file.find('td:last').append($actionLink)
				return $actionLink
			},
			actionHandler(filename, context) {
				const fileList = context.fileList
				$('.tipsy').remove()
				const tr = fileList.findFileEl(filename)
				fileList.showFileBusyState(tr, true)
				const dir = context.fileList.getCurrentDirectory()
				client.remove(OC.joinPaths('trash', dir, filename))
					.then(
						fileList._removeCallback.bind(fileList, [filename]),
						function() {
							fileList.showFileBusyState(tr, false)
							OC.Notification.show(t('files_trashbin', 'Error while removing file from trash bin'))
						}
					)
			},
		})
		return fileActions
	},
}

window.addEventListener('DOMContentLoaded', function() {
	$('#app-content-trashbin').one('show', function() {
		const App = OCA.Trashbin.App
		App.initialize($('#app-content-trashbin'))
		// force breadcrumb init
		// App.fileList.changeDirectory(App.fileList.getCurrentDirectory(), false, true);
	})
})
