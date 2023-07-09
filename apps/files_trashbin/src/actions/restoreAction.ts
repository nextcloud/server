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
import { Permission, Node } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import History from '@mdi/svg/svg/history.svg?raw'

import { registerFileAction, FileAction } from '../../../files/src/services/FileAction'
import logger from '../../../files/src/logger.js'
import type { Navigation } from '../../../files/src/services/Navigation'

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
			const destination = generateRemoteUrl(`dav/trashbin/${getCurrentUser()?.uid}/restore/${node.basename}`)
			await axios({
				method: 'MOVE',
				url: node.source,
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
	async execBatch(nodes: Node[], view: Navigation, dir: string) {
		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 1,
	inline: () => true,
}))
