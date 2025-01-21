/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, Node, View } from '@nextcloud/files'
import type { IFilePickerButton } from '@nextcloud/dialogs'
import type { FileStat, ResponseDataDetailed, WebDAVClientError } from 'webdav'
import type { MoveCopyResult } from './moveOrCopyActionUtils'

import { isAxiosError } from '@nextcloud/axios'
import { FilePickerClosed, getFilePickerBuilder, showError, showInfo, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileAction, FileType, NodeStatus, davGetClient, davRootPath, davResultToNode, davGetDefaultPropfind, getUniqueName, Permission } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { openConflictPicker, hasConflict } from '@nextcloud/upload'
import { basename, join } from 'path'
import Vue from 'vue'

import CopyIconSvg from '@mdi/svg/svg/folder-multiple.svg?raw'
import FolderMoveSvg from '@mdi/svg/svg/folder-move.svg?raw'

import { MoveCopyAction, canCopy, canMove, getQueue } from './moveOrCopyActionUtils'
import { getContents } from '../services/Files'
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
 * Create a loading notification toast
 * @param mode The move or copy mode
 * @param source Name of the node that is copied / moved
 * @param destination Destination path
 * @return {() => void} Function to hide the notification
 */
function createLoadingNotification(mode: MoveCopyAction, source: string, destination: string): () => void {
	const text = mode === MoveCopyAction.MOVE ? t('files', 'Moving "{source}" to "{destination}" …', { source, destination }) : t('files', 'Copying "{source}" to "{destination}" …', { source, destination })

	let toast: ReturnType<typeof showInfo>|undefined
	toast = showInfo(
		`<span class="icon icon-loading-small toast-loading-icon"></span> ${text}`,
		{
			isHTML: true,
			timeout: TOAST_PERMANENT_TIMEOUT,
			onRemove: () => { toast?.hideToast(); toast = undefined },
		},
	)
	return () => toast && toast.hideToast()
}

/**
 * Handle the copy/move of a node to a destination
 * This can be imported and used by other scripts/components on server
 * @param {Node} node The node to copy/move
 * @param {Folder} destination The destination to copy/move the node to
 * @param {MoveCopyAction} method The method to use for the copy/move
 * @param {boolean} overwrite Whether to overwrite the destination if it exists
 * @return {Promise<void>} A promise that resolves when the copy/move is done
 */
export const handleCopyMoveNodeTo = async (node: Node, destination: Folder, method: MoveCopyAction.COPY | MoveCopyAction.MOVE, overwrite = false) => {
	if (!destination) {
		return
	}

	if (destination.type !== FileType.Folder) {
		throw new Error(t('files', 'Destination is not a folder'))
	}

	// Do not allow to MOVE a node to the same folder it is already located
	if (method === MoveCopyAction.MOVE && node.dirname === destination.path) {
		throw new Error(t('files', 'This file/folder is already in that directory'))
	}

	/**
	 * Example:
	 * - node: /foo/bar/file.txt -> path = /foo/bar/file.txt, destination: /foo
	 *   Allow move of /foo does not start with /foo/bar/file.txt so allow
	 * - node: /foo , destination: /foo/bar
	 *   Do not allow as it would copy foo within itself
	 * - node: /foo/bar.txt, destination: /foo
	 *   Allow copy a file to the same directory
	 * - node: "/foo/bar", destination: "/foo/bar 1"
	 *   Allow to move or copy but we need to check with trailing / otherwise it would report false positive
	 */
	if (`${destination.path}/`.startsWith(`${node.path}/`)) {
		throw new Error(t('files', 'You cannot move a file/folder onto itself or into a subfolder of itself'))
	}

	// Set loading state
	Vue.set(node, 'status', NodeStatus.LOADING)
	const actionFinished = createLoadingNotification(method, node.basename, destination.path)

	const queue = getQueue()
	return await queue.add(async () => {
		const copySuffix = (index: number) => {
			if (index === 1) {
				return t('files', '(copy)') // TRANSLATORS: Mark a file as a copy of another file
			}
			return t('files', '(copy %n)', undefined, index) // TRANSLATORS: Meaning it is the n'th copy of a file
		}

		try {
			const client = davGetClient()
			const currentPath = join(davRootPath, node.path)
			const destinationPath = join(davRootPath, destination.path)

			if (method === MoveCopyAction.COPY) {
				let target = node.basename
				// If we do not allow overwriting then find an unique name
				if (!overwrite) {
					const otherNodes = await client.getDirectoryContents(destinationPath) as FileStat[]
					target = getUniqueName(
						node.basename,
						otherNodes.map((n) => n.basename),
						{
							suffix: copySuffix,
							ignoreFileExtension: node.type === FileType.Folder,
						},
					)
				}
				await client.copyFile(currentPath, join(destinationPath, target))
				// If the node is copied into current directory the view needs to be updated
				if (node.dirname === destination.path) {
					const { data } = await client.stat(
						join(destinationPath, target),
						{
							details: true,
							data: davGetDefaultPropfind(),
						},
					) as ResponseDataDetailed<FileStat>
					emit('files:node:created', davResultToNode(data))
				}
			} else {
				// show conflict file popup if we do not allow overwriting
				if (!overwrite) {
					const otherNodes = await getContents(destination.path)
					if (hasConflict([node], otherNodes.contents)) {
						try {
							// Let the user choose what to do with the conflicting files
							const { selected, renamed } = await openConflictPicker(destination.path, [node], otherNodes.contents)
							// two empty arrays: either only old files or conflict skipped -> no action required
							if (!selected.length && !renamed.length) {
								return
							}
						} catch (error) {
							// User cancelled
							showError(t('files', 'Move cancelled'))
							return
						}
					}
				}
				// getting here means either no conflict, file was renamed to keep both files
				// in a conflict, or the selected file was chosen to be kept during the conflict
				try {
					await client.moveFile(currentPath, join(destinationPath, node.basename))
				} catch (error) {
					const parser = new DOMParser()
					const text = await (error as WebDAVClientError).response?.text()
					const message = parser.parseFromString(text ?? '', 'text/xml')
						.querySelector('message')?.textContent
					if (message) {
						showError(message)
					}
					throw error
				}
				// Delete the node as it will be fetched again
				// when navigating to the destination folder
				emit('files:node:deleted', node)
			}
		} catch (error) {
			if (isAxiosError(error)) {
				if (error.response?.status === 412) {
					throw new Error(t('files', 'A file or folder with that name already exists in this folder'))
				} else if (error.response?.status === 423) {
					throw new Error(t('files', 'The files are locked'))
				} else if (error.response?.status === 404) {
					throw new Error(t('files', 'The file does not exist anymore'))
				} else if (error.message) {
					throw new Error(error.message)
				}
			}
			logger.debug(error as Error)
			throw new Error()
		} finally {
			Vue.set(node, 'status', '')
			actionFinished()
		}
	})
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

			if (selection.some((node) => (node.permissions & Permission.CREATE) === 0)) {
				// Missing 'CREATE' permissions for selected destination
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
			await handleCopyMoveNodeTo(node, result.destination, result.action)
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
				await handleCopyMoveNodeTo(node, result.destination, result.action)
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
