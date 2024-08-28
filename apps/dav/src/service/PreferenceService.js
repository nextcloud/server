/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Enable user status automation based on availability
 */
export async function enableUserStatusAutomation() {
	return await axios.post(
		generateOcsUrl('/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
			appId: 'dav',
			configKey: 'user_status_automation',
		}),
		{
			configValue: 'yes',
		},
	)
}

/**
 * Disable user status automation based on availability
 */
export async function disableUserStatusAutomation() {
	return await axios.delete(
		generateOcsUrl('/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
			appId: 'dav',
			configKey: 'user_status_automation',
		}),
	)
}
