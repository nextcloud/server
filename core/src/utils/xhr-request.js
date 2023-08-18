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

/**
 * Intercept XMLHttpRequest and fetch API calls to add X-Requested-With header
 *
 * This is also done in @nextcloud/axios but not all requests pass through that
 */
export const interceptRequests = () => {
	XMLHttpRequest.prototype.open = (function(open) {
		return function(method, url, async) {
			open.apply(this, arguments)
			if (!this.getResponseHeader('X-Requested-With')) {
				this.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
			}
		}
	})(XMLHttpRequest.prototype.open)

	window.fetch = (function(fetch) {
		return (input, init) => {
			if (!init) {
				init = {}
			}
			if (!init.headers) {
				init.headers = new Headers()
			}

			if (init.headers instanceof Headers && !init.headers.has('X-Requested-With')) {
				init.headers.append('X-Requested-With', 'XMLHttpRequest')
			} else if (init.headers instanceof Object && !init.headers['X-Requested-With']) {
				init.headers['X-Requested-With'] = 'XMLHttpRequest'
			}

			return fetch(input, init)
		}
	})(window.fetch)
}
