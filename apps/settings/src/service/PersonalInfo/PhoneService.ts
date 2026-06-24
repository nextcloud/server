/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { ACCOUNT_PROPERTY_ENUM, SCOPE_SUFFIX } from '../../constants/AccountPropertyConstants.ts'

addPasswordConfirmationInterceptors(axios)

/**
 * Save the primary phone number of the user
 *
 * @param phone the primary phone number
 */
export async function savePrimaryPhone(phone: string) {
	const userId = getCurrentUser()?.uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	const res = await axios.put<OCSResponse<[]>>(url, {
		key: ACCOUNT_PROPERTY_ENUM.PHONE,
		value: phone,
	}, { confirmPassword: PwdConfirmationMode.Strict })

	return res.data
}

/**
 * Save an additional phone number of the user
 *
 * @param phone the additional phone number
 */
export async function saveAdditionalPhone(phone: string) {
	const userId = getCurrentUser()?.uid
	const url = generateOcsUrl('cloud/users/{userId}', { userId })

	const res = await axios.put<OCSResponse<[]>>(url, {
		key: ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION,
		value: phone,
	}, { confirmPassword: PwdConfirmationMode.Strict })

	return res.data
}

/**
 * Remove an additional phone number of the user
 *
 * @param phone the additional phone number
 */
export async function removeAdditionalPhone(phone: string) {
	const userId = getCurrentUser()?.uid
	const url = generateOcsUrl('cloud/users/{userId}/{collection}', { userId, collection: ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION })

	const res = await axios.put<OCSResponse<[]>>(url, {
		key: phone,
		value: '',
	}, { confirmPassword: PwdConfirmationMode.Strict })

	return res.data
}

/**
 * Update an additional phone number of the user
 *
 * @param prevPhone the additional phone number to be updated
 * @param newPhone the new additional phone number
 */
export async function updateAdditionalPhone(prevPhone: string, newPhone: string) {
	const userId = getCurrentUser()?.uid
	const url = generateOcsUrl('cloud/users/{userId}/{collection}', { userId, collection: ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION })

	const res = await axios.put<OCSResponse<[]>>(url, {
		key: prevPhone,
		value: newPhone,
	}, { confirmPassword: PwdConfirmationMode.Strict })

	return res.data
}

/**
 * Save the federation scope for the additional phone number of the user
 *
 * @param phone the additional phone number
 * @param scope the federation scope
 */
export async function saveAdditionalPhoneScope(phone: string, scope: string) {
	const userId = getCurrentUser()?.uid
	const url = generateOcsUrl('cloud/users/{userId}/{collectionScope}', { userId, collectionScope: `${ACCOUNT_PROPERTY_ENUM.PHONE_COLLECTION}${SCOPE_SUFFIX}` })

	const res = await axios.put<OCSResponse<[]>>(url, {
		key: phone,
		value: scope,
	}, { confirmPassword: PwdConfirmationMode.Strict })

	return res.data
}
