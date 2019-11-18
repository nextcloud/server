/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import $ from 'jquery'

import { generateUrl } from './OC/routing'
import OC from './OC'
import { setToken as setRequestToken } from './OC/requesttoken'

/**
 * session heartbeat (defaults to enabled)
 * @returns {boolean}
 */
const keepSessionAlive = () => {
	return OC.config.session_keepalive === undefined
		|| !!OC.config.session_keepalive
}

/**
 * get interval in seconds
 * @returns {Number}
 */
const getInterval = () => {
	let interval = NaN
	if (OC.config.session_lifetime) {
		interval = Math.floor(OC.config.session_lifetime / 2)
	}

	// minimum one minute, max 24 hours, default 15 minutes
	return Math.min(
		24 * 3600,
		Math.max(
			60,
			isNaN(interval) ? 900 : interval
		)
	)
}

/**
 * Calls the server periodically to ensure that session and CSRF
 * token doesn't expire
 */
export const initSessionHeartBeat = () => {
	if (!keepSessionAlive()) {
		console.info('session heartbeat disabled')
		return
	}

	setInterval(() => {
		$.ajax(generateUrl('/csrftoken'))
			.then(resp => setRequestToken(resp.token))
			.fail(e => {
				console.error('session heartbeat failed', e)
			})
	}, getInterval() * 1000)
}
