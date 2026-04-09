/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { confirmPassword, isPasswordConfirmationRequired } from '@nextcloud/password-confirmation'

/**
 * @namespace OC.PasswordConfirmation
 */
export default {

	/**
	 * @deprecated 28.0.0 use methods from '@nextcloud/password-confirmation'
	 */
	requiresPasswordConfirmation() {
		return isPasswordConfirmationRequired()
	},

	/**
	 * @param {Function} callback success callback function
	 * @param {object} options options currently not used by confirmPassword
	 * @param {Function} rejectCallback error callback function
	 *
	 * @deprecated 28.0.0 use methods from '@nextcloud/password-confirmation'
	 */
	requirePasswordConfirmation(callback, options, rejectCallback) {
		confirmPassword().then(callback, rejectCallback)
	},
}
