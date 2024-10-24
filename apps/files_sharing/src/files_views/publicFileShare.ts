/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileStat, ResponseDataDetailed } from 'webdav'
import { Folder, Permission, View, davGetDefaultPropfind, davRemoteURL, davResultToNode, davRootPath, getNavigation } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { CancelablePromise } from 'cancelable-promise'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'

import { client } from '../../../files/src/services/WebdavClient'
import logger from '../services/logger'

export default () => {
	const view = new View({
		id: 'public-file-share',
		name: t('files_sharing', 'Public file share'),
		caption: t('files_sharing', 'Publicly shared file.'),

		emptyTitle: t('files_sharing', 'No file'),
		emptyCaption: t('files_sharing', 'The file shared with you will show up here'),

		icon: LinkSvg,
		order: 1,

		getContents: () => {
			return new CancelablePromise(async (resolve, reject, onCancel) => {
				const abort = new AbortController()
				onCancel(() => abort.abort())
				try {
					const node = await client.stat(
						davRootPath,
						{
							data: davGetDefaultPropfind(),
							details: true,
							signal: abort.signal,
						},
					) as ResponseDataDetailed<FileStat>

					resolve({
						// We only have one file as the content
						contents: [davResultToNode(node.data)],
						// Fake a readonly folder as root
						folder: new Folder({
							id: 0,
							source: `${davRemoteURL}${davRootPath}`,
							root: davRootPath,
							owner: null,
							permissions: Permission.READ,
							attributes: {
								// Ensure the share note is set on the root
								note: node.data.props?.note,
							},
						}),
					})
				} catch (e) {
					logger.error(e as Error)
					reject(e as Error)
				}
			})
		},
	})

	const Navigation = getNavigation()
	Navigation.register(view)
}
