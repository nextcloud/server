/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'
import type { Node } from '@nextcloud/files'

export const client = davGetClient()

export const fetchNode = async (node: Node): Promise<Node> => {
	const propfindPayload = davGetDefaultPropfind()
	const result = await client.stat(`${davRootPath}${node.path}`, {
		details: true,
		data: propfindPayload,
	}) as ResponseDataDetailed<FileStat>
	return davResultToNode(result.data)
}
