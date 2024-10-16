/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * Fetches the current user-status
 *
 * @param {string} userId Id of the user to fetch the status
 * @return {Promise<object>}
 */
const fetchBackupStatus = async (userId) => {
	const url = generateOcsUrl('apps/user_status/api/v1/statuses/{userId}', { userId: '_' + userId })
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

/**
 * Revert the automated status
 *
 * @param {string} messageId ID of the message to revert
 * @return {Promise<object>}
 */
const revertToBackupStatus = async (messageId) => {
	const url = generateOcsUrl('apps/user_status/api/v1/user_status/revert/{messageId}', { messageId })
	const response = await HttpClient.delete(url)

	return response.data.ocs.data
}

export {
	fetchCurrentStatus,
	fetchBackupStatus,
	setStatus,
	setCustomMessage,
	setPredefinedMessage,
	clearMessage,
	revertToBackupStatus,
}
