import type { ContentsWithRoot, File, Folder } from '@nextcloud/files'
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { davResultToNode, getDavNameSpaces, getDavProperties } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { client, rootPath } from './client.ts'

const data = `<?xml version="1.0"?>
<d:propfind ${getDavNameSpaces()}>
	<d:prop>
		<nc:trashbin-deletion-time />
		<nc:trashbin-original-location />
		<nc:trashbin-title />
		<nc:trashbin-deleted-by-id />
		<nc:trashbin-deleted-by-display-name />
		${getDavProperties()}
	</d:prop>
</d:propfind>`

/**
 *
 * @param stat
 */
function resultToNode(stat: FileStat): File | Folder {
	const node = davResultToNode(stat, rootPath)
	node.attributes.previewUrl = generateUrl('/apps/files_trashbin/preview?fileId={fileid}&x=32&y=32', { fileid: node.fileid })
	return node
}

/**
 *
 * @param path
 */
export async function getContents(path = '/'): Promise<ContentsWithRoot> {
	const contentsResponse = await client.getDirectoryContents(`${rootPath}${path}`, {
		details: true,
		data,
		includeSelf: true,
	}) as ResponseDataDetailed<FileStat[]>

	const contents = contentsResponse.data.map(resultToNode)
	const [folder] = contents.splice(contents.findIndex((node) => node.path === path), 1)

	return {
		folder: folder as Folder,
		contents,
	}
}
