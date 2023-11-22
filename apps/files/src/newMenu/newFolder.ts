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

import { basename, extname } from 'path'
import { emit } from '@nextcloud/event-bus'
import { getCurrentUser } from '@nextcloud/auth'
import { Permission, Folder } from '@nextcloud/files'
import { showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import FolderPlusSvg from '@mdi/svg/svg/folder-plus.svg?raw'

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

// TODO: move to @nextcloud/files
export const getUniqueName = (name: string, names: string[]): string => {
	let newName = name
	let i = 1
	while (names.includes(newName)) {
		const ext = extname(name)
		newName = `${basename(name, ext)} (${i++})${ext}`
	}
	return newName
}

export const entry = {
	id: 'newFolder',
	displayName: t('files', 'New folder'),
	enabled: (context: Folder) => (context.permissions & Permission.CREATE) !== 0,
	iconSvgInline: FolderPlusSvg,
	order: 0,
	async handler(context: Folder, content: Node[]) {
		const contentNames = content.map((node: Node) => node.basename)
		const name = getUniqueName(t('files', 'New folder'), contentNames)
		const { fileid, source } = await createNewFolder(context, name)

		// Create the folder in the store
		const folder = new Folder({
			source,
			id: fileid,
			mtime: new Date(),
			owner: getCurrentUser()?.uid || null,
			permissions: Permission.ALL,
			root: context?.root || '/files/' + getCurrentUser()?.uid,
		})

		showSuccess(t('files', 'Created new folder "{name}"', { name: basename(source) }))
		logger.debug('Created new folder', { folder, source })
		emit('files:node:created', folder)
		emit('files:node:rename', folder)
	},
} as Entry
