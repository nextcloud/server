/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import { registerFileAction, FileAction } from '@nextcloud/files'
import { translatePlural as n } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import CheckSvg from '@mdi/svg/svg/check.svg?raw'

import { pendingSharesViewId } from '../files_views/shares'

export const action = new FileAction({
	id: 'accept-share',
	displayName: (nodes: Node[]) => n('files_sharing', 'Accept share', 'Accept shares', nodes.length),
	iconSvgInline: () => CheckSvg,

	enabled: (nodes, view) => nodes.length > 0 && view.id === pendingSharesViewId,

	async exec(node: Node) {
		try {
			const isRemote = !!node.attributes.remote
			const url = generateOcsUrl('apps/files_sharing/api/v1/{shareBase}/pending/{id}', {
				shareBase: isRemote ? 'remote_shares' : 'shares',
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
