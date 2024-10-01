/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import HttpClient from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Fetches all predefined statuses from the server
 *
 * @return {Promise<void>}
 */
const fetchAllPredefinedStatuses = async () => {
	const url = generateOcsUrl('apps/user_status/api/v1/predefined_statuses?format=json')
	const response = await HttpClient.get(url)

	return response.data.ocs.data
}

export {
	fetchAllPredefinedStatuses,
}
