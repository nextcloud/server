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
import type { Node } from '@nextcloud/files'
import type { Navigation } from '../../../files/src/services/Navigation'

import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { translatePlural as n } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import CloseSvg from '@mdi/svg/svg/close.svg?raw'

import { FileAction, registerFileAction } from '../../../files/src/services/FileAction'
import { pendingSharesViewId } from '../views/shares'

export const action = new FileAction({
	id: 'reject-share',
	displayName: (nodes: Node[]) => n('files_sharing', 'Reject share', 'Reject shares', nodes.length),
	iconSvgInline: () => CloseSvg,

	enabled: (nodes, view) => {
		if (view.id !== pendingSharesViewId) {
			return false
		}

		if (nodes.length === 0) {
			return false
		}

		// disable rejecting group shares from the pending list because they anyway
		// land back into that same list after rejecting them
		if (nodes.some(node => node.attributes.remote_id
			&& node.attributes.share_type === window.OC.Share.SHARE_TYPE_REMOTE_GROUP)) {
			return false
		}

		return true
	},

	async exec(node: Node) {
		try {
			const isRemote = !!node.attributes.remote
			const url = generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/{id}', {
				shareBase: isRemote ? 'remote_shares' : 'shares',
				id: node.attributes.id,
			})
			await axios.delete(url)

			// Remove from current view
			emit('files:node:deleted', node)

			return true
		} catch (error) {
			return false
		}
	},
	async execBatch(nodes: Node[], view: Navigation, dir: string) {
		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 2,
	inline: () => true,
})

registerFileAction(action)
