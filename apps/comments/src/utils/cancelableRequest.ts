/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Creates a cancelable axios 'request object'.
 *
 * @param request the axios promise request
 * @return
 */
function cancelableRequest(request: (url: string, options?: Record<string, unknown>) => Promise<unknown>) {
	const controller = new AbortController()
	const signal = controller.signal

	/**
	 * Execute the request
	 *
	 * @param url the url to send the request to
	 * @param [options] optional config for the request
	 */
	const fetch = async function(url: string, options?: Record<string, unknown>) {
		const response = await request(
			url,
			{ signal, ...options },
		)
		return response
	}

	return {
		request: fetch,
		abort: () => controller.abort(),
	}
}

export default cancelableRequest
