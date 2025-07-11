/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Upload } from '@nextcloud/upload'
import type { RootDirectory } from './DropServiceUtils'

import { Folder, Node, NodeStatus, davRootPath } from '@nextcloud/files'
import { getUploader, hasConflict } from '@nextcloud/upload'
import { join } from 'path'
import { joinPaths } from '@nextcloud/paths'
import { showError, showInfo, showSuccess, showWarning } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

import { Directory, traverseTree, resolveConflict, createDirectoryIfNotExists } from './DropServiceUtils'
import { handleCopyMoveNodeTo } from '../actions/moveOrCopyAction'
import { MoveCopyAction } from '../actions/moveOrCopyActionUtils'
import logger from '../logger.ts'

/**
 * This function converts a list of DataTransferItems to a file tree.
 * It uses the Filesystem API if available, otherwise it falls back to the File API.
 * The File API will NOT be available if the browser is not in a secure context (e.g. HTTP).
 * ⚠️ When using this method, you need to use it as fast as possible, as the DataTransferItems
 * will be cleared after the first access to the props of one of the entries.
 *
 * @param items the list of DataTransferItems
 */
export const dataTransferToFileTree = async (items: DataTransferItem[]): Promise<RootDirectory> => {
	// Check if the browser supports the Filesystem API
	// We need to cache the entries to prevent Blink engine bug that clears
	// the list (`data.items`) after first access props of one of the entries
	const entries = items
		.filter((item) => {
			if (item.kind !== 'file') {
				logger.debug('Skipping dropped item', { kind: item.kind, type: item.type })
				return false
			}
			return true
		}).map((item) => {
			// MDN recommends to try both, as it might be renamed in the future
			return (item as unknown as { getAsEntry?: () => FileSystemEntry|undefined })?.getAsEntry?.()
				?? item?.webkitGetAsEntry?.()
				?? item
		}) as (FileSystemEntry | DataTransferItem)[]

	let warned = false
	const fileTree = new Directory('root') as RootDirectory

	// Traverse the file tree
	for (const entry of entries) {
		// Handle browser issues if Filesystem API is not available. Fallback to File API
		if (entry instanceof DataTransferItem) {
			logger.warn('Could not get FilesystemEntry of item, falling back to file')

			const file = entry.getAsFile()
			if (file === null) {
				logger.warn('Could not process DataTransferItem', { type: entry.type, kind: entry.kind })
				showError(t('files', 'One of the dropped files could not be processed'))
				continue
			}

			// Warn the user that the browser does not support the Filesystem API
			// we therefore cannot upload directories recursively.
			if (file.type === 'httpd/unix-directory' || !file.type) {
				if (!warned) {
					logger.warn('Browser does not support Filesystem API. Directories will not be uploaded')
					showWarning(t('files', 'Your browser does not support the Filesystem API. Directories will not be uploaded'))
					warned = true
				}
				continue
			}

			fileTree.contents.push(file)
			continue
		}

		// Use Filesystem API
		try {
			fileTree.contents.push(await traverseTree(entry))
		} catch (error) {
			// Do not throw, as we want to continue with the other files
			logger.error('Error while traversing file tree', { error })
		}
	}

	return fileTree
}

export const onDropExternalFiles = async (root: RootDirectory, destination: Folder, contents: Node[]): Promise<Upload[]> => {
	const uploader = getUploader()

	// Check for conflicts on root elements
	if (await hasConflict(root.contents, contents)) {
		root.contents = await resolveConflict(root.contents, destination, contents)
	}

	if (root.contents.length === 0) {
		logger.info('No files to upload', { root })
		showInfo(t('files', 'No files to upload'))
		return []
	}

	// Let's process the files
	logger.debug(`Uploading files to ${destination.path}`, { root, contents: root.contents })
	const queue = [] as Promise<Upload>[]

	const uploadDirectoryContents = async (directory: Directory, path: string) => {
		for (const file of directory.contents) {
			// This is the relative path to the resource
			// from the current uploader destination
			const relativePath = join(path, file.name)

			// If the file is a directory, we need to create it first
			// then browse its tree and upload its contents.
			if (file instanceof Directory) {
				const absolutePath = joinPaths(davRootPath, destination.path, relativePath)
				try {
					console.debug('Processing directory', { relativePath })
					await createDirectoryIfNotExists(absolutePath)
					await uploadDirectoryContents(file, relativePath)
				} catch (error) {
					showError(t('files', 'Unable to create the directory {directory}', { directory: file.name }))
					logger.error('', { error, absolutePath, directory: file })
				}
				continue
			}

			// If we've reached a file, we can upload it
			logger.debug('Uploading file to ' + join(destination.path, relativePath), { file })

			// Overriding the root to avoid changing the current uploader context
			queue.push(uploader.upload(relativePath, file, destination.source))
		}
	}

	// Pause the uploader to prevent it from starting
	// while we compute the queue
	uploader.pause()

	// Upload the files. Using '/' as the starting point
	// as we already adjusted the uploader destination
	await uploadDirectoryContents(root, '/')
	uploader.start()

	// Wait for all promises to settle
	const results = await Promise.allSettled(queue)

	// Check for errors
	const errors = results.filter(result => result.status === 'rejected')
	if (errors.length > 0) {
		logger.error('Error while uploading files', { errors })
		showError(t('files', 'Some files could not be uploaded'))
		return []
	}

	logger.debug('Files uploaded successfully')
	showSuccess(t('files', 'Files uploaded successfully'))

	return Promise.all(queue)
}

export const onDropInternalFiles = async (nodes: Node[], destination: Folder, contents: Node[], isCopy = false) => {
	const queue = [] as Promise<void>[]

	// Check for conflicts on root elements
	if (await hasConflict(nodes, contents)) {
		nodes = await resolveConflict(nodes, destination, contents)
	}

	if (nodes.length === 0) {
		logger.info('No files to process', { nodes })
		showInfo(t('files', 'No files to process'))
		return
	}

	for (const node of nodes) {
		Vue.set(node, 'status', NodeStatus.LOADING)
		queue.push(handleCopyMoveNodeTo(node, destination, isCopy ? MoveCopyAction.COPY : MoveCopyAction.MOVE, true))
	}

	// Wait for all promises to settle
	const results = await Promise.allSettled(queue)
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
