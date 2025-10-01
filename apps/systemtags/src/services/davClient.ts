/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'
import { generateRemoteUrl } from '@nextcloud/router'
import { createClient } from 'webdav'

// init webdav client
const rootUrl = generateRemoteUrl('dav')
export const davClient = createClient(rootUrl)

// set CSRF token header
/**
 *
 * @param token
 */
function setHeaders(token: string | null) {
	davClient.setHeaders({
		// Add this so the server knows it is an request from the browser
		'X-Requested-With': 'XMLHttpRequest',
		// Inject user auth
		requesttoken: token ?? '',
	})
}

// refresh headers when request token changes
onRequestTokenUpdate(setHeaders)
setHeaders(getRequestToken())
