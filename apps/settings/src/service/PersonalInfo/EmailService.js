/**
 * @copyright 2021, Christopher Ng <chrng8@gmail.com>
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
import { confirmPassword } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'

import { ACCOUNT_PROPERTY_ENUM, SCOPE_SUFFIX } from '../../constants/AccountPropertyConstants.js'

/**
 * Save the primary email of the user
 *
 * @param {string} email the primary email
 * @return {object}
 */
export const savePrimaryEmail = async (email) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: ACCOUNT_PROPERTY_ENUM.EMAIL,
		value: email,
	})

	return res.data
}

/**
 * Save an additional email of the user
 *
 * Will be appended to the user's additional emails*
 *
 * @param {string} email the additional email
 * @return {object}
 */
export const saveAdditionalEmail = async (email) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION,
		value: email,
	})

	return res.data
}

/**
 * Save the notification email of the user
 *
 * @param {string} email the notification email
 * @return {object}
 */
export const saveNotificationEmail = async (email) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: ACCOUNT_PROPERTY_ENUM.NOTIFICATION_EMAIL,
		value: email,
	})

	return res.data
}

/**
 * Remove an additional email of the user
 *
 * @param {string} email the additional email
 * @return {object}
 */
export const removeAdditionalEmail = async (email) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}/{collection}', { userId, collection: ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION })

	await confirmPassword()

	const res = await axios.put(url, {
		key: email,
		value: '',
	})

	return res.data
}

/**
 * Update an additional email of the user
 *
 * @param {string} prevEmail the additional email to be updated
 * @param {string} newEmail the new additional email
 * @return {object}
 */
export const updateAdditionalEmail = async (prevEmail, newEmail) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}/{collection}', { userId, collection: ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION })

	await confirmPassword()

	const res = await axios.put(url, {
		key: prevEmail,
		value: newEmail,
	})

	return res.data
}

/**
 * Save the federation scope for the additional email of the user
 *
 * @param {string} email the additional email
 * @param {string} scope the federation scope
 * @return {object}
 */
export const saveAdditionalEmailScope = async (email, scope) => {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}/{collectionScope}', { userId, collectionScope: `${ACCOUNT_PROPERTY_ENUM.EMAIL_COLLECTION}${SCOPE_SUFFIX}` })

	await confirmPassword()

	const res = await axios.put(url, {
		key: email,
		value: scope,
	})

	return res.data
}
