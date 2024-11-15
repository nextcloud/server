/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, Node } from '@nextcloud/files'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { isAxiosError } from '@nextcloud/axios'
import { showInfo, showWarning, TOAST_PERMANENT_TIMEOUT } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import {
	davGetClient,
	davGetDefaultPropfind,
	davResultToNode,
	davRootPath,
	FileType,
	getUniqueName,
	NodeStatus,
} from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { hasConflict, openConflictPicker } from '@nextcloud/upload'
import { join } from 'path'
import { getContents } from './Files'
import { MoveCopyAction } from '../types'
import PQueue from 'p-queue'
import Vue from 'vue'
import logger from '../logger'

// This is the processing queue. We only want to allow 3 concurrent requests
let queue: PQueue

// Maximum number of concurrent operations
const MAX_CONCURRENCY = 5

/**
 * Get the processing queue
 */
function getQueue() {
	if (!queue) {
		queue = new PQueue({ concurrency: MAX_CONCURRENCY })
	}
	return queue
}

/**
 * Handle the copy/move of a node to a destination
 * This can be imported and used by other scripts/components on server
 * @param {Node} node The node to copy/move
 * @param {Folder} destination The destination to copy/move the node to
 * @param {MoveCopyAction} method The method to use for the copy/move
 * @param {boolean} overwrite Whether to overwrite the destination if it exists
 * @return A promise that resolves when the copy/move is done
 */
export async function handleCopyMoveNode(
	node: Node,
	destination: Folder,
	method: MoveCopyAction.COPY | MoveCopyAction.MOVE,
	overwrite = false,
): Promise<void> {
	if (!destination) {
		throw new SyntaxError()
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
							showWarning(t('files', 'Move cancelled'))
							return
						}
					}
				}
				// getting here means either no conflict, file was renamed to keep both files
				// in a conflict, or the selected file was chosen to be kept during the conflict
				await client.moveFile(currentPath, join(destinationPath, node.basename))
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
 * Create a loading notification toast
 * @param mode The move or copy mode
 * @param source Name of the node that is copied / moved
 * @param destination Destination path
 * @return {() => void} Function to hide the notification
 */
function createLoadingNotification(mode: MoveCopyAction, source: string, destination: string): () => void {
	const text = mode === MoveCopyAction.MOVE
		? t('files', 'Moving "{source}" to "{destination}" …', { source, destination })
		: t('files', 'Copying "{source}" to "{destination}" …', { source, destination })

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
