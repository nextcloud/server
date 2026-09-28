/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import HttpClient from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Sends a heartbeat
 *
 * @param {boolean} isAway Whether or not the user is active
 * @return {Promise<void>}
 */
const sendHeartbeat = async (isAway) => {
	const url = generateOcsUrl('apps/user_status/api/v1/heartbeat?format=json')
	const response = await HttpClient.put(url, {
		status: isAway ? 'away' : 'online',
	})
	return response.data.ocs.data
}

export {
	sendHeartbeat,
}
