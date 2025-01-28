/*
 * @copyright Copyright (c) 2023 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl, getRootUrl } from '@nextcloud/router'

/**
 *
 * @param {string} url the URL to check
 * @returns {boolean}
 */
const isRelativeUrl = (url) => {
	return !url.startsWith('https://') && !url.startsWith('http://')
}

/**
 * @param {string} url The URL to check
 * @return {boolean} true if the URL points to this nextcloud instance
 */
const isNextcloudUrl = (url) => {
	const nextcloudBaseUrl = window.location.protocol + '//' + window.location.host + getRootUrl()
	// if the URL is absolute and starts with the baseUrl+rootUrl
	// OR if the URL is relative and starts with rootUrl
	return url.startsWith(nextcloudBaseUrl)
		|| (isRelativeUrl(url) && url.startsWith(getRootUrl()))
}

/**
 * Check if a user was logged in but is now logged-out.
 * If this is the case then the user will be forwarded to the login page.
 * @returns {Promise<void>}
 */
async function checkLoginStatus() {
	// skip if no logged in user
	if (getCurrentUser() === null) {
		return
	}

	// skip if already running
	if (checkLoginStatus.running === true) {
		return
	}

	// only run one request in parallel
	checkLoginStatus.running = true

	try {
		// We need to check this as a 401 in the first place could also come from other reasons
		const { status } = await window.fetch(generateUrl('/apps/files'))
		if (status === 401) {
			console.warn('User session was terminated, forwarding to login page.')
			window.location = generateUrl('/login?redirect_url={url}', {
				url: window.location.pathname + window.location.search + window.location.hash,
			})
		}
	} catch (error) {
		console.warn('Could not check login-state')
	} finally {
		delete checkLoginStatus.running
	}
}

/**
 * Intercept XMLHttpRequest and fetch API calls to add X-Requested-With header
 *
 * This is also done in @nextcloud/axios but not all requests pass through that
 */
export const interceptRequests = () => {
	XMLHttpRequest.prototype.open = (function(open) {
		return function(method, url, async) {
			open.apply(this, arguments)
			if (isNextcloudUrl(url)) {
				if (!this.getResponseHeader('X-Requested-With')) {
					this.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
				}
				this.addEventListener('loadend', function() {
					if (this.status === 401) {
						checkLoginStatus()
					}
				})
			}
		}
	})(XMLHttpRequest.prototype.open)

	window.fetch = (function(fetch) {
		return async (resource, options) => {
			// fetch allows the `input` to be either a Request object or any stringifyable value
			if (!isNextcloudUrl(resource.url ?? resource.toString())) {
				return await fetch(resource, options)
			}
			if (!options) {
				options = {}
			}
			if (!options.headers) {
				options.headers = new Headers()
			}

			if (options.headers instanceof Headers && !options.headers.has('X-Requested-With')) {
				options.headers.append('X-Requested-With', 'XMLHttpRequest')
			} else if (options.headers instanceof Object && !options.headers['X-Requested-With']) {
				options.headers['X-Requested-With'] = 'XMLHttpRequest'
			}

			const response = await fetch(resource, options)
			if (response.status === 401) {
				checkLoginStatus()
			}
			return response
		}
	})(window.fetch)
}
