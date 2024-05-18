/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
