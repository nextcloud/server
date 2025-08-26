/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

/**
 * Configure the creation of example events on a user's first login.
 *
 * @param {boolean} enable Whether to enable or disable the feature.
 * @return {Promise<void>}
 */
export async function setCreateExampleEvent(enable) {
	const url = generateUrl('/apps/dav/api/exampleEvent/enable')
	await axios.post(url, {
		enable,
	})
}

/**
 * Upload a custom example event.
 *
 * @param {string} ics The ICS data of the event.
 * @return {Promise<void>}
 */
export async function uploadExampleEvent(ics) {
	const url = generateUrl('/apps/dav/api/exampleEvent/event')
	await axios.post(url, {
		ics,
	})
}

/**
 * Delete a previously uploaded custom example event.
 *
 * @return {Promise<void>}
 */
export async function deleteExampleEvent() {
	const url = generateUrl('/apps/dav/api/exampleEvent/event')
	await axios.delete(url)
}
