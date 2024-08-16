/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { emit } from '@nextcloud/event-bus'
import { encodePath } from '@nextcloud/paths'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { Permission, Node, View, registerFileAction, FileAction } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'
import History from '@mdi/svg/svg/history.svg?raw'

import logger from '../../../files/src/logger.ts'

registerFileAction(new FileAction({
	id: 'restore',
	displayName() {
		return t('files_trashbin', 'Restore')
	},
	iconSvgInline: () => History,

	enabled(nodes: Node[], view) {
		// Only available in the trashbin view
		if (view.id !== 'trashbin') {
			return false
		}

		// Only available if all nodes have read permission
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.READ) !== 0)
	},

	async exec(node: Node) {
		try {
			const destination = generateRemoteUrl(encodePath(`dav/trashbin/${getCurrentUser()?.uid}/restore/${node.basename}`))
			await axios({
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
			logger.error(error)
			return false
		}
	},
	async execBatch(nodes: Node[], view: View, dir: string) {
		return Promise.all(nodes.map(node => this.exec(node, view, dir)))
	},

	order: 1,
	inline: () => true,
}))
