/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
