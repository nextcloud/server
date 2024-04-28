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
import type { Entry, Node } from '@nextcloud/files'

import { basename } from 'path'
import { emit } from '@nextcloud/event-bus'
import { getCurrentUser } from '@nextcloud/auth'
import { Permission, Folder } from '@nextcloud/files'
import { showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import FolderPlusSvg from '@mdi/svg/svg/folder-plus.svg?raw'

import { newNodeName } from '../utils/newNodeDialog'
import logger from '../logger'

type createFolderResponse = {
	fileid: number
	source: string
}

const createNewFolder = async (root: Folder, name: string): Promise<createFolderResponse> => {
	const source = root.source + '/' + name
	const encodedSource = root.encodedSource + '/' + encodeURIComponent(name)

	const response = await axios({
		method: 'MKCOL',
		url: encodedSource,
		headers: {
			Overwrite: 'F',
		},
	})
	return {
		fileid: parseInt(response.headers['oc-fileid']),
		source,
	}
}

export const entry = {
	id: 'newFolder',
	displayName: t('files', 'New folder'),
	enabled: (context: Folder) => (context.permissions & Permission.CREATE) !== 0,
	iconSvgInline: FolderPlusSvg,
	order: 0,
	async handler(context: Folder, content: Node[]) {
		const name = await newNodeName(t('files', 'New folder'), content)
		if (name !== null) {
			const { fileid, source } = await createNewFolder(context, name)
			// Create the folder in the store
			const folder = new Folder({
				source,
				id: fileid,
				mtime: new Date(),
				owner: getCurrentUser()?.uid || null,
				permissions: Permission.ALL,
				root: context?.root || '/files/' + getCurrentUser()?.uid,
				// Include mount-type from parent folder as this is inherited
				attributes: {
					'mount-type': context.attributes?.['mount-type'],
					'owner-id': context.attributes?.['owner-id'],
					'owner-display-name': context.attributes?.['owner-display-name'],
				},
			})

			showSuccess(t('files', 'Created new folder "{name}"', { name: basename(source) }))
			logger.debug('Created new folder', { folder, source })
			emit('files:node:created', folder)
			window.OCP.Files.Router.goToRoute(
				null, // use default route
				{ view: 'files', fileid: folder.fileid },
				{ dir: context.path },
			)
		}
	},
} as Entry
