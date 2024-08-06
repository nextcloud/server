/**
 * @copyright Copyright (c) 2023 Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
 *
 * @author Hamza Mahjoubi <hamza.mahjoubi221@proton.me>
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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { isPublicShare } from '@nextcloud/sharing/public'

/**
 * @return {object}
 */
export default async function getSortingConfig() {
	const viewConfigs = await getViewConfigs()

	if (!viewConfigs) {
		return { key: 'basename', asc: true }
	}

	const keyMap = { mtime: 'lastmod' }
	const key = keyMap[viewConfigs.sorting_mode] || viewConfigs.sorting_mode || 'basename'
	const asc = viewConfigs.sorting_direction === 'asc' || !viewConfigs.sorting_direction

	return { key, asc }
}

/**
 * @return {object}
 */
async function getViewConfigs() {
	if (isPublicShare()) {
		return null
	}
	const url = generateUrl('apps/files/api/v1/views')
	return await axios.get(url)
		.then((response) => {
			return response.data.data?.files
		})
		.catch(() => {
			return null
		})
}
