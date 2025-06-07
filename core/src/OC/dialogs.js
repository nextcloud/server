/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* eslint-disable */
import _ from 'underscore'
import $ from 'jquery'

import IconMove from '@mdi/svg/svg/folder-move.svg?raw'
import IconCopy from '@mdi/svg/svg/folder-multiple.svg?raw'

import OC from './index.js'
import { DialogBuilder, FilePickerType, getFilePickerBuilder, spawnDialog } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { basename } from 'path'
import { defineAsyncComponent } from 'vue'

/**
 * this class to ease the usage of jquery dialogs
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
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {function} callback which will be triggered when user presses OK
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
			modal
		)
	},

	/**
	 * displays info dialog
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {function} callback which will be triggered when user presses OK
	 * @param {boolean} [modal] make the dialog modal
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	info: function(text, title, callback, modal) {
		this.message(text, title, 'info', Dialogs.OK_BUTTON, callback, modal)
	},

	/**
	 * displays confirmation dialog
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @param {boolean} [modal] make the dialog modal
	 * @returns {Promise}
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
			modal
		)
	},
	/**
	 * displays confirmation dialog
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {(number|{type: number, confirm: string, cancel: string, confirmClasses: string})} buttons text content of buttons
	 * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @param {boolean} [modal] make the dialog modal
	 * @returns {Promise}
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	confirmDestructive: function(text, title, buttons = Dialogs.OK_BUTTONS, callback = () => {}, modal) {
		return (new DialogBuilder())
			.setName(title)
			.setText(text)
			.setButtons(
				buttons === Dialogs.OK_BUTTONS
				? [
					{
						label: t('core', 'Yes'),
						type: 'error',
						callback: () => {
							callback.clicked = true
							callback(true)
						},
					}
				]
				: Dialogs._getLegacyButtons(buttons, callback)
			)
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
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @param {boolean} [modal] make the dialog modal
	 * @returns {Promise}
	 *
	 * @deprecated 30.0.0 Use `@nextcloud/dialogs` instead or build your own with `@nextcloud/vue` NcDialog
	 */
	confirmHtml: function(text, title, callback, modal) {
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
					type: 'primary',
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
	 * @param {string} text content of dialog
	 * @param {string} title dialog title
	 * @param {function} callback which will be triggered when user presses OK (true or false would be passed to callback respectively)
	 * @param {boolean} [modal] make the dialog modal
	 * @param {string} name name of the input field
	 * @param {boolean} password whether the input should be a password input
	 * @returns {Promise}
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
					isPassword: !!password
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
	filepicker(title, callback, multiselect = false, mimetype = undefined, _modal = undefined, type = FilePickerType.Choose, path = undefined, options = undefined) {

		/**
		 * Create legacy callback wrapper to support old filepicker syntax
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
					type: button.defaultButton ? 'primary' : 'secondary',
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
						type: 'primary',
					})
				}
				if (type === FilePickerType.CopyMove || type === FilePickerType.Copy) {
					buttons.push({
						callback: legacyCallback(callback, FilePickerType.Copy),
						label: target ? t('core', 'Copy to {target}', { target }) : t('core', 'Copy'),
						type: 'primary',
						icon: IconCopy,
					})
				}
				if (type === FilePickerType.Move || type === FilePickerType.CopyMove) {
					buttons.push({
						callback: legacyCallback(callback, FilePickerType.Move),
						label: target ? t('core', 'Move to {target}', { target }) : t('core', 'Move'),
						type: type === FilePickerType.Move ? 'primary' : 'secondary',
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
			if(!callback._clicked) {
				callback(false)
			}
		})
	},

	/**
	 * Helper for legacy API
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
					type: 'primary',
					callback: () => {
						callback._clicked = true
						callback(true)
					},
				})
				break
			case Dialogs.OK_BUTTONS:
				buttonList.push({
					label: buttons?.confirm ?? t('core', 'OK'),
					type: 'primary',
					callback: () => {
						callback._clicked = true
						callback(true)
					},
				})
				break
			default:
				console.error('Invalid call to OC.dialogs')
				break
		}
		return buttonList
	},

	_fileexistsshown: false,
	/**
	 * Displays file exists dialog
	 * @param {object} data upload object
	 * @param {object} original file with name, size and mtime
	 * @param {object} replacement file with name, size and mtime
	 * @param {object} controller with onCancel, onSkip, onReplace and onRename methods
	 * @returns {Promise} jquery promise that resolves after the dialog template was loaded
	 *
	 * @deprecated 29.0.0 Use openConflictPicker from the @nextcloud/upload package instead
	 */
	fileexists: function(data, original, replacement, controller) {
		var self = this
		var dialogDeferred = new $.Deferred()

		var getCroppedPreview = function(file) {
			var deferred = new $.Deferred()
			// Only process image files.
			var type = file.type && file.type.split('/').shift()
			if (window.FileReader && type === 'image') {
				var reader = new FileReader()
				reader.onload = function(e) {
					var blob = new Blob([e.target.result])
					window.URL = window.URL || window.webkitURL
					var originalUrl = window.URL.createObjectURL(blob)
					var image = new Image()
					image.src = originalUrl
					image.onload = function() {
						var url = crop(image)
						deferred.resolve(url)
					}
				}
				reader.readAsArrayBuffer(file)
			} else {
				deferred.reject()
			}
			return deferred
		}

		var crop = function(img) {
			var canvas = document.createElement('canvas')
			var targetSize = 96
			var width = img.width
			var height = img.height
			var x; var y; var size

			// Calculate the width and height, constraining the proportions
			if (width > height) {
				y = 0
				x = (width - height) / 2
			} else {
				y = (height - width) / 2
				x = 0
			}
			size = Math.min(width, height)

			// Set canvas size to the cropped area
			canvas.width = size
			canvas.height = size
			var ctx = canvas.getContext('2d')
			ctx.drawImage(img, x, y, size, size, 0, 0, size, size)

			// Resize the canvas to match the destination (right size uses 96px)
			resampleHermite(canvas, size, size, targetSize, targetSize)

			return canvas.toDataURL('image/png', 0.7)
		}

		/**
		 * Fast image resize/resample using Hermite filter with JavaScript.
		 *
		 * @author: ViliusL
		 *
		 * @param {*} canvas
		 * @param {number} W
		 * @param {number} H
		 * @param {number} W2
		 * @param {number} H2
		 */
		var resampleHermite = function(canvas, W, H, W2, H2) {
			W2 = Math.round(W2)
			H2 = Math.round(H2)
			var img = canvas.getContext('2d').getImageData(0, 0, W, H)
			var img2 = canvas.getContext('2d').getImageData(0, 0, W2, H2)
			var data = img.data
			var data2 = img2.data
			var ratio_w = W / W2
			var ratio_h = H / H2
			var ratio_w_half = Math.ceil(ratio_w / 2)
			var ratio_h_half = Math.ceil(ratio_h / 2)

			for (var j = 0; j < H2; j++) {
				for (var i = 0; i < W2; i++) {
					var x2 = (i + j * W2) * 4
					var weight = 0
					var weights = 0
					var weights_alpha = 0
					var gx_r = 0
					var gx_g = 0
					var gx_b = 0
					var gx_a = 0
					var center_y = (j + 0.5) * ratio_h
					for (var yy = Math.floor(j * ratio_h); yy < (j + 1) * ratio_h; yy++) {
						var dy = Math.abs(center_y - (yy + 0.5)) / ratio_h_half
						var center_x = (i + 0.5) * ratio_w
						var w0 = dy * dy // pre-calc part of w
						for (var xx = Math.floor(i * ratio_w); xx < (i + 1) * ratio_w; xx++) {
							var dx = Math.abs(center_x - (xx + 0.5)) / ratio_w_half
							var w = Math.sqrt(w0 + dx * dx)
							if (w >= -1 && w <= 1) {
								// hermite filter
								weight = 2 * w * w * w - 3 * w * w + 1
								if (weight > 0) {
									dx = 4 * (xx + yy * W)
									// alpha
									gx_a += weight * data[dx + 3]
									weights_alpha += weight
									// colors
									if (data[dx + 3] < 255) { weight = weight * data[dx + 3] / 250 }
									gx_r += weight * data[dx]
									gx_g += weight * data[dx + 1]
									gx_b += weight * data[dx + 2]
									weights += weight
								}
							}
						}
					}
					data2[x2] = gx_r / weights
					data2[x2 + 1] = gx_g / weights
					data2[x2 + 2] = gx_b / weights
					data2[x2 + 3] = gx_a / weights_alpha
				}
			}
			canvas.getContext('2d').clearRect(0, 0, Math.max(W, W2), Math.max(H, H2))
			canvas.width = W2
			canvas.height = H2
			canvas.getContext('2d').putImageData(img2, 0, 0)
		}

		var addConflict = function($conflicts, original, replacement) {

			var $conflict = $conflicts.find('.template').clone().removeClass('template').addClass('conflict')
			var $originalDiv = $conflict.find('.original')
			var $replacementDiv = $conflict.find('.replacement')

			$conflict.data('data', data)

			$conflict.find('.filename').text(original.name)
			$originalDiv.find('.size').text(OC.Util.humanFileSize(original.size))
			$originalDiv.find('.mtime').text(OC.Util.formatDate(original.mtime))
			// ie sucks
			if (replacement.size && replacement.lastModified) {
				$replacementDiv.find('.size').text(OC.Util.humanFileSize(replacement.size))
				$replacementDiv.find('.mtime').text(OC.Util.formatDate(replacement.lastModified))
			}
			var path = original.directory + '/' + original.name
			var urlSpec = {
				file: path,
				x: 96,
				y: 96,
				c: original.etag,
				forceIcon: 0
			}
			var previewpath = Files.generatePreviewUrl(urlSpec)
			// Escaping single quotes
			previewpath = previewpath.replace(/'/g, '%27')
			$originalDiv.find('.icon').css({ 'background-image': "url('" + previewpath + "')" })
			getCroppedPreview(replacement).then(
				function(path) {
					$replacementDiv.find('.icon').css('background-image', 'url(' + path + ')')
				}, function() {
					path = OC.MimeType.getIconUrl(replacement.type)
					$replacementDiv.find('.icon').css('background-image', 'url(' + path + ')')
				}
			)
			// connect checkboxes with labels
			var checkboxId = $conflicts.find('.conflict').length
			$originalDiv.find('input:checkbox').attr('id', 'checkbox_original_' + checkboxId)
			$replacementDiv.find('input:checkbox').attr('id', 'checkbox_replacement_' + checkboxId)

			$conflicts.append($conflict)

			// set more recent mtime bold
			// ie sucks
			if (replacement.lastModified > original.mtime) {
				$replacementDiv.find('.mtime').css('font-weight', 'bold')
			} else if (replacement.lastModified < original.mtime) {
				$originalDiv.find('.mtime').css('font-weight', 'bold')
			} else {
				// TODO add to same mtime collection?
			}

			// set bigger size bold
			if (replacement.size && replacement.size > original.size) {
				$replacementDiv.find('.size').css('font-weight', 'bold')
			} else if (replacement.size && replacement.size < original.size) {
				$originalDiv.find('.size').css('font-weight', 'bold')
			} else {
				// TODO add to same size collection?
			}

			// TODO show skip action for files with same size and mtime in bottom row

			// always keep readonly files

			if (original.status === 'readonly') {
				$originalDiv
					.addClass('readonly')
					.find('input[type="checkbox"]')
					.prop('checked', true)
					.prop('disabled', true)
				$originalDiv.find('.message')
					.text(t('core', 'read-only'))
			}
		}
		// var selection = controller.getSelection(data.originalFiles);
		// if (selection.defaultAction) {
		//	controller[selection.defaultAction](data);
		// } else {
		var dialogName = 'oc-dialog-fileexists-content'
		var dialogId = '#' + dialogName
		if (this._fileexistsshown) {
			// add conflict

			var $conflicts = $(dialogId + ' .conflicts')
			addConflict($conflicts, original, replacement)

			var count = $(dialogId + ' .conflict').length
			var title = n('core',
				'{count} file conflict',
				'{count} file conflicts',
				count,
				{ count: count }
			)
			$(dialogId).parent().children('.oc-dialog-title').text(title)

			// recalculate dimensions
			$(window).trigger('resize')
			dialogDeferred.resolve()
		} else {
			// create dialog
			this._fileexistsshown = true
			$.when(this._getFileExistsTemplate()).then(function($tmpl) {
				var title = t('core', 'One file conflict')
				var $dlg = $tmpl.octemplate({
					dialog_name: dialogName,
					title: title,
					type: 'fileexists',

					allnewfiles: t('core', 'New Files'),
					allexistingfiles: t('core', 'Already existing files'),

					why: t('core', 'Which files do you want to keep?'),
					what: t('core', 'If you select both versions, the copied file will have a number added to its name.')
				})
				$('body').append($dlg)

				if (original && replacement) {
					var $conflicts = $dlg.find('.conflicts')
					addConflict($conflicts, original, replacement)
				}

				var buttonlist = [{
					text: t('core', 'Cancel'),
					classes: 'cancel',
					click: function() {
						if (typeof controller.onCancel !== 'undefined') {
							controller.onCancel(data)
						}
						$(dialogId).ocdialog('close')
					}
				},
				{
					text: t('core', 'Continue'),
					classes: 'continue',
					click: function() {
						if (typeof controller.onContinue !== 'undefined') {
							controller.onContinue($(dialogId + ' .conflict'))
						}
						$(dialogId).ocdialog('close')
					}
				}]

				$(dialogId).ocdialog({
					width: 500,
					closeOnEscape: true,
					modal: true,
					buttons: buttonlist,
					closeButton: null,
					close: function() {
						self._fileexistsshown = false
						try {
							$(this).ocdialog('destroy').remove()
						} catch (e) {
							// ignore
						}
					}
				})

				$(dialogId).css('height', 'auto')

				var $primaryButton = $dlg.closest('.oc-dialog').find('button.continue')
				$primaryButton.prop('disabled', true)

				function updatePrimaryButton() {
					var checkedCount = $dlg.find('.conflicts .checkbox:checked').length
					$primaryButton.prop('disabled', checkedCount === 0)
				}

				// add checkbox toggling actions
				$(dialogId).find('.allnewfiles').on('click', function() {
					var $checkboxes = $(dialogId).find('.conflict .replacement input[type="checkbox"]')
					$checkboxes.prop('checked', $(this).prop('checked'))
				})
				$(dialogId).find('.allexistingfiles').on('click', function() {
					var $checkboxes = $(dialogId).find('.conflict .original:not(.readonly) input[type="checkbox"]')
					$checkboxes.prop('checked', $(this).prop('checked'))
				})
				$(dialogId).find('.conflicts').on('click', '.replacement,.original:not(.readonly)', function() {
					var $checkbox = $(this).find('input[type="checkbox"]')
					$checkbox.prop('checked', !$checkbox.prop('checked'))
				})
				$(dialogId).find('.conflicts').on('click', '.replacement input[type="checkbox"],.original:not(.readonly) input[type="checkbox"]', function() {
					var $checkbox = $(this)
					$checkbox.prop('checked', !$checkbox.prop('checked'))
				})

				// update counters
				$(dialogId).on('click', '.replacement,.allnewfiles', function() {
					var count = $(dialogId).find('.conflict .replacement input[type="checkbox"]:checked').length
					if (count === $(dialogId + ' .conflict').length) {
						$(dialogId).find('.allnewfiles').prop('checked', true)
						$(dialogId).find('.allnewfiles + .count').text(t('core', '(all selected)'))
					} else if (count > 0) {
						$(dialogId).find('.allnewfiles').prop('checked', false)
						$(dialogId).find('.allnewfiles + .count').text(t('core', '({count} selected)', { count: count }))
					} else {
						$(dialogId).find('.allnewfiles').prop('checked', false)
						$(dialogId).find('.allnewfiles + .count').text('')
					}
					updatePrimaryButton()
				})
				$(dialogId).on('click', '.original,.allexistingfiles', function() {
					var count = $(dialogId).find('.conflict .original input[type="checkbox"]:checked').length
					if (count === $(dialogId + ' .conflict').length) {
						$(dialogId).find('.allexistingfiles').prop('checked', true)
						$(dialogId).find('.allexistingfiles + .count').text(t('core', '(all selected)'))
					} else if (count > 0) {
						$(dialogId).find('.allexistingfiles').prop('checked', false)
						$(dialogId).find('.allexistingfiles + .count')
							.text(t('core', '({count} selected)', { count: count }))
					} else {
						$(dialogId).find('.allexistingfiles').prop('checked', false)
						$(dialogId).find('.allexistingfiles + .count').text('')
					}
					updatePrimaryButton()
				})

				dialogDeferred.resolve()
			})
				.fail(function() {
					dialogDeferred.reject()
					alert(t('core', 'Error loading file exists template'))
				})
		}
		// }
		return dialogDeferred.promise()
	},

	_getFileExistsTemplate: function() {
		var defer = $.Deferred()
		if (!this.$fileexistsTemplate) {
			var self = this
			$.get(OC.filePath('core', 'templates/legacy', 'fileexists.html'), function(tmpl) {
				self.$fileexistsTemplate = $(tmpl)
				defer.resolve(self.$fileexistsTemplate)
			})
				.fail(function() {
					defer.reject()
				})
		} else {
			defer.resolve(this.$fileexistsTemplate)
		}
		return defer.promise()
	},
}

export default Dialogs
