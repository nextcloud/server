/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getDavNameSpaces, getDavProperties } from '@nextcloud/files'
import { client } from './WebdavClient'
import { genFileInfo, type FileInfo } from '../utils/fileUtils'
import type { FileStat, ResponseDataDetailed } from 'webdav'

/**
 * Retrieve the files list
 * @param path
 * @param options
 */
export default async function(path: string, options = {}): Promise<FileInfo[]> {
	const response = await client.getDirectoryContents(path, Object.assign({
		data: `<?xml version="1.0"?>
			<d:propfind ${getDavNameSpaces()}>
				<d:prop>
					<oc:tags />
					${getDavProperties()}
				</d:prop>
			</d:propfind>`,
		details: true,
	}, options)) as ResponseDataDetailed<FileStat[]>

	return response.data.map(genFileInfo)
}
