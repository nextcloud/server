/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createClient } from 'webdav'
import { getRootPath } from '../utils/davUtils.js'
import { getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'

// init webdav client
const client = createClient(getRootPath())

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
