/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import _ from 'underscore'
/** @typedef {import('jquery')} jQuery */
import $ from 'jquery'
import { showMessage, TOAST_DEFAULT_TIMEOUT, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'

/**
 * @todo Write documentation
 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package instead
 * @namespace OC.Notification
 */
export default {

	updatableNotification: null,

	getDefaultNotificationFunction: null,

	/**
	 * @param {Function} callback callback function
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	setDefault(callback) {
		this.getDefaultNotificationFunction = callback
	},

	/**
	 * Hides a notification.
	 *
	 * If a row is given, only hide that one.
	 * If no row is given, hide all notifications.
	 *
	 * @param {jQuery} [$row] notification row
	 * @param {Function} [callback] callback
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	hide($row, callback) {
		if (_.isFunction($row)) {
			// first arg is the callback
			callback = $row
			$row = undefined
		}

		if (!$row) {
			console.error('Missing argument $row in OC.Notification.hide() call, caller needs to be adjusted to only dismiss its own notification')
			return
		}

		// remove the row directly
		$row.each(function() {
			if ($(this)[0].toastify) {
				$(this)[0].toastify.hideToast()
			} else {
				console.error('cannot hide toast because object is not set')
			}
			if (this === this.updatableNotification) {
				this.updatableNotification = null
			}
		})
		if (callback) {
			callback.call()
		}
		if (this.getDefaultNotificationFunction) {
			this.getDefaultNotificationFunction()
		}
	},

	/**
	 * Shows a notification as HTML without being sanitized before.
	 * If you pass unsanitized user input this may lead to a XSS vulnerability.
	 * Consider using show() instead of showHTML()
	 *
	 * @param {string} html Message to display
	 * @param {object} [options] options
	 * @param {string} [options.type] notification type
	 * @param {number} [options.timeout=0] timeout value, defaults to 0 (permanent)
	 * @return {jQuery} jQuery element for notification row
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	showHtml(html, options) {
		options = options || {}
		options.isHTML = true
		options.timeout = (!options.timeout) ? TOAST_PERMANENT_TIMEOUT : options.timeout
		const toast = showMessage(html, options)
		toast.toastElement.toastify = toast
		return $(toast.toastElement)
	},

	/**
	 * Shows a sanitized notification
	 *
	 * @param {string} text Message to display
	 * @param {object} [options] options
	 * @param {string} [options.type] notification type
	 * @param {number} [options.timeout=0] timeout value, defaults to 0 (permanent)
	 * @return {jQuery} jQuery element for notification row
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	show(text, options) {
		const escapeHTML = function(text) {
			return text.toString()
				.split('&').join('&amp;')
				.split('<').join('&lt;')
				.split('>').join('&gt;')
				.split('"').join('&quot;')
				.split('\'').join('&#039;')
		}

		options = options || {}
		options.timeout = (!options.timeout) ? TOAST_PERMANENT_TIMEOUT : options.timeout
		const toast = showMessage(escapeHTML(text), options)
		toast.toastElement.toastify = toast
		return $(toast.toastElement)
	},

	/**
	 * Updates (replaces) a sanitized notification.
	 *
	 * @param {string} text Message to display
	 * @return {jQuery} JQuery element for notificaiton row
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	showUpdate(text) {
		if (this.updatableNotification) {
			this.updatableNotification.hideToast()
		}
		this.updatableNotification = showMessage(text, { timeout: TOAST_PERMANENT_TIMEOUT })
		this.updatableNotification.toastElement.toastify = this.updatableNotification
		return $(this.updatableNotification.toastElement)
	},

	/**
	 * Shows a notification that disappears after x seconds, default is
	 * 7 seconds
	 *
	 * @param {string} text Message to show
	 * @param {Array} [options] options array
	 * @param {number} [options.timeout=7] timeout in seconds, if this is 0 it will show the message permanently
	 * @param {boolean} [options.isHTML=false] an indicator for HTML notifications (true) or text (false)
	 * @param {string} [options.type] notification type
	 * @return {JQuery} the toast element
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	showTemporary(text, options) {
		options = options || {}
		options.timeout = options.timeout || TOAST_DEFAULT_TIMEOUT
		const toast = showMessage(text, options)
		toast.toastElement.toastify = toast
		return $(toast.toastElement)
	},

	/**
	 * Returns whether a notification is hidden.
	 *
	 * @return {boolean}
	 * @deprecated 17.0.0 use the `@nextcloud/dialogs` package
	 */
	isHidden() {
		return !$('#content').find('.toastify').length
	},
}
