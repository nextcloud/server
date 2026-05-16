/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Creates a cancelable axios 'request object'.
 *
 * @param {Function} request the axios promise request
 * @return {object}
 */
const CancelableRequest = function(request) {
	const controller = new AbortController()

	/**
	 * Execute the request
	 *
	 * @param {string} url the url to send the request to
	 * @param {object} [options] optional config for the request
	 */
	const fetch = async function(url, options) {
		return request(
			url,
			{ ...options, signal: controller.signal },
		)
	}
	return {
		request: fetch,
		cancel: () => controller.abort(),
	}
}

export default CancelableRequest
