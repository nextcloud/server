/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { Folder, davGetDefaultPropfind, davGetFavoritesReport } from '@nextcloud/files'

import { getClient } from './WebdavClient'
import { resultToNode } from './Files'

const client = getClient()

export const getContents = async (path = '/'): Promise<ContentsWithRoot> => {
	const propfindPayload = davGetDefaultPropfind()
	const reportPayload = davGetFavoritesReport()

	// Get root folder
	let rootResponse
	if (path === '/') {
		rootResponse = await client.stat(path, {
			details: true,
			data: propfindPayload,
		}) as ResponseDataDetailed<FileStat>
	}

	const contentsResponse = await client.getDirectoryContents(path, {
		details: true,
		// Only filter favorites if we're at the root
		data: path === '/' ? reportPayload : propfindPayload,
		headers: {
			// Patched in WebdavClient.ts
			method: path === '/' ? 'REPORT' : 'PROPFIND',
		},
		includeSelf: true,
	}) as ResponseDataDetailed<FileStat[]>

	const root = rootResponse?.data || contentsResponse.data[0]
	const contents = contentsResponse.data.filter(node => node.filename !== path)

	return {
		folder: resultToNode(root) as Folder,
		contents: contents.map(resultToNode),
	}
}
