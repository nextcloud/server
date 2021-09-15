/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

import axios from '@nextcloud/axios'
import { getCurrentUser } from '@nextcloud/auth'
import { generateOcsUrl } from '@nextcloud/router'
import confirmPassword from '@nextcloud/password-confirmation'

import { ACCOUNT_PROPERTY_ENUM, SCOPE_SUFFIX } from '../../constants/AccountPropertyConstants'

/**
 * Save the primary display name of the user
 *
 * @param {string} displayName the primary display name
 * @returns {object}
 */
export const savePrimaryDisplayName = async(displayName) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: ACCOUNT_PROPERTY_ENUM.DISPLAYNAME,
		value: displayName,
	})

	return res.data
}

/**
 * Save the federation scope for the primary display name of the user
 *
 * @param {string} scope the federation scope
 * @returns {object}
 */
export const savePrimaryDisplayNameScope = async(scope) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: `${ACCOUNT_PROPERTY_ENUM.DISPLAYNAME}${SCOPE_SUFFIX}`,
		value: scope,
	})

	return res.data
}
