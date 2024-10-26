/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { Permission, Node, View, FileAction } from '@nextcloud/files'
import { showInfo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import PQueue from 'p-queue'

import CloseSvg from '@mdi/svg/svg/close.svg?raw'
import NetworkOffSvg from '@mdi/svg/svg/network-off.svg?raw'
import TrashCanSvg from '@mdi/svg/svg/trash-can.svg?raw'

import logger from '../logger.ts'
import { askConfirmation, canDisconnectOnly, canUnshareOnly, deleteNode, displayName, isTrashbinEnabled } from './deleteUtils'

const queue = new PQueue({ concurrency: 5 })

export const action = new FileAction({
	id: 'delete',
	displayName,
	iconSvgInline: (nodes: Node[]) => {
		if (canUnshareOnly(nodes)) {
			return CloseSvg
		}

		if (canDisconnectOnly(nodes)) {
			return NetworkOffSvg
		}

		return TrashCanSvg
	},

	enabled(nodes: Node[]) {
		return nodes.length > 0 && nodes
			.map(node => node.permissions)
			.every(permission => (permission & Permission.DELETE) !== 0)
	},

	async exec(node: Node, view: View) {
		try {
			let confirm = true

			// If trashbin is disabled, we need to ask for confirmation
			if (!isTrashbinEnabled()) {
				confirm = await askConfirmation([node], view)
			}

			// If the user cancels the deletion, we don't want to do anything
			if (confirm === false) {
				showInfo(t('files', 'Deletion cancelled'))
				return null
			}

			await deleteNode(node)

			return true
		} catch (error) {
			logger.error('Error while deleting a file', { error, source: node.source, node })
			return false
		}
	},

	async execBatch(nodes: Node[], view: View): Promise<(boolean | null)[]> {
		let confirm = true

		// If trashbin is disabled, we need to ask for confirmation
		if (!isTrashbinEnabled()) {
			confirm = await askConfirmation(nodes, view)
		} else if (nodes.length >= 5 && !canUnshareOnly(nodes) && !canDisconnectOnly(nodes)) {
			confirm = await askConfirmation(nodes, view)
		}

		// If the user cancels the deletion, we don't want to do anything
		if (confirm === false) {
			showInfo(t('files', 'Deletion cancelled'))
			return Promise.all(nodes.map(() => null))
		}

		// Map each node to a promise that resolves with the result of exec(node)
		const promises = nodes.map(node => {
		    // Create a promise that resolves with the result of exec(node)
		    const promise = new Promise<boolean>(resolve => {
				queue.add(async () => {
					try {
						await deleteNode(node)
						resolve(true)
					} catch (error) {
						logger.error('Error while deleting a file', { error, source: node.source, node })
						resolve(false)
					}
				})
			})
			return promise
		})

		return Promise.all(promises)
	},

	order: 100,
})
