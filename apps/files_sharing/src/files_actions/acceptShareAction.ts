/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import CheckSvg from '@mdi/svg/svg/check.svg?raw'
import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { FileAction, registerFileAction } from '@nextcloud/files'
import { translatePlural as n } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { pendingSharesViewId } from '../files_views/shares.ts'

export const action = new FileAction({
	id: 'accept-share',
	displayName: ({ nodes }) => n('files_sharing', 'Accept share', 'Accept shares', nodes.length),
	iconSvgInline: () => CheckSvg,

	enabled: ({ nodes, view }) => nodes.length > 0 && view.id === pendingSharesViewId,

	async exec({ nodes }) {
		try {
			const node = nodes[0]
			const isRemote = !!node.attributes.remote
			const url = generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/pending/{id}', {
				shareBase: isRemote ? 'remote_shares' : 'shares',
				id: node.attributes.id,
			})
			await axios.post(url)

			// Remove from current view
			emit('files:node:deleted', node)

			return true
		} catch {
			return false
		}
	},
	async execBatch({ nodes, view, folder, contents }) {
		return Promise.all(nodes.map((node) => this.exec({
			nodes: [node],
			view,
			folder,
			contents,
		})))
	},

	order: 1,
	inline: () => true,
})

registerFileAction(action)
