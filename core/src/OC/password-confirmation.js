/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { confirmPassword, isPasswordConfirmationRequired } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'

/**
 * @namespace OC.PasswordConfirmation
 */
export default {

	requiresPasswordConfirmation() {
		return isPasswordConfirmationRequired()
	},

	/**
	 * @param {Function} callback success callback function
	 * @param {object} options options currently not used by confirmPassword
	 * @param {Function} rejectCallback error callback function
	 */
	requirePasswordConfirmation(callback, options, rejectCallback) {
		confirmPassword().then(callback, rejectCallback)
	},
}
