/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient, getDefaultPropfind, getRootPath, resultToNode } from '@nextcloud/files/dav'
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { Node } from '@nextcloud/files'

export default async (path: string): Promise<Node> => {
	if (!path.startsWith('/')) {
		path = `/${path}`
	}
	const client = getClient()
	const propfindPayload = getDefaultPropfind()
	const result = await client.stat(`${getRootPath()}${path}`, {
		details: true,
		data: propfindPayload,
	}) as ResponseDataDetailed<FileStat>
	return resultToNode(result.data)
}
