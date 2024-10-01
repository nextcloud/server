/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import axios from '@nextcloud/axios'

import { ACCOUNT_PROPERTY_ENUM, SCOPE_SUFFIX } from '../../constants/AccountPropertyConstants.js'

import '@nextcloud/password-confirmation/dist/style.css'

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
