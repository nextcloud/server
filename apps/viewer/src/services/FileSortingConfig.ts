/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
