/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Creates a cancelable axios 'request object'.
 *
 * @param {Function} request the axios promise request
 * @return {object}
 */
const cancelableRequest = function(request) {
	const controller = new AbortController()
	const signal = controller.signal

	/**
	 * Execute the request
	 *
	 * @param {string} url the url to send the request to
	 * @param {object} [options] optional config for the request
	 */
	const fetch = async function(url, options) {
		const response = await request(
			url,
			Object.assign({ signal }, options),
		)
		return response
	}

	return {
		request: fetch,
		abort: () => controller.abort(),
	}
}

export default cancelableRequest
