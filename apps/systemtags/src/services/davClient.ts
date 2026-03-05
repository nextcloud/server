/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getClient, getDefaultPropfind, getRootPath, resultToNode } from '@nextcloud/files/dav'

export const davClient = getClient()

/**
 * Fetches a node from the given path
 *
 * @param path - The path to fetch the node from
 */
export async function fetchNode(path: string): Promise<Node> {
	const propfindPayload = getDefaultPropfind()
	const result = await davClient.stat(`${getRootPath()}${path}`, {
		details: true,
		data: propfindPayload,
	}) as ResponseDataDetailed<FileStat>
	return resultToNode(result.data)
}
