/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { ContentsWithRoot } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { CancelablePromise } from 'cancelable-promise'
import { type Node, File, Folder, davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import logger from '../logger'

/**
 * Slim wrapper over `@nextcloud/files` `davResultToNode` to allow using the function with `Array.map`
 * @param node The node returned by the webdav library
 */
export const resultToNode = (node: FileStat): Node => {
	return davResultToNode(node)
}

const client = davGetClient()

export const getContents = (path = '/'): Promise<ContentsWithRoot> => {
	path = `${davRootPath}${path}`
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
				throw new Error('Root node does not match requested path')
			}

			resolve({
				folder: resultToNode(root) as Folder,
				contents: contents.map(result => {
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
