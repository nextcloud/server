/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ContentsWithRoot, Folder, Node } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { resultToNode as davResultToNode, getDavNameSpaces, getDavProperties } from '@nextcloud/files/dav'
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
 * Converts a WebDAV file stat to a File or Folder
 * This will fix the preview URL attribute for trashbin items
 *
 * @param stat - The file stat object from WebDAV response
 */
function resultToNode(stat: FileStat): Node {
	const node = davResultToNode(stat, rootPath)
	node.attributes.previewUrl = generateUrl('/apps/files_trashbin/preview?fileId={fileid}&x=32&y=32', { fileid: node.fileid })
	return node
}

/**
 * Get the contents of a trashbin folder
 *
 * @param path - The path of the trashbin folder to get contents from
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
