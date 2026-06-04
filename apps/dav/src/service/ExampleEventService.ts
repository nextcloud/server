/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Configure the creation of example events on a user's first login.
 *
 * @param enable - Whether to enable or disable the feature.
 */
export async function setCreateExampleEvent(enable: boolean): Promise<void> {
	const url = generateUrl('/apps/dav/api/exampleEvent/enable')
	await axios.post(url, {
		enable,
	})
}

/**
 * Upload a custom example event.
 *
 * @param ics - The ICS data of the event.
 */
export async function uploadExampleEvent(ics: string): Promise<void> {
	const url = generateUrl('/apps/dav/api/exampleEvent/event')
	await axios.post(url, {
		ics,
	})
}

/**
 * Delete a previously uploaded custom example event.
 */
export async function deleteExampleEvent(): Promise<void> {
	const url = generateUrl('/apps/dav/api/exampleEvent/event')
	await axios.delete(url)
}
