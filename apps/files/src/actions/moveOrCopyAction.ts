/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFilePickerButton } from '@nextcloud/dialogs'
import type { IFolder, INode } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed, WebDAVClientError } from 'webdav'
import type { MoveCopyResult } from './moveOrCopyActionUtils.ts'

import FolderMoveSvg from '@mdi/svg/svg/folder-move-outline.svg?raw'
import CopyIconSvg from '@mdi/svg/svg/folder-multiple-outline.svg?raw'
import { isAxiosError } from '@nextcloud/axios'
import { FilePickerClosed, getFilePickerBuilder, openConflictPicker, showError, showLoading } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileAction, FileType, getUniqueName, NodeStatus, Permission } from '@nextcloud/files'
import { defaultRootPath, getClient, getDefaultPropfind, resultToNode } from '@nextcloud/files/dav'
import { t } from '@nextcloud/l10n'
import { getConflicts } from '@nextcloud/upload'
import { basename, join } from 'path'
import Vue from 'vue'
import logger from '../logger.ts'
import { getContents } from '../services/Files.ts'
import { canCopy, canMove, getQueue, MoveCopyAction } from './moveOrCopyActionUtils.ts'

/**
 * Exception to hint the user about something.
 * The message is intended to be shown to the user.
 */
export class HintException extends Error {}

export const ACTION_COPY_MOVE = 'move-copy'

