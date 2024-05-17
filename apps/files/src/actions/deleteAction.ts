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
import { Permission, Node } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import TrashCan from '@mdi/svg/svg/trash-can.svg?raw'

import { registerFileAction, FileAction } from '../services/FileAction.ts'
import logger from '../logger.js'
import type { Navigation } from '../services/Navigation.ts'
import { encodePath } from '@nextcloud/paths'

import PQueue from 'p-queue'

const queue = new PQueue({ concurrency: 1 })

registerFileAction(new FileAction({
	id: 'delete',
	displayName(nodes: Node[], view: Navigation) {
		return view.id === 'trashbin'
			? t('files_trashbin', 'Delete permanently')
			: t('files', 'Delete')
	},
	iconSvgInline: () => TrashCan,

	enabled(nodes: Node[]) {
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.DELETE) !== 0)
	},

	async exec(node: Node) {
		const { origin } = new URL(node.source)
		const encodedSource = origin + encodePath(node.source.slice(origin.length))

		try {
			await axios.delete(encodedSource)

			// Let's delete even if it's moved to the trashbin
			// since it has been removed from the current view
			//  and changing the view will trigger a reload anyway.
			emit('files:node:deleted', node)
			return true
		} catch (error) {
			logger.error('Error while deleting a file', { error, source: node.source, node })
			return false
		}
	},
	async execBatch(nodes: Node[], view: Navigation, dir: string) {
		// Map each node to a promise that resolves with the result of exec(node)
		const promises = nodes.map(node => {
		    // Create a promise that resolves with the result of exec(node)
		    const promise = new Promise<boolean>(resolve => {
				queue.add(async () => {
					const result = await this.exec(node, view, dir)
					resolve(result !== null ? result : false)
				})
			})
			return promise
		})

		return Promise.all(promises)
	},

	order: 100,
}))
