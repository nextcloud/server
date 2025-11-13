/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import CloseSvg from '@mdi/svg/svg/close.svg?raw'
import NetworkOffSvg from '@mdi/svg/svg/network-off.svg?raw'
import TrashCanSvg from '@mdi/svg/svg/trash-can-outline.svg?raw'
import { FileAction, Permission } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import PQueue from 'p-queue'
import { TRASHBIN_VIEW_ID } from '../../../files_trashbin/src/files_views/trashbinView.ts'
import logger from '../logger.ts'
import { askConfirmation, canDisconnectOnly, canUnshareOnly, deleteNode, displayName, shouldAskForConfirmation } from './deleteUtils.ts'

const queue = new PQueue({ concurrency: 5 })

export const ACTION_DELETE = 'delete'

export const action = new FileAction({
	id: ACTION_DELETE,
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

	enabled(nodes: Node[], view: View): boolean {
		if (view.id === TRASHBIN_VIEW_ID) {
			const config = loadState('files_trashbin', 'config', { allow_delete: true })
			if (config.allow_delete === false) {
				return false
			}
		}

		return nodes.length > 0 && nodes
			.map((node) => node.permissions)
			.every((permission) => (permission & Permission.DELETE) !== 0)
	},

	async exec(node: Node, view: View) {
		try {
			let confirm = true

			// Trick to detect if the action was called from a keyboard event
			// we need to make sure the method calling have its named containing 'keydown'
			// here we use `onKeydown` method from the FileEntryActions component
			const callStack = new Error().stack || ''
			const isCalledFromEventListener = callStack.toLocaleLowerCase().includes('keydown')

			if (shouldAskForConfirmation() || isCalledFromEventListener) {
				confirm = await askConfirmation([node], view)
			}

			// If the user cancels the deletion, we don't want to do anything
			if (confirm === false) {
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

		if (shouldAskForConfirmation()) {
			confirm = await askConfirmation(nodes, view)
		} else if (nodes.length >= 5 && !canUnshareOnly(nodes) && !canDisconnectOnly(nodes)) {
			confirm = await askConfirmation(nodes, view)
		}

		// If the user cancels the deletion, we don't want to do anything
		if (confirm === false) {
			return Promise.all(nodes.map(() => null))
		}

		// Map each node to a promise that resolves with the result of exec(node)
		const promises = nodes.map((node) => {
			// Create a promise that resolves with the result of exec(node)
			const promise = new Promise<boolean>((resolve) => {
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

	destructive: true,
	order: 100,

	hotkey: {
		description: t('files', 'Delete'),
		key: 'Delete',
	},
})
