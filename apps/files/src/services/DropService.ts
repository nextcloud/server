/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFolder, INode } from '@nextcloud/files'
import { Folder, getNavigation, Node, NodeStatus } from '@nextcloud/files'
import { showError, showInfo, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { getUploader, hasConflict, openConflictPicker, Upload, uploadConflictHandler } from '@nextcloud/upload'
import { relative } from 'path'
import { useFilesStore } from '../store/files.ts'
import { handleCopyMoveNode } from './MoveOrCopyService.ts'
import { MoveCopyAction } from '../types.ts'
import logger from '../logger.ts'
import Vue from 'vue'

/**
 * Resolve conflicts when dropping nodes.
 *
 * @param files Files to move or upload
 * @param destination Destination folder
 * @param contents Content of the destination
 */
export async function resolveConflict<T extends INode>(files: T[], destination: Folder, contents: INode[]): Promise<T[]> {
	try {
		// List all conflicting files
		const conflicts = files.filter((file: INode) => {
			return contents.find((node: INode) => node.basename === (file instanceof File ? file.name : file.basename))
		}).filter(Boolean)

		// List of incoming files that are NOT in conflict
		const uploads = files.filter((file: T) => {
			return !conflicts.includes(file)
		})

		// Let the user choose what to do with the conflicting files
		const { selected, renamed } = await openConflictPicker(destination.path, conflicts as unknown as Node[], contents as Node[])

		logger.debug('Conflict resolution', { uploads, selected, renamed })

		// If the user selected nothing, we cancel the upload
		if (selected.length === 0 && renamed.length === 0) {
			// User skipped
			showInfo(t('files', 'Conflicts resolution skipped'))
			logger.info('User skipped the conflict resolution')
			return []
		}

		// Update the list of files to upload
		return [...uploads, ...selected, ...renamed] as (typeof files)
	} catch (error) {
		console.error(error)
		// User cancelled
		showError(t('files', 'Upload cancelled'))
		logger.error('User cancelled the upload')
	}

	return []
}

/**
 * Handle drop of internal files (e.g. drag and drop a file within the web ui)
 * @param nodes The nodes that were dragged
 * @param destination The destination where the files where dropped
 * @param isCopy True if the nodes should be copied rather than moved
 */
export async function onDropInternalFiles(nodes: INode[], destination: IFolder, isCopy = false) {
	const contents = await getContent(destination.path)
	// Check for conflicts on root elements
	if (await hasConflict(nodes as Node[], contents)) {
		nodes = await resolveConflict(nodes, destination as Folder, contents)
	}

	if (nodes.length === 0) {
		logger.info('No files to process', { nodes })
		showInfo(t('files', 'No files to process'))
		return
	}

	const promises: Promise<void>[] = []
	for (const node of nodes) {
		Vue.set(node, 'status', NodeStatus.LOADING)
		promises.push(
			handleCopyMoveNode(node as Node, destination as Folder, isCopy ? MoveCopyAction.COPY : MoveCopyAction.MOVE, true),
		)
	}

	// Wait for all promises to settle
	const results = await Promise.allSettled(promises)
	nodes.forEach(node => Vue.set(node, 'status', undefined))

	// Check for errors
	const errors = results.filter(result => result.status === 'rejected')
	if (errors.length > 0) {
		logger.error('Error while copying or moving files', { errors })
		showError(isCopy ? t('files', 'Some files could not be copied') : t('files', 'Some files could not be moved'))
		return
	}

	logger.debug('Files copy/move successful')
	showSuccess(isCopy ? t('files', 'Files copied successfully') : t('files', 'Files moved successfully'))
}

// MDN recommends to also try `getAsEntry` so we extend the interface with it
interface DataTransferItemFutureSafe extends DataTransferItem {
	webkitGetAsEntry(): FileSystemEntry | null,
	getAsEntry?(): FileSystemEntry | null
}

/**
 * This is a typescript helper function that asserts that a passed value is not null.
 * Helpful for passing as callbacks to automatically infer types for `.filter` functions.
 * @param value The value to check
 */
function isNotNull<T>(value: T|null): value is T {
	return value !== null
}

/**
 * Helper function to either use cached files or fetch directory content from server
 */
async function getContent(path: string) {
	const store = useFilesStore()
	const view = getNavigation().active!
	const nodes = store.getNodesByPath(view.id, path)
	if (nodes.length > 0) {
		return nodes
	}

	try {
		const { contents } = await view.getContents(path)
		return contents
	} catch (error) {
		logger.error('Error while fetch directory content', { error })
		return []
	}
}

export async function onDropExternalFiles(dataTransfer: DataTransfer, targetFolder: IFolder): Promise<Upload[]> {
	const items = Array.from(dataTransfer.items)

	const entries = items
		.filter((item) => {
			if (item.kind !== 'file') {
				logger.debug('Skipping dropped item', { kind: item.kind, type: item.type })
				return false
			}
			return true
		}).map((item) => (
			(item as unknown as DataTransferItemFutureSafe).getAsEntry?.()
				?? item.webkitGetAsEntry?.()
				?? item.getAsFile()
		)).filter(isNotNull)

	logger.debug(`Uploading files to ${targetFolder.path}`, { entries })

	try {
		const uploader = getUploader()
		// Create a recursive conflict handler
		const conflictHandler = uploadConflictHandler((path: string) => (
			// Make the relative path absolute to the current view
			getContent(`${targetFolder.path}${path}`)
		))

		const targetPath = relative(uploader.destination.path, targetFolder.path)
		return await uploader.batchUpload(targetPath, entries, conflictHandler)
	} catch (error) {
		logger.error('Failed to upload dropped files', { error })
		return []
	}
}
