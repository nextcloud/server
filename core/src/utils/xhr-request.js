/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRootUrl } from '@nextcloud/router'

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
 * Intercept XMLHttpRequest and fetch API calls to add X-Requested-With header
 *
 * This is also done in @nextcloud/axios but not all requests pass through that
 */
export const interceptRequests = () => {
	XMLHttpRequest.prototype.open = (function(open) {
		return function(method, url, async) {
			open.apply(this, arguments)
			if (isNextcloudUrl(url) && !this.getResponseHeader('X-Requested-With')) {
				this.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
			}
		}
	})(XMLHttpRequest.prototype.open)

	window.fetch = (function(fetch) {
		return (resource, options) => {
			// fetch allows the `input` to be either a Request object or any stringifyable value
			if (!isNextcloudUrl(resource.url ?? resource.toString())) {
				return fetch(resource, options)
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

			return fetch(resource, options)
		}
	})(window.fetch)
}
