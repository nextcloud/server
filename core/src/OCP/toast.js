/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	showError,
	showInfo, showMessage,
	showSuccess,
	showWarning,
} from '@nextcloud/dialogs'

/** @typedef {import('toastify-js')} Toast */

export default {
	/**
	 * @deprecated 19.0.0 use `showSuccess` from the `@nextcloud/dialogs` package instead
	 *
	 * @param {string} text the toast text
	 * @param {object} options options
	 * @return {Toast}
	 */
	success(text, options) {
		return showSuccess(text, options)
	},
	/**
	 * @deprecated 19.0.0 use `showWarning` from the `@nextcloud/dialogs` package instead
	 *
	 * @param {string} text the toast text
	 * @param {object} options options
	 * @return {Toast}
	 */
	warning(text, options) {
		return showWarning(text, options)
	},
	/**
	 * @deprecated 19.0.0 use `showError` from the `@nextcloud/dialogs` package instead
	 *
	 * @param {string} text the toast text
	 * @param {object} options options
	 * @return {Toast}
	 */
	error(text, options) {
		return showError(text, options)
	},
	/**
	 * @deprecated 19.0.0 use `showInfo` from the `@nextcloud/dialogs` package instead
	 *
	 * @param {string} text the toast text
	 * @param {object} options options
	 * @return {Toast}
	 */
	info(text, options) {
		return showInfo(text, options)
	},
	/**
	 * @deprecated 19.0.0 use `showMessage` from the `@nextcloud/dialogs` package instead
	 *
	 * @param {string} text the toast text
	 * @param {object} options options
	 * @return {Toast}
	 */
	message(text, options) {
		return showMessage(text, options)
	},

}
