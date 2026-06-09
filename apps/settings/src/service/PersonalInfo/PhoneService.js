/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { ACCOUNT_PROPERTY_ENUM, SCOPE_SUFFIX } from '../../constants/AccountPropertyConstants.ts'

/**
 * Save the primary phone number of the user
 *
 * @param {string} phone the primary phone number
 * @return {object}
 */
export async function savePrimaryPhone(phone) {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: ACCOUNT_PROPERTY_ENUM.PHONE,
		value: phone,
	})

	return res.data
}

/**
 * Save an additional phone number of the user
 *
 * @param {string} phone the additional phone number
 * @return {object}
 */
export async function saveAdditionalPhone(phone) {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		key: ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION,
		value: phone,
	})

	return res.data
}

/**
 * Remove an additional phone number of the user
 *
 * @param {string} phone the additional phone number
 * @return {object}
 */
export async function removeAdditionalPhone(phone) {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}/{collection}', { userId, collection: ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION })

	await confirmPassword()

	const res = await axios.put(url, {
		key: phone,
		value: '',
	})

	return res.data
}

/**
 * Update an additional phone number of the user
 *
 * @param {string} prevPhone the additional phone number to be updated
 * @param {string} newPhone the new additional phone number
 * @return {object}
 */
export async function updateAdditionalPhone(prevPhone, newPhone) {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}/{collection}', { userId, collection: ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION })

	await confirmPassword()

	const res = await axios.put(url, {
		key: prevPhone,
		value: newPhone,
	})

	return res.data
}

/**
 * Save the federation scope for the additional phone number of the user
 *
 * @param {string} phone the additional phone number
 * @param {string} scope the federation scope
 * @return {object}
 */
export async function saveAdditionalPhoneScope(phone, scope) {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('cloud/users/{userId}/{collectionScope}', { userId, collectionScope: `${ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION}${SCOPE_SUFFIX}` })

	await confirmPassword()

	const res = await axios.put(url, {
		key: phone,
		value: scope,
	})

	return res.data
}
