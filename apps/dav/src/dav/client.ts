/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { WebDAVClient } from 'webdav'

import { getCurrentUser, getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'
import { generateRemoteUrl } from '@nextcloud/router'
import { createClient } from 'webdav'

let client: WebDAVClient | undefined = undefined

/**
 * Get the WebDAV client for the current user on the calendars endpoint.
 */
export function getClient(): WebDAVClient {
	if (!client) {
		// init webdav client
		const remote = generateRemoteUrl(`dav/calendars/${getCurrentUser()!.uid}`)
		client = createClient(remote)

		// set CSRF token header
		const setHeaders = (token) => {
			client!.setHeaders({
				// Add this so the server knows it is an request from the browser
				'X-Requested-With': 'XMLHttpRequest',
				// Inject user auth
				requesttoken: token ?? '',
			})
		}

		// refresh headers when request token changes
		onRequestTokenUpdate(setHeaders)
		setHeaders(getRequestToken())
	}

	return client
}
