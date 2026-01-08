/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileStat, ResponseDataDetailed } from 'webdav'

import LinkSvg from '@mdi/svg/svg/link.svg?raw'
import { Folder, getNavigation, Permission, View } from '@nextcloud/files'
import { getDefaultPropfind, getRemoteURL, getRootPath, resultToNode } from '@nextcloud/files/dav'
import { translate as t } from '@nextcloud/l10n'
import { client } from '../../../files/src/services/WebdavClient.ts'
import logger from '../services/logger.ts'

export default () => {
	const view = new View({
		id: 'public-file-share',
		name: t('files_sharing', 'Public file share'),
		caption: t('files_sharing', 'Publicly shared file.'),

		emptyTitle: t('files_sharing', 'No file'),
		emptyCaption: t('files_sharing', 'The file shared with you will show up here'),

		icon: LinkSvg,
		order: 1,

		getContents: async (path, { signal }) => {
			try {
				const node = await client.stat(
					getRootPath(),
					{
						data: getDefaultPropfind(),
						details: true,
						signal,
					},
				) as ResponseDataDetailed<FileStat>

				return {
					// We only have one file as the content
					contents: [resultToNode(node.data)],
					// Fake a readonly folder as root
					folder: new Folder({
						id: 0,
						source: `${getRemoteURL()}${getRootPath()}`,
						root: getRootPath(),
						owner: null,
						permissions: Permission.READ,
						attributes: {
							// Ensure the share note is set on the root
							note: node.data.props?.note,
						},
					}),
				}
			} catch (error) {
				if (signal.aborted) {
					logger.info('Fetching contents for public file share was aborted', { error })
					throw new DOMException('Aborted', 'AbortError')
				}
				logger.error('Failed to get contents for public file share', { error })
				throw error
			}
		},
	})

	const Navigation = getNavigation()
	Navigation.register(view)
}
