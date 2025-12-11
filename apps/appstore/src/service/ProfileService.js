/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Save the visibility of the profile parameter
 *
 * @param {string} paramId the profile parameter ID
 * @param {string} visibility the visibility
 * @return {object}
 */
export async function saveProfileParameterVisibility(paramId, visibility) {
	const userId = getCurrentUser().uid
	const url = generateOcsUrl('/profile/{userId}', { userId })

	await confirmPassword()

	const res = await axios.put(url, {
		paramId,
		visibility,
	})

	return res.data
}

/**
 * Save profile default
 *
 * @param {boolean} isEnabled the default
 * @return {object}
 */
export async function saveProfileDefault(isEnabled) {
	// Convert to string for compatibility
	isEnabled = isEnabled ? '1' : '0'

	const url = generateOcsUrl('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
		appId: 'settings',
		key: 'profile_enabled_by_default',
	})

	await confirmPassword()

	const res = await axios.post(url, {
		value: isEnabled,
	})

	return res.data
}
