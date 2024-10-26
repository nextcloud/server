/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

/**
 * A little class to manage a status field for a "saving" process.
 * It can be used to display a starting message (e.g. "Saving...") and then
 * replace it with a green success message or a red error message.
 *
 * @namespace OC.msg
 */
export default {
	/**
	 * Displayes a "Saving..." message in the given message placeholder
	 *
	 * @param {object} selector    Placeholder to display the message in
	 */
	startSaving(selector) {
		this.startAction(selector, t('core', 'Saving …'))
	},

	/**
	 * Displayes a custom message in the given message placeholder
	 *
	 * @param {object} selector    Placeholder to display the message in
	 * @param {string} message    Plain text message to display (no HTML allowed)
	 */
	startAction(selector, message) {
		$(selector).text(message)
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show()
	},

	/**
	 * Displayes an success/error message in the given selector
	 *
	 * @param {object} selector    Placeholder to display the message in
	 * @param {object} response    Response of the server
	 * @param {object} response.data    Data of the servers response
	 * @param {string} response.data.message    Plain text message to display (no HTML allowed)
	 * @param {string} response.status    is being used to decide whether the message
	 * is displayed as an error/success
	 */
	finishedSaving(selector, response) {
		this.finishedAction(selector, response)
	},

	/**
	 * Displayes an success/error message in the given selector
	 *
	 * @param {object} selector    Placeholder to display the message in
	 * @param {object} response    Response of the server
	 * @param {object} response.data Data of the servers response
	 * @param {string} response.data.message Plain text message to display (no HTML allowed)
	 * @param {string} response.status is being used to decide whether the message
	 * is displayed as an error/success
	 */
	finishedAction(selector, response) {
		if (response.status === 'success') {
			this.finishedSuccess(selector, response.data.message)
		} else {
			this.finishedError(selector, response.data.message)
		}
	},

	/**
	 * Displayes an success message in the given selector
	 *
	 * @param {object} selector Placeholder to display the message in
	 * @param {string} message Plain text success message to display (no HTML allowed)
	 */
	finishedSuccess(selector, message) {
		$(selector).text(message)
			.addClass('success')
			.removeClass('error')
			.stop(true, true)
			.delay(3000)
			.fadeOut(900)
			.show()
	},

	/**
	 * Displayes an error message in the given selector
	 *
	 * @param {object} selector Placeholder to display the message in
	 * @param {string} message Plain text error message to display (no HTML allowed)
	 */
	finishedError(selector, message) {
		$(selector).text(message)
			.addClass('error')
			.removeClass('success')
			.show()
	},
}