export const action = new FileAction({
	id: ACTION_COPY_MOVE,
	order: 15,
	displayName({ nodes }) {
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
	enabled({ nodes, view }): boolean {
		// We can not copy or move in single file shares
		if (view.id === 'public-file-share') {
			return false
		}
		// We only support moving/copying files within the user folder
		if (!nodes.every((node) => node.root?.startsWith('/files/'))) {
			return false
		}
		return nodes.length > 0 && (canMove(nodes) || canCopy(nodes))
	},

	async exec(context) {
		return this.execBatch!(context)[0]
	},

	async execBatch({ nodes, folder }) {
		const action = getActionForNodes(nodes)
		const target = await openFilePickerForAction(action, folder.path, nodes)
		// Handle cancellation silently
		if (target === false) {
			return nodes.map(() => null)
		}

		try {
			const result = await Array.fromAsync(handleCopyMoveNodesTo(nodes, target.destination, target.action))
			return result.map(() => true)
		} catch (error) {
			logger.error(`Failed to ${target.action} node`, { nodes, error })
			if (error instanceof HintException && !!error.message) {
				showError(error.message)
				// Silent action as we handle the toast
				return nodes.map(() => null)
			}
			// We need to keep the selection on error!
			// So we do not return null, and for batch action
			return nodes.map(() => false)
		}
	},
})

/**
 * Handle the copy/move of a node to a destination
 * This can be imported and used by other scripts/components on server
 *
 * @param nodes The nodes to copy/move
 * @param destination The destination to copy/move the nodes to
 * @param method The method to use for the copy/move
 * @param overwrite Whether to overwrite the destination if it exists
 * @yields {AsyncGenerator<void, void, never>} A promise that resolves when the copy/move is done
 */
export async function* handleCopyMoveNodesTo(nodes: INode[], destination: IFolder, method: MoveCopyAction.COPY | MoveCopyAction.MOVE, overwrite = false): AsyncGenerator<void, void, never> {
	if (!destination) {
		return
	}

	if (destination.type !== FileType.Folder) {
		throw new Error(t('files', 'Destination is not a folder'))
	}

	// Do not allow to MOVE a node to the same folder it is already located
	if (method === MoveCopyAction.MOVE && nodes.some((node) => node.dirname === destination.path)) {
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
	if (nodes.some((node) => `${destination.path}/`.startsWith(`${node.path}/`))) {
		throw new Error(t('files', 'You cannot move a file/folder onto itself or into a subfolder of itself'))
	}

	const nameMapping = new Map<string, string>()
	// Check for conflicts if we do not want to overwrite
	if (!overwrite) {
		const otherNodes = (await getContents(destination.path)).contents
		const conflicts = getConflicts(nodes, otherNodes) as unknown as INode[]
		const nodesToRename: INode[] = []
		if (conflicts.length > 0) {
			if (method === MoveCopyAction.MOVE) {
				// Let the user choose what to do with the conflicting files
				const content = otherNodes.filter((n) => conflicts.some((c) => c.basename === n.basename))
				const result = await openConflictPicker(destination.path, conflicts, content)
				if (!result) {
					// User cancelled
					return
				}

				nodes = nodes.filter((n) => !result.skipped.includes(n as never))
				nodesToRename.push(...(result.renamed as unknown as INode[]))
			} else {
				// for COPY we always rename conflicting files
				nodesToRename.push(...conflicts)
			}

			const usedNames = [...otherNodes, ...nodes.filter((n) => !conflicts.includes(n))].map((n) => n.basename)
			for (const node of nodesToRename) {
				const newName = getUniqueName(node.basename, usedNames, { ignoreFileExtension: node.type === FileType.Folder })
				nameMapping.set(node.source, newName)
				usedNames.push(newName) // add the new name to avoid duplicates for following re-namimgs
			}
		}
	}

	const actionFinished = createLoadingNotification(method, nodes.map((node) => node.basename), destination.path)
	const queue = getQueue()
	try {
		for (const node of nodes) {
			// Set loading state
			Vue.set(node, 'status', NodeStatus.LOADING)
			yield queue.add(async () => {
				try {
					const client = getClient()

					const currentPath = join(defaultRootPath, node.path)
					const destinationPath = join(defaultRootPath, destination.path, nameMapping.get(node.source) ?? node.basename)

					if (method === MoveCopyAction.COPY) {
						await client.copyFile(currentPath, destinationPath)
						// If the node is copied into current directory the view needs to be updated
						if (node.dirname === destination.path) {
							const { data } = await client.stat(
								destinationPath,
								{
									details: true,
									data: getDefaultPropfind(),
								},
							) as ResponseDataDetailed<FileStat>
							emit('files:node:created', resultToNode(data))
						}
					} else {
						await client.moveFile(currentPath, destinationPath)
						// Delete the node as it will be fetched again
						// when navigating to the destination folder
						emit('files:node:deleted', node)
					}
				} catch (error) {
					logger.debug(`Error while trying to ${method === MoveCopyAction.COPY ? 'copy' : 'move'} node`, { node, error })
					if (isAxiosError(error)) {
						if (error.response?.status === 412) {
							throw new HintException(t('files', 'A file or folder with that name already exists in this folder'))
						} else if (error.response?.status === 423) {
							throw new HintException(t('files', 'The files are locked'))
						} else if (error.response?.status === 404) {
							throw new HintException(t('files', 'The file does not exist anymore'))
						} else if ('response' in error && error.response) {
							const parser = new DOMParser()
							const text = await (error as WebDAVClientError).response!.text()
							const message = parser.parseFromString(text ?? '', 'text/xml')
								.querySelector('message')?.textContent
							if (message) {
								throw new HintException(message)
							}
						}
					}
					throw error
				} finally {
					Vue.set(node, 'status', undefined)
				}
			})
		}
	} finally {
		actionFinished()
	}
}

/**
 * Return the action that is possible for the given nodes
 *
 * @param nodes The nodes to check against
 * @return The action that is possible for the given nodes
 */
function getActionForNodes(nodes: INode[]): MoveCopyAction {
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
 *
 * @param mode The move or copy mode
 * @param sources Names of the nodes that are copied / moved
 * @param destination Destination path
 * @return Function to hide the notification
 */
function createLoadingNotification(mode: MoveCopyAction, sources: string[], destination: string): () => void {
	const text = mode === MoveCopyAction.MOVE
		? (sources.length === 1
				? t('files', 'Moving "{source}" to "{destination}" …', { source: sources[0], destination })
				: t('files', 'Moving {count} files to "{destination}" …', { count: sources.length, destination })
			)
		: (sources.length === 1
				? t('files', 'Copying "{source}" to "{destination}" …', { source: sources[0], destination })
				: t('files', 'Copying {count} files to "{destination}" …', { count: sources.length, destination })
			)

	const toast = showLoading(text)
	return () => toast && toast.hideToast()
}

/**
 * Open a file picker for the given action
 *
 * @param action The action to open the file picker for
 * @param dir The directory to start the file picker in
 * @param nodes The nodes to move/copy
 * @return The picked destination or false if cancelled by user
 */
async function openFilePickerForAction(
	action: MoveCopyAction,
	dir = '/',
	nodes: INode[],
): Promise<MoveCopyResult | false> {
	const { resolve, reject, promise } = Promise.withResolvers<MoveCopyResult | false>()
	const fileIDs = nodes.map((node) => node.fileid).filter(Boolean)
	const filePicker = getFilePickerBuilder(t('files', 'Choose destination'))
		.allowDirectories(true)
		.setFilter((n: INode) => {
			// We don't want to show the current nodes in the file picker
			return !fileIDs.includes(n.fileid)
		})
		.setCanPick((n) => {
			const hasCreatePermissions = (n.permissions & Permission.CREATE) === Permission.CREATE
			return hasCreatePermissions
		})
		.setMimeTypeFilter([])
		.setMultiSelect(false)
		.startAt(dir)
		.setButtonFactory((selection: INode[], path: string) => {
			const buttons: IFilePickerButton[] = []
			const target = basename(path)

			const dirnames = nodes.map((node) => node.dirname)
			const paths = nodes.map((node) => node.path)

			if (action === MoveCopyAction.COPY || action === MoveCopyAction.MOVE_OR_COPY) {
				buttons.push({
					label: target ? t('files', 'Copy to {target}', { target }, { escape: false, sanitize: false }) : t('files', 'Copy'),
					variant: 'primary',
					icon: CopyIconSvg,
					async callback(destination: INode[]) {
						resolve({
							destination: destination[0] as IFolder,
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
					variant: action === MoveCopyAction.MOVE ? 'primary' : 'secondary',
					icon: FolderMoveSvg,
					async callback(destination: INode[]) {
						resolve({
							destination: destination[0] as IFolder,
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
