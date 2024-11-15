/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, Node, View } from '@nextcloud/files'
import type { IFilePickerButton } from '@nextcloud/dialogs'

import { FilePickerClosed, getFilePickerBuilder, showError, showInfo } from '@nextcloud/dialogs'
import { FileAction, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { basename } from 'path'
import { MoveCopyAction, type MoveCopyResult } from '../types'
import { canCopy, canMove } from '../utils/filePermissions.ts'
import { handleCopyMoveNode } from '../services/MoveOrCopyService'

import CopyIconSvg from '@mdi/svg/svg/folder-multiple.svg?raw'
import FolderMoveSvg from '@mdi/svg/svg/folder-move.svg?raw'
import logger from '../logger'

/**
 * Return the action that is possible for the given nodes
 * @param {Node[]} nodes The nodes to check against
 * @return {MoveCopyAction} The action that is possible for the given nodes
 */
const getActionForNodes = (nodes: Node[]): MoveCopyAction => {
	if (canMove(nodes)) {
		if (canCopy(nodes)) {
			return MoveCopyAction.MOVE_OR_COPY
		}
		return MoveCopyAction.MOVE
	}

	// Assuming we can copy as the enabled checks for copy permissions
	return MoveCopyAction.COPY
}

/**
 * Open a file picker for the given action
 * @param action The action to open the file picker for
 * @param dir The directory to start the file picker in
 * @param nodes The nodes to move/copy
 * @return The picked destination or false if cancelled by user
 */
async function openFilePickerForAction(
	action: MoveCopyAction,
	dir = '/',
	nodes: Node[],
): Promise<MoveCopyResult | false> {
	const { resolve, reject, promise } = Promise.withResolvers<MoveCopyResult | false>()
	const fileIDs = nodes.map(node => node.fileid).filter(Boolean)
	const filePicker = getFilePickerBuilder(t('files', 'Choose destination'))
		.allowDirectories(true)
		.setFilter((n: Node) => {
			// We don't want to show the current nodes in the file picker
			return !fileIDs.includes(n.fileid)
		})
		.setMimeTypeFilter([])
		.setMultiSelect(false)
		.startAt(dir)
		.setButtonFactory((selection: Node[], path: string) => {
			const buttons: IFilePickerButton[] = []
			const target = basename(path)

			const dirnames = nodes.map(node => node.dirname)
			const paths = nodes.map(node => node.path)

			if (action === MoveCopyAction.COPY || action === MoveCopyAction.MOVE_OR_COPY) {
				buttons.push({
					label: target ? t('files', 'Copy to {target}', { target }, undefined, { escape: false, sanitize: false }) : t('files', 'Copy'),
					type: 'primary',
					icon: CopyIconSvg,
					disabled: selection.some((node) => (node.permissions & Permission.CREATE) === 0),
					async callback(destination: Node[]) {
						resolve({
							destination: destination[0] as Folder,
							action: MoveCopyAction.COPY,
						} as MoveCopyResult)
					},
				})
			}

			// Invalid MOVE targets (but valid copy targets)
			if (dirnames.includes(path)) {
				// This file/folder is already in that directory
				return buttons
			}

			if (paths.includes(path)) {
				// You cannot move a file/folder onto itself
				return buttons
			}

			if (action === MoveCopyAction.MOVE || action === MoveCopyAction.MOVE_OR_COPY) {
				buttons.push({
					label: target ? t('files', 'Move to {target}', { target }, undefined, { escape: false, sanitize: false }) : t('files', 'Move'),
					type: action === MoveCopyAction.MOVE ? 'primary' : 'secondary',
					icon: FolderMoveSvg,
					async callback(destination: Node[]) {
						resolve({
							destination: destination[0] as Folder,
							action: MoveCopyAction.MOVE,
						} as MoveCopyResult)
					},
				})
			}

			return buttons
		})
		.build()

	filePicker.pick()
		.catch((error: Error) => {
			logger.debug(error as Error)
			if (error instanceof FilePickerClosed) {
				resolve(false)
			} else {
				reject(new Error(t('files', 'Move or copy operation failed')))
			}
		})

	return promise
}

export const action = new FileAction({
	id: 'move-copy',
	displayName(nodes: Node[]) {
		switch (getActionForNodes(nodes)) {
		case MoveCopyAction.MOVE:
			return t('files', 'Move')
		case MoveCopyAction.COPY:
			return t('files', 'Copy')
		case MoveCopyAction.MOVE_OR_COPY:
			return t('files', 'Move or copy')
		}
	},
	iconSvgInline: () => FolderMoveSvg,
	enabled(nodes: Node[], view: View) {
		// We can not copy or move in single file shares
		if (view.id === 'public-file-share') {
			return false
		}
		// We only support moving/copying files within the user folder
		if (!nodes.every(node => node.root?.startsWith('/files/'))) {
			return false
		}
		return nodes.length > 0 && (canMove(nodes) || canCopy(nodes))
	},

	async exec(node: Node, view: View, dir: string) {
		const action = getActionForNodes([node])
		let result
		try {
			result = await openFilePickerForAction(action, dir, [node])
		} catch (e) {
			logger.error(e as Error)
			return false
		}
		if (result === false) {
			showInfo(t('files', 'Cancelled move or copy of "{filename}".', { filename: node.displayname }))
			return null
		}

		try {
			await handleCopyMoveNode(node, result.destination, result.action)
			return true
		} catch (error) {
			if (error instanceof Error && !!error.message) {
				showError(error.message)
				// Silent action as we handle the toast
				return null
			}
			return false
		}
	},

	async execBatch(nodes: Node[], view: View, dir: string) {
		const action = getActionForNodes(nodes)
		const result = await openFilePickerForAction(action, dir, nodes)
		// Handle cancellation silently
		if (result === false) {
			showInfo(nodes.length === 1
				? t('files', 'Cancelled move or copy of "{filename}".', { filename: nodes[0].displayname })
				: t('files', 'Cancelled move or copy operation'),
			)
			return nodes.map(() => null)
		}

		const promises = nodes.map(async node => {
			try {
				await handleCopyMoveNode(node, result.destination, result.action)
				return true
			} catch (error) {
				logger.error(`Failed to ${result.action} node`, { node, error })
				return false
			}
		})

		// We need to keep the selection on error!
		// So we do not return null, and for batch action
		// we let the front handle the error.
		return await Promise.all(promises)
	},

	order: 15,
})
