/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot, File, Folder, Node } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { defaultRootPath, getDefaultPropfind, resultToNode as davResultToNode } from '@nextcloud/files/dav'
import { CancelablePromise } from 'cancelable-promise'
import { join } from 'path'
import { client } from './WebdavClient.ts'
import { searchNodes } from './WebDavSearch.ts'
import { getPinia } from '../store/index.ts'
import { useFilesStore } from '../store/files.ts'
import { useSearchStore } from '../store/search.ts'
import logger from '../logger.ts'
/**
 * Slim wrapper over `@nextcloud/files` `davResultToNode` to allow using the function with `Array.map`
 * @param stat The result returned by the webdav library
 */
export const resultToNode = (stat: FileStat): Node => davResultToNode(stat)

/**
 * Get contents implementation for the files view.
 * This also allows to fetch local search results when the user is currently filtering.
 *
 * @param path - The path to query
 */
export function getContents(path = '/'): CancelablePromise<ContentsWithRoot> {
	const controller = new AbortController()
	const searchStore = useSearchStore(getPinia())

	if (searchStore.query.length >= 3) {
		return new CancelablePromise((resolve, reject, cancel) => {
			cancel(() => controller.abort())
			getLocalSearch(path, searchStore.query, controller.signal)
				.then(resolve)
				.catch(reject)
		})
	} else {
		return defaultGetContents(path)
	}
}

/**
 * Generic `getContents` implementation for the users files.
 *
 * @param path - The path to get the contents
 */
export function defaultGetContents(path: string): CancelablePromise<ContentsWithRoot> {
	path = join(defaultRootPath, path)
	const controller = new AbortController()
	const propfindPayload = getDefaultPropfind()

	return new CancelablePromise(async (resolve, reject, onCancel) => {
		onCancel(() => controller.abort())

		try {
			const contentsResponse = await client.getDirectoryContents(path, {
				details: true,
				data: propfindPayload,
				includeSelf: true,
				signal: controller.signal,
			}) as ResponseDataDetailed<FileStat[]>

			const root = contentsResponse.data[0]
			const contents = contentsResponse.data.slice(1)
			if (root.filename !== path && `${root.filename}/` !== path) {
				logger.debug(`Exepected "${path}" but got filename "${root.filename}" instead.`)
				throw new Error('Root node does not match requested path')
			}

			resolve({
				folder: resultToNode(root) as Folder,
				contents: contents.map((result) => {
					try {
						return resultToNode(result)
					} catch (error) {
						logger.error(`Invalid node detected '${result.basename}'`, { error })
						return null
					}
				}).filter(Boolean) as File[],
			})
		} catch (error) {
			reject(error)
		}
	})
}

/**
 * Get the local search results for the current folder.
 *
 * @param path - The path
 * @param query - The current search query
 * @param signal - The aboort signal
 */
async function getLocalSearch(path: string, query: string, signal: AbortSignal): Promise<ContentsWithRoot> {
	const filesStore = useFilesStore(getPinia())
	let folder = filesStore.getDirectoryByPath('files', path)
	if (!folder) {
		const rootPath = join(defaultRootPath, path)
		const stat = await client.stat(rootPath, { details: true }) as ResponseDataDetailed<FileStat>
		folder = resultToNode(stat.data) as Folder
	}
	const contents = await searchNodes(query, { dir: path, signal })
	return {
		folder,
		contents,
	}
}
