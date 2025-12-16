/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import svgHistory from '@mdi/svg/svg/history.svg?raw'
import { getCurrentUser } from '@nextcloud/auth'
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileAction, Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { encodePath } from '@nextcloud/paths'
import { generateRemoteUrl } from '@nextcloud/router'
import { TRASHBIN_VIEW_ID } from '../files_views/trashbinView.ts'
import { logger } from '../logger.ts'

export const restoreAction = new FileAction({
	id: 'restore',

	displayName() {
		return t('files_trashbin', 'Restore')
	},

	iconSvgInline: () => svgHistory,

	enabled({ nodes, view }) {
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

	async exec({ nodes }) {
		const node = nodes[0]
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
			if (isAxiosError(error) && error.response?.status === 507) {
				showError(t('files_trashbin', 'Not enough free space to restore the file/folder'))
			}
			logger.error('Failed to restore node', { error, node })
			return false
		}
	},

	async execBatch({ nodes, view, folder, contents }) {
		return Promise.all(nodes.map((node) => this.exec({ nodes: [node], view, folder, contents })))
	},

	order: 1,

	inline: () => true,
})
