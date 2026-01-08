/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import CloseSvg from '@mdi/svg/svg/close.svg?raw'
import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { FileAction, registerFileAction } from '@nextcloud/files'
import { translatePlural as n } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { pendingSharesViewId } from '../files_views/shares.ts'

export const action = new FileAction({
	id: 'reject-share',
	displayName: ({ nodes }) => n('files_sharing', 'Reject share', 'Reject shares', nodes.length),
	iconSvgInline: () => CloseSvg,

	enabled: ({ nodes, view }) => {
		if (view.id !== pendingSharesViewId) {
			return false
		}

		if (nodes.length === 0) {
			return false
		}

		// disable rejecting group shares from the pending list because they anyway
		// land back into that same list after rejecting them
		if (nodes.some((node) => node.attributes.remote_id
			&& node.attributes.share_type === ShareType.RemoteGroup)) {
			return false
		}

		return true
	},

	async exec({ nodes }) {
		try {
			const node = nodes[0]
			const isRemote = !!node.attributes.remote
			const shareBase = isRemote ? 'remote_shares' : 'shares'
			const id = node.attributes.id
			let url: string
			if (node.attributes.accepted === 0) {
				url = generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/pending/{id}', {
					shareBase,
					id,
				})
			} else {
				url = generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/{id}', {
					shareBase,
					id,
				})
			}
			await axios.delete(url)

			// Remove from current view
			emit('files:node:deleted', node)

			return true
		} catch {
			return false
		}
	},
	async execBatch({ nodes, view, folder, contents }) {
		return Promise.all(nodes.map((node) => this.exec({ nodes: [node], view, folder, contents })))
	},

	order: 2,
	inline: () => true,
})

registerFileAction(action)
