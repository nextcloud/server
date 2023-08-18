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
import type { Node, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { FileAction, registerFileAction } from '@nextcloud/files'
import { generateOcsUrl } from '@nextcloud/router'
import { translatePlural as n } from '@nextcloud/l10n'
import ArrowULeftTopSvg from '@mdi/svg/svg/arrow-u-left-top.svg?raw'
import axios from '@nextcloud/axios'

import { deletedSharesViewId } from '../views/shares'

export const action = new FileAction({
	id: 'restore-share',
	displayName: (nodes: Node[]) => n('files_sharing', 'Restore share', 'Restore shares', nodes.length),

	iconSvgInline: () => ArrowULeftTopSvg,

	enabled: (nodes, view) => nodes.length > 0 && view.id === deletedSharesViewId,

	async exec(node: Node) {
		try {
			const url = generateOcsUrl('apps/files_sharing/api/v1/deletedshares/{id}', {
				id: node.attributes.id,
			})
			await axios.post(url)

			// Remove from current view
			emit('files:node:deleted', node)

			return true
		} catch (error) {
			return false
		}
	},
	async execBatch(nodes: Node[], view: View, dir: string) {
		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 1,
	inline: () => true,
})

registerFileAction(action)
