/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot, File, Folder } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { getDefaultPropfind, getRootPath, resultToNode } from '@nextcloud/files/dav'
import { join } from 'path'
import logger from '../logger.ts'
import { useFilesStore } from '../store/files.ts'
import { getPinia } from '../store/index.ts'
import { useSearchStore } from '../store/search.ts'
import { client } from './WebdavClient.ts'
import { searchNodes } from './WebDavSearch.ts'

/**
 * Get contents implementation for the files view.
 * This also allows to fetch local search results when the user is currently filtering.
 *
 * @param path - The path to query
 * @param options - Options
 * @param options.signal - Abort signal to cancel the request
 */
export async function getContents(path = '/', options?: { signal: AbortSignal }): Promise<ContentsWithRoot> {
	const searchStore = useSearchStore(getPinia())

	if (searchStore.query.length < 3) {
		return await defaultGetContents(path, options)
	}

	return await getLocalSearch(path, searchStore.query, options?.signal)
}

/**
 * Generic `getContents` implementation for the users files.
 *
 * @param path - The path to get the contents
 * @param options - Options
 * @param options.signal - Abort signal to cancel the request
 */
export async function defaultGetContents(path: string, options?: { signal: AbortSignal }): Promise<ContentsWithRoot> {
	path = join(getRootPath(), path)
	const propfindPayload = getDefaultPropfind()

	const contentsResponse = await client.getDirectoryContents(path, {
		details: true,
		data: propfindPayload,
		includeSelf: true,
		signal: options?.signal,
	}) as ResponseDataDetailed<FileStat[]>

	const root = contentsResponse.data[0]!
	const contents = contentsResponse.data.slice(1)
	if (root?.filename !== path && `${root?.filename}/` !== path) {
		logger.debug(`Exepected "${path}" but got filename "${root.filename}" instead.`)
		throw new Error('Root node does not match requested path')
	}

	return {
		folder: resultToNode(root) as Folder,
		contents: contents.map((result) => {
			try {
				return resultToNode(result)
			} catch (error) {
				logger.error(`Invalid node detected '${result.basename}'`, { error })
				return null
			}
		}).filter(Boolean) as File[],
	}
}

/**
 * Get the local search results for the current folder.
 *
 * @param path - The path
 * @param query - The current search query
 * @param signal - The aboort signal
 */
async function getLocalSearch(path: string, query: string, signal?: AbortSignal): Promise<ContentsWithRoot> {
	const filesStore = useFilesStore(getPinia())
	let folder = filesStore.getDirectoryByPath('files', path)
	if (!folder) {
		const rootPath = join(getRootPath(), path)
		const stat = await client.stat(rootPath, { details: true }) as ResponseDataDetailed<FileStat>
		folder = resultToNode(stat.data) as Folder
	}
	const contents = await searchNodes(query, { dir: path, signal })
	return {
		folder,
		contents,
	}
}
