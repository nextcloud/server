/**
 * @copyright Copyright (c) 2024 Eduardo Morales <emoral435@gmail.com>
 *
 * @author Eduardo Morales <emoral435@gmail.com>
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
import { CancelablePromise } from 'cancelable-promise'
import type { FileStat, ResponseDataDetailed } from 'webdav';
import { davGetDefaultPropfind} from "@nextcloud/files";
import { Folder, File, type ContentsWithRoot } from '@nextcloud/files'
import { getCurrentUser } from '@nextcloud/auth';

import logger from '../logger'
import { resultToNode } from './Files';
import { getClient } from './WebdavClient';

const client = getClient()

/**
 * NOTE MOVE TO @nextcloud/files 
 * @brief filters each file/folder on its shared statuses
 * @param {FileStat} node that contains  
 * @return {Boolean}
 */
export const davNotShared = function(node: File | Folder | null, currUserID: string | undefined): Boolean {
	// (essentially .filter(Boolean))
	if (!node) return false
	
	const isNotShared = currUserID ? node.attributes['owner-id'] === currUserID : true
						&& node.attributes['mount-type'] !== 'group'
						&& node.attributes['mount-type'] !== 'shared'

	return 	isNotShared
}

export const getContents = (path: string = "/"): Promise<ContentsWithRoot> => {
    const controller = new AbortController()
	const propfindPayload = davGetDefaultPropfind()
	const currUserID = getCurrentUser()?.uid.toString()

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

			if (root.filename !== path) {
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
				}).filter(node => davNotShared(node, currUserID)) as File[],
			})
        } catch (error) {
            reject(error)
        }
    })
} 