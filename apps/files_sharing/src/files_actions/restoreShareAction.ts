/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import ArrowULeftTopSvg from '@mdi/svg/svg/arrow-u-left-top.svg?raw'
import axios from '@nextcloud/axios'
import { emit } from '@nextcloud/event-bus'
import { FileAction, registerFileAction } from '@nextcloud/files'
import { translatePlural as n } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { deletedSharesViewId } from '../files_views/shares.ts'

export const action = new FileAction({
	id: 'restore-share',
	displayName: ({ nodes }) => n('files_sharing', 'Restore share', 'Restore shares', nodes.length),

	iconSvgInline: () => ArrowULeftTopSvg,

	enabled: ({ nodes, view }) => nodes.length > 0 && view.id === deletedSharesViewId,

	async exec({ nodes }) {
		try {
			const node = nodes[0]
			const url = generateOcsUrl('apps/files_sharing/api/v1/deletedshares/{id}', {
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
		return Promise.all(nodes.map((node) => this.exec({ nodes: [node], view, folder, contents })))
	},

	order: 1,
	inline: () => true,
})

registerFileAction(action)
