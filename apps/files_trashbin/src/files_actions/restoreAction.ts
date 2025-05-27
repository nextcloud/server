/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { Permission, Node, View, FileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { encodePath } from '@nextcloud/paths'
import { generateRemoteUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import svgHistory from '@mdi/svg/svg/history.svg?raw'

import { TRASHBIN_VIEW_ID } from '../files_views/trashbinView.ts'
import logger from '../../../files/src/logger.ts'

export const restoreAction = new FileAction({
	id: 'restore',

	displayName() {
		return t('files_trashbin', 'Restore')
	},

	iconSvgInline: () => svgHistory,

	enabled(nodes: Node[], view) {
		// Only available in the trashbin view
		if (view.id !== TRASHBIN_VIEW_ID) {
			return false
		}

		// Only available if all nodes have read permission
		return nodes.length > 0
			&& nodes
				.map((node) => node.permissions)
				.every((permission) => Boolean(permission & Permission.READ))
	},

	async exec(node: Node) {
		try {
			const destination = generateRemoteUrl(encodePath(`dav/trashbin/${getCurrentUser()!.uid}/restore/${node.basename}`))
			await axios.request({
				method: 'MOVE',
				url: node.encodedSource,
				headers: {
					destination,
				},
			})

			// Let's pretend the file is deleted since
			// we don't know the restored location
			emit('files:node:deleted', node)
			return true
		} catch (error) {
			if (error.response?.status === 507) {
				showError(t('files_trashbin', 'Not enough free space to restore the file/folder'))
			}
			logger.error('Failed to restore node', { error, node })
			return false
		}
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 1,

	inline: () => true,
})
