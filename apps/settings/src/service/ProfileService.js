/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { getCurrentUser } from '@nextcloud/auth'
import { generateOcsUrl } from '@nextcloud/router'
import { confirmPassword } from '@nextcloud/password-confirmation'
import '@nextcloud/password-confirmation/dist/style.css'

/**
 * Save the visibility of the profile parameter
 *
 * @param {string} paramId the profile parameter ID
 * @param {string} visibility the visibility
 * @return {object}
 */
export const saveProfileParameterVisibility = async (paramId, visibility) => {
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
export const saveProfileDefault = async (isEnabled) => {
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
