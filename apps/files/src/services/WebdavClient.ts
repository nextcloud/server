/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createClient, getPatcher } from 'webdav'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser, getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'

export const rootPath = `/files/${getCurrentUser()?.uid}`
export const defaultRootUrl = generateRemoteUrl('dav' + rootPath)

export const getClient = (rootUrl = defaultRootUrl) => {
	const client = createClient(rootUrl)

	// set CSRF token header
	const setHeaders = (token: string | null) => {
		client?.setHeaders({
			// Add this so the server knows it is an request from the browser
			'X-Requested-With': 'XMLHttpRequest',
			// Inject user auth
			requesttoken: token ?? '',
		});
	}

	// refresh headers when request token changes
	onRequestTokenUpdate(setHeaders)
	setHeaders(getRequestToken())

	/**
	 * Allow to override the METHOD to support dav REPORT
	 *
	 * @see https://github.com/perry-mitchell/webdav-client/blob/8d9694613c978ce7404e26a401c39a41f125f87f/source/request.ts
	 */
	const patcher = getPatcher()
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	// https://github.com/perry-mitchell/hot-patcher/issues/6
	patcher.patch('fetch', (url: string, options: RequestInit): Promise<Response> => {
		const headers = options.headers as Record<string, string>
		if (headers?.method) {
			options.method = headers.method
			delete headers.method
		}
		return fetch(url, options)
	})

	return client;
}
