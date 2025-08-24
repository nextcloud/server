/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import { emit } from '@nextcloud/event-bus'
import { FileAction, registerFileAction } from '@nextcloud/files'
import { generateOcsUrl } from '@nextcloud/router'
import { translatePlural as n } from '@nextcloud/l10n'
import ArrowULeftTopSvg from '@mdi/svg/svg/arrow-u-left-top.svg?raw'
import axios from '@nextcloud/axios'

import { deletedSharesViewId } from '../files_views/shares'

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
