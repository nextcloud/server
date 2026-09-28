/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createClient } from 'webdav'
import { generateRemoteUrl } from '@nextcloud/router'
import { getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'

// init webdav client
const rootPath = 'dav'
const remote = generateRemoteUrl(rootPath)
const client = createClient(remote)

// set CSRF token header
const setHeaders = (token) => {
	client.setHeaders({
		// Add this so the server knows it is an request from the browser
		'X-Requested-With': 'XMLHttpRequest',
		// Inject user auth
		requesttoken: token ?? '',
	})
}

// refresh headers when request token changes
onRequestTokenUpdate(setHeaders)
setHeaders(getRequestToken())

export default client
