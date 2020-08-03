/**
 * @copyright 2020, John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'

export const defaultLimit = loadState('unified-search', 'limit-default')

/**
 * Get the list of available search providers
 */
export async function getTypes() {
	try {
		const { data } = await axios.get(generateUrl('/search/providers'))
		if (Array.isArray(data) && data.length > 0) {
			return data
		}
	} catch (error) {
		console.error(error)
	}
	return []
}

/**
 * Get the list of available search providers
 *
 * @param {string} type the type to search
 * @param {string} query the search
 * @returns {Promise}
 */
export function search(type, query) {
	return axios.get(generateUrl(`/search/providers/${type}/search?term=${query}`))
}
