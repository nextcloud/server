/**
 * @copyright Copyright (c) 2019 Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @author Marco Ambrosini <marcoambrosini@pm.me>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Louis Chemineau <louis@chmn.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
