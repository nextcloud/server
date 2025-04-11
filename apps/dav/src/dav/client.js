/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createClient } from 'webdav'
import memoize from 'lodash/fp/memoize.js'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser, getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'

export const getClient = memoize((service) => {
	// init webdav client
	const remote = generateRemoteUrl(`dav/${service}/${getCurrentUser().uid}`)
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

	return client
})
