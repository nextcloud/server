/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

(function() {
	if (!OCA.SystemTags) {
		/**
		 * @namespace
		 */
		OCA.SystemTags = {}
	}

	OCA.SystemTags.App = {

		initFileList($el) {
			if (this._fileList) {
				return this._fileList
			}

			const tagsParam = (new URL(window.location.href)).searchParams.get('tags')
			const initialTags = tagsParam ? tagsParam.split(',').map(parseInt) : []

			this._fileList = new OCA.SystemTags.FileList(
				$el,
				{
					id: 'systemtags',
					fileActions: this._createFileActions(),
					config: OCA.Files.App.getFilesConfig(),
					// The file list is created when a "show" event is handled,
					// so it should be marked as "shown" like it would have been
					// done if handling the event with the file list already
					// created.
					shown: true,
					systemTagIds: initialTags,
				},
			)

			this._fileList.appName = t('systemtags', 'Tags')
			return this._fileList
		},

		removeFileList() {
			if (this._fileList) {
				this._fileList.$fileList.empty()
			}
		},

		_createFileActions() {
			// inherit file actions from the files app
			const fileActions = new OCA.Files.FileActions()
			// note: not merging the legacy actions because legacy apps are not
			// compatible with the sharing overview and need to be adapted first
			fileActions.registerDefaultActions()
			fileActions.merge(OCA.Files.fileActions)

			if (!this._globalActionsInitialized) {
				// in case actions are registered later
				this._onActionsUpdated = _.bind(this._onActionsUpdated, this)
				OCA.Files.fileActions.on('setDefault.app-systemtags', this._onActionsUpdated)
				OCA.Files.fileActions.on('registerAction.app-systemtags', this._onActionsUpdated)
				this._globalActionsInitialized = true
			}

			// when the user clicks on a folder, redirect to the corresponding
			// folder in the files app instead of opening it directly
			fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function(filename, context) {
				OCA.Files.App.setActiveView('files', { silent: true })
				OCA.Files.App.fileList.changeDirectory(OC.joinPaths(context.$file.attr('data-path'), filename), true, true)
			})
			fileActions.setDefault('dir', 'Open')
			return fileActions
		},

		_onActionsUpdated(ev) {
			if (!this._fileList) {
				return
			}

			if (ev.action) {
				this._fileList.fileActions.registerAction(ev.action)
			} else if (ev.defaultAction) {
				this._fileList.fileActions.setDefault(
					ev.defaultAction.mime,
					ev.defaultAction.name,
				)
			}
		},

		/**
		 * Destroy the app
		 */
		destroy() {
			OCA.Files.fileActions.off('setDefault.app-systemtags', this._onActionsUpdated)
			OCA.Files.fileActions.off('registerAction.app-systemtags', this._onActionsUpdated)
			this.removeFileList()
			this._fileList = null
			delete this._globalActionsInitialized
		},
	}

})()

window.addEventListener('DOMContentLoaded', function() {
	$('#app-content-systemtagsfilter').on('show', function(e) {
		OCA.SystemTags.App.initFileList($(e.target))
	})
	$('#app-content-systemtagsfilter').on('hide', function() {
		OCA.SystemTags.App.removeFileList()
	})
})
