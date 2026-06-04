/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import IconMove from '@mdi/svg/svg/folder-move.svg?raw'
import IconCopy from '@mdi/svg/svg/folder-multiple-outline.svg?raw'
import { DialogBuilder, FilePickerType, getFilePickerBuilder } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { basename } from 'path'
import { defineAsyncComponent } from 'vue'
import logger from '../logger.js'

/**
 * this class to ease the usage of dialogs
 */
const Dialogs = {
	// dialog button types
	/** @deprecated use `@nextcloud/dialogs` */
	YES_NO_BUTTONS: 70,
	/** @deprecated use `@nextcloud/dialogs` */
	OK_BUTTONS: 71,

	/** @deprecated use FilePickerType from `@nextcloud/dialogs` */
	FILEPICKER_TYPE_CHOOSE: 1,
	/** @deprecated use FilePickerType from `@nextcloud/dialogs` */
	FILEPICKER_TYPE_MOVE: 2,
	/** @deprecated use FilePickerType from `@nextcloud/dialogs` */
	FILEPICKER_TYPE_COPY: 3,
	/** @deprecated use FilePickerType from `@nextcloud/dialogs` */
	FILEPICKER_TYPE_COPY_MOVE: 4,
	/** @deprecated use FilePickerType from `@nextcloud/dialogs` */
	FILEPICKER_TYPE_CUSTOM: 5,

	/**
	 * displays alert dialog
	 *
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {Function} callback which will be triggered when user presses OK
	 * @param {boolean} [modal] make the dialog modal
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	alert: function(text, title, callback, modal) {
		this.message(
			text,
			title,
			'alert',
			Dialogs.OK_BUTTON,
			callback,
			modal,
		)
	},

	/**
	 * displays info dialog
	 *
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {Function} callback which will be triggered when user presses OK
	 * @param {boolean} [modal] make the dialog modal
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	info: function(text, title, callback, modal) {
		this.message(text, title, 'info', Dialogs.OK_BUTTON, callback, modal)
	},

	/**
	 * displays confirmation dialog
	 *
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @param {boolean} [modal] make the dialog modal
	 * @return {Promise}
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	confirm: function(text, title, callback, modal) {
		return this.message(
			text,
			title,
			'notice',
			Dialogs.YES_NO_BUTTONS,
			callback,
			modal,
		)
	},
	/**
	 * displays confirmation dialog
	 *
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {(number|{type: number, confirm: string, cancel: string, confirmClasses: string})} buttons text content of buttons
	 * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @return {Promise}
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	confirmDestructive: function(text, title, buttons = Dialogs.OK_BUTTONS, callback = () => {}) {
		return (new DialogBuilder())
			.setName(title)
			.setText(text)
			.setButtons(buttons === Dialogs.OK_BUTTONS
				? [
						{
							label: t('core', 'Yes'),
							variant: 'error',
							callback: () => {
								callback.clicked = true
								callback(true)
							},
						},
					]
				: Dialogs._getLegacyButtons(buttons, callback))
			.build()
			.show()
			.then(() => {
				if (!callback.clicked) {
					callback(false)
				}
			})
	},
	/**
	 * displays confirmation dialog
	 *
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @return {Promise}
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	confirmHtml: function(text, title, callback) {
		return (new DialogBuilder())
			.setName(title)
			.setText('')
			.setButtons([
				{
					label: t('core', 'No'),
					callback: () => {},
				},
				{
					label: t('core', 'Yes'),
					variant: 'primary',
					callback: () => {
						callback.clicked = true
						callback(true)
					},
				},
			])
			.build()
			.setHTML(text)
			.show()
			.then(() => {
				if (!callback.clicked) {
					callback(false)
				}
			})
	},
	/**
	 * displays prompt dialog
	 *
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {Function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @param {boolean} [modal] make the dialog modal
	 * @param {string} name name of the input field
	 * @param {boolean} password whether the input should be a password input
	 * @return {Promise}
	 *
	 * @deprecated Use NcDialog from `@nextcloud/vue` instead
	 */
	prompt: function(text, title, callback, modal, name, password) {
		return new Promise((resolve) => {
			spawnDialog(
				defineAsyncComponent(() => import('../components/LegacyDialogPrompt.vue')),
				{
					text,
					name: title,
					callback,
					inputName: name,
					isPassword: !!password,
				},
				(...args) => {
					callback(...args)
					resolve()
				},
			)
		})
	},

	/**
	 * Legacy wrapper to the new Vue based filepicker from `@nextcloud/dialogs`
	 *
	 * Prefer to use the Vue filepicker directly instead.
	 *
	 * In order to pick several types of mime types they need to be passed as an
	 * array of strings.
	 *
	 * When no mime type filter is given only files can be selected. In order to
	 * be able to select both files and folders "['*', 'httpd/unix-directory']"
	 * should be used instead.
	 *
	 * @param {string} title dialog title
	 * @param {Function} callback which will be triggered when user presses Choose
	 * @param {boolean} [multiselect] whether it should be possible to select multiple files
	 * @param {string[]} [mimetype] mimetype to filter by - directories will always be included
	 * @param {boolean} [_modal] do not use
	 * @param {string} [type] Type of file picker : Choose, copy, move, copy and move
	 * @param {string} [path] path to the folder that the the file can be picket from
	 * @param {object} [options] additonal options that need to be set
	 * @param {Function} [options.filter] filter function for advanced filtering
	 * @param {boolean} [options.allowDirectoryChooser] Allow to select directories
	 * @deprecated since 27.1.0 use the filepicker from `@nextcloud/dialogs` instead
	 */
	// eslint-disable-next-line no-unused-vars
	filepicker(title, callback, multiselect = false, mimetype = undefined, _modal = undefined, type = FilePickerType.Choose, path = undefined, options = undefined) {
		/**
		 * Create legacy callback wrapper to support old filepicker syntax
		 *
		 * @param fn The original callback
		 * @param type The file picker type which was used to pick the file(s)
		 */
		const legacyCallback = (fn, type) => {
			const getPath = (node) => {
				const root = node?.root || ''
				let path = node?.path || ''
				// TODO: Fix this in @nextcloud/files
				if (path.startsWith(root)) {
					path = path.slice(root.length) || '/'
				}
				return path
			}

			if (multiselect) {
				return (nodes) => fn(nodes.map(getPath), type)
			} else {
				return (nodes) => fn(getPath(nodes[0]), type)
			}
		}

		/**
		 * Coverting a Node into a legacy file info to support the OC.dialogs.filepicker filter function
		 *
		 * @param node The node to convert
		 */
		const nodeToLegacyFile = (node) => ({
			id: node.fileid || null,
			path: node.path,
			mimetype: node.mime || null,
			mtime: node.mtime?.getTime() || null,
			permissions: node.permissions,
			name: node.attributes?.displayName || node.basename,
			etag: node.attributes?.etag || null,
			hasPreview: node.attributes?.hasPreview || null,
			mountType: node.attributes?.mountType || null,
			quotaAvailableBytes: node.attributes?.quotaAvailableBytes || null,
			icon: null,
			sharePermissions: null,
		})

		const builder = getFilePickerBuilder(title)

		// Setup buttons
		if (type === this.FILEPICKER_TYPE_CUSTOM) {
			(options.buttons || []).forEach((button) => {
				builder.addButton({
					callback: legacyCallback(callback, button.type),
					label: button.text,
					variant: button.defaultButton ? 'primary' : 'secondary',
				})
			})
		} else {
			builder.setButtonFactory((nodes, path) => {
				const buttons = []
				const [node] = nodes
				const target = node?.displayname || node?.basename || basename(path)

				if (type === FilePickerType.Choose) {
					buttons.push({
						callback: legacyCallback(callback, FilePickerType.Choose),
						label: node && !this.multiSelect ? t('core', 'Choose {file}', { file: target }) : t('core', 'Choose'),
						variant: 'primary',
					})
				}
				if (type === FilePickerType.CopyMove || type === FilePickerType.Copy) {
					buttons.push({
						callback: legacyCallback(callback, FilePickerType.Copy),
						label: target ? t('core', 'Copy to {target}', { target }) : t('core', 'Copy'),
						variant: 'primary',
						icon: IconCopy,
					})
				}
				if (type === FilePickerType.Move || type === FilePickerType.CopyMove) {
					buttons.push({
						callback: legacyCallback(callback, FilePickerType.Move),
						label: target ? t('core', 'Move to {target}', { target }) : t('core', 'Move'),
						variant: type === FilePickerType.Move ? 'primary' : 'secondary',
						icon: IconMove,
					})
				}
				return buttons
			})
		}

		if (mimetype) {
			builder.setMimeTypeFilter(typeof mimetype === 'string' ? [mimetype] : (mimetype || []))
		}
		if (typeof options?.filter === 'function') {
			builder.setFilter((node) => options.filter(nodeToLegacyFile(node)))
		}
		builder.allowDirectories(options?.allowDirectoryChooser === true || mimetype?.includes('httpd/unix-directory') || false)
			.setMultiSelect(multiselect)
			.startAt(path)
			.build()
			.pick()
	},

	/**
	 * Displays raw dialog
	 * You better use a wrapper instead ...
	 *
	 * @param content
	 * @param title
	 * @param dialogType
	 * @param buttons
	 * @param callback
	 * @param modal
	 * @param allowHtml
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	message: function(content, title, dialogType, buttons, callback = () => {}, modal, allowHtml) {
		const builder = (new DialogBuilder())
			.setName(title)
			.setText(allowHtml ? '' : content)
			.setButtons(Dialogs._getLegacyButtons(buttons, callback))

		switch (dialogType) {
			case 'alert':
				builder.setSeverity('warning')
				break
			case 'notice':
				builder.setSeverity('info')
				break
			default:
				break
		}

		const dialog = builder.build()

		if (allowHtml) {
			dialog.setHTML(content)
		}

		return dialog.show().then(() => {
			if (!callback._clicked) {
				callback(false)
			}
		})
	},

	/**
	 * Helper for legacy API
	 *
	 * @param buttons
	 * @param callback
	 * @deprecated
	 */
	_getLegacyButtons(buttons, callback) {
		const buttonList = []

		switch (typeof buttons === 'object' ? buttons.type : buttons) {
			case Dialogs.YES_NO_BUTTONS:
				buttonList.push({
					label: buttons?.cancel ?? t('core', 'No'),
					callback: () => {
						callback._clicked = true
						callback(false)
					},
				})
				buttonList.push({
					label: buttons?.confirm ?? t('core', 'Yes'),
					variant: 'primary',
					callback: () => {
						callback._clicked = true
						callback(true)
					},
				})
				break
			case Dialogs.OK_BUTTONS:
				buttonList.push({
					label: buttons?.confirm ?? t('core', 'OK'),
					variant: 'primary',
					callback: () => {
						callback._clicked = true
						callback(true)
					},
				})
				break
			default:
				logger.error('Invalid call to OC.dialogs')
				break
		}
		return buttonList
	},
}

export default Dialogs
