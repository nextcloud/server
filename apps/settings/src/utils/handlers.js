/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger.js'

/**
 * @param {import('axios').AxiosError} error the error
 * @param {string?} message the message to display
 */
export const handleError = (error, message) => {
	let fullMessage = ''

	if (message) {
		fullMessage += message
	}

	if (error.response?.status === 429) {
		if (fullMessage) {
			fullMessage += '\n'
		}
		fullMessage += t('settings', 'There were too many requests from your network. Retry later or contact your administrator if this is an error.')
	}

	showError(fullMessage)
	logger.error(fullMessage || t('Error'), error)
}
