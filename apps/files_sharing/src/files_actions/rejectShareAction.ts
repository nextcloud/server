/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { registerFileAction, FileAction } from '@nextcloud/files'
import { translatePlural as n } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'
import { pendingSharesViewId } from '../files_views/shares'

import axios from '@nextcloud/axios'
import CloseSvg from '@mdi/svg/svg/close.svg?raw'

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
			&& node.attributes.share_type === ShareType.RemoteGroup)) {
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
	async execBatch(nodes: Node[], view: View, dir: string) {
		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 2,
	inline: () => true,
})

registerFileAction(action)
