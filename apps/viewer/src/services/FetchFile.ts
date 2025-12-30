/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getClient, getDefaultPropfind, getRootPath, resultToNode } from '@nextcloud/files/dav'

export default async (path: string): Promise<INode> => {
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
