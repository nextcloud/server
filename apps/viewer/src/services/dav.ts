/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFile, IFolder } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { FileType } from '@nextcloud/files'
import { getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'

export const client = getClient()

/**
 *
 * @param folder - The folder whose file contents should be fetched
 */
export async function fetchFolderContent(folder: IFolder): Promise<IFile[]> {
	const propfindPayload = getDefaultPropfind()
	const result = (await client.getDirectoryContents(`${folder.root}${folder.path}`, {
		details: true,
		data: propfindPayload,
	})) as ResponseDataDetailed<Array<FileStat>>

	return result.data
		.map((node) => resultToNode(node))
		.filter((node) => node.type === FileType.File) as IFile[]
}
