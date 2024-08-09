/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ContentsWithRoot, File, Folder } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import { CancelablePromise } from 'cancelable-promise'
import { join } from 'path'
import { client } from './WebdavClient.ts'
import logger from '../logger.ts'

/**
 * Slim wrapper over `@nextcloud/files` `davResultToNode` to allow using the function with `Array.map`
 * @param stat The result returned by the webdav library
 */
export const resultToNode = (stat: FileStat): File | Folder => davResultToNode(stat)

export const getContents = (path = '/'): CancelablePromise<ContentsWithRoot> => {
	path = join(davRootPath, path)
	const controller = new AbortController()
	const propfindPayload = davGetDefaultPropfind()

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
