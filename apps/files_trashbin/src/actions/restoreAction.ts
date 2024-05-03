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
import { emit } from '@nextcloud/event-bus'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { Permission, Node, View, registerFileAction, FileAction, FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import History from '@mdi/svg/svg/history.svg?raw'

import logger from '../../../files/src/logger.js'
import { encodePath } from '@nextcloud/paths'

const sortByDeletionTime = (a: Node, b: Node) => {
	const deletionTimeA = a.attributes?.['trashbin-deletion-time'] || a?.mtime || 0
	const deletionTimeB = b.attributes?.['trashbin-deletion-time'] || b?.mtime || 0
	return deletionTimeB - deletionTimeA
}

registerFileAction(new FileAction({
	id: 'restore',
	displayName() {
		return t('files_trashbin', 'Restore')
	},
	iconSvgInline: () => History,

	enabled(nodes: Node[], view) {
		// Only available in the trashbin view
		if (view.id !== 'trashbin') {
			return false
		}

		// Only available if all nodes have read permission
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.READ) !== 0)
	},

	async exec(node: Node) {
		try {
			const destination = generateRemoteUrl(encodePath(`dav/trashbin/${getCurrentUser()?.uid}/restore/${node.basename}`))
			await axios({
				method: 'MOVE',
				url: node.encodedSource,
				headers: {
					destination,
				},
			})

			// Let's pretend the file is deleted since
			// we don't know the restored location
			emit('files:node:deleted', node)
			return true
		} catch (error) {
			logger.error(error)
			return false
		}
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		// Restore folders sequentially by deletion time to preserve original directory structure
		const sortedFolderNodes = nodes
			.filter(node => node.type === FileType.Folder)
			.toSorted(sortByDeletionTime)
		const folderResults: boolean[] = []
		for (const node of sortedFolderNodes) {
			folderResults.push(await this.exec(node, view, dir) as boolean)
		}
		const fileResults = await Promise.all(
			nodes
				.filter(node => node.type === FileType.File)
				.map(node => this.exec(node, view, dir)),
		)
		return [...folderResults, ...fileResults]
	},

	order: 1,
	inline: () => true,
}))
