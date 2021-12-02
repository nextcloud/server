/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

import HttpClient from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Fetches the current user-status
 *
 * @return {Promise<object>}
 */
const fetchCurrentStatus = async () => {
	const url = generateOcsUrl('apps/user_status/api/v1/user_status')
	const response = await HttpClient.get(url)

	return response.data.ocs.data
}

/**
 * Sets the status
 *
 * @param {string} statusType The status (online / away / dnd / invisible)
 * @return {Promise<void>}
 */
const setStatus = async (statusType) => {
	const url = generateOcsUrl('apps/user_status/api/v1/user_status/status')
	await HttpClient.put(url, {
		statusType,
	})
}

/**
 * Sets a message based on our predefined statuses
 *
 * @param {string} messageId The id of the message, taken from predefined status service
 * @param {number | null} clearAt When to automatically clean the status
 * @return {Promise<void>}
 */
const setPredefinedMessage = async (messageId, clearAt = null) => {
	const url = generateOcsUrl('apps/user_status/api/v1/user_status/message/predefined?format=json')
	await HttpClient.put(url, {
		messageId,
		clearAt,
	})
}

/**
 * Sets a custom message
 *
 * @param {string} message The user-defined message
 * @param {string | null} statusIcon The user-defined icon
 * @param {number | null} clearAt When to automatically clean the status
 * @return {Promise<void>}
 */
const setCustomMessage = async (message, statusIcon = null, clearAt = null) => {
	const url = generateOcsUrl('apps/user_status/api/v1/user_status/message/custom?format=json')
	await HttpClient.put(url, {
		message,
		statusIcon,
		clearAt,
	})
}

/**
 * Clears the current status of the user
 *
 * @return {Promise<void>}
 */
const clearMessage = async () => {
	const url = generateOcsUrl('apps/user_status/api/v1/user_status/message?format=json')
	await HttpClient.delete(url)
}

export {
	fetchCurrentStatus,
	setStatus,
	setCustomMessage,
	setPredefinedMessage,
	clearMessage,
}
