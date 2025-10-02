import type { Node } from '@nextcloud/files'
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getClient, getDefaultPropfind, getRootPath, resultToNode } from '@nextcloud/files/dav'

export const client = getClient()

/**
 *
 * @param path
 */
export async function fetchNode(path: string): Promise<Node> {
	const propfindPayload = getDefaultPropfind()
	const result = await client.stat(`${getRootPath()}${path}`, {
		details: true,
		data: propfindPayload,
	}) as ResponseDataDetailed<FileStat>
	return resultToNode(result.data)
}
