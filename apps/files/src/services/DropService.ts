/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Upload } from '@nextcloud/upload'
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { emit } from '@nextcloud/event-bus'
import { Folder, Node, NodeStatus, davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import { getUploader, hasConflict, openConflictPicker } from '@nextcloud/upload'
import { join } from 'path'
import { joinPaths } from '@nextcloud/paths'
import { showError, showInfo, showSuccess, showWarning } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

import { handleCopyMoveNodeTo } from '../actions/moveOrCopyAction'
import { MoveCopyAction } from '../actions/moveOrCopyActionUtils'
import logger from '../logger.js'

/**
 * This represents a Directory in the file tree
 * We extend the File class to better handling uploading
 * and stay as close as possible as the Filesystem API.
 * This also allow us to hijack the size or lastModified
 * properties to compute them dynamically.
 */
class Directory extends File {

	/* eslint-disable no-use-before-define */
	_contents: (Directory|File)[]

	constructor(name, contents: (Directory|File)[] = []) {
		super([], name, { type: 'httpd/unix-directory' })
		this._contents = contents
	}

	set contents(contents: (Directory|File)[]) {
		this._contents = contents
	}

	get contents(): (Directory|File)[] {
		return this._contents
	}

	get size() {
		return this._computeDirectorySize(this)
	}

	get lastModified() {
		if (this._contents.length === 0) {
			return Date.now()
		}
		return this._computeDirectoryMtime(this)
	}

	/**
	 * Get the last modification time of a file tree
	 * This is not perfect, but will get us a pretty good approximation
	 * @param directory the directory to traverse
	 */
	_computeDirectoryMtime(directory: Directory): number {
		return directory.contents.reduce((acc, file) => {
			return file.lastModified > acc
				// If the file is a directory, the lastModified will
				// also return the results of its _computeDirectoryMtime method
				// Fancy recursion, huh?
				? file.lastModified
				: acc
		}, 0)
	}

	/**
	 * Get the size of a file tree
	 * @param directory the directory to traverse
	 */
	_computeDirectorySize(directory: Directory): number {
		return directory.contents.reduce((acc: number, entry: Directory|File) => {
			// If the file is a directory, the size will
			// also return the results of its _computeDirectorySize method
			// Fancy recursion, huh?
			return acc + entry.size
		}, 0)
	}

}

type RootDirectory = Directory & {
	name: 'root'
}

/**
 * Traverse a file tree using the Filesystem API
 * @param entry the entry to traverse
 */
const traverseTree = async (entry: FileSystemEntry): Promise<Directory|File> => {
	// Handle file
	if (entry.isFile) {
		return new Promise<File>((resolve, reject) => {
			(entry as FileSystemFileEntry).file(resolve, reject)
		})
	}

	// Handle directory
	logger.debug('Handling recursive file tree', { entry: entry.name })
	const directory = entry as FileSystemDirectoryEntry
	const entries = await readDirectory(directory)
	const contents = (await Promise.all(entries.map(traverseTree))).flat()
	return new Directory(directory.name, contents)
}

/**
 * Read a directory using Filesystem API
 * @param directory the directory to read
 */
const readDirectory = (directory: FileSystemDirectoryEntry): Promise<FileSystemEntry[]> => {
	const dirReader = directory.createReader()

	return new Promise<FileSystemEntry[]>((resolve, reject) => {
		const entries = [] as FileSystemEntry[]
		const getEntries = () => {
			dirReader.readEntries((results) => {
				if (results.length) {
					entries.push(...results)
					getEntries()
				} else {
					resolve(entries)
				}
			}, (error) => {
				reject(error)
			})
		}

		getEntries()
	})
}

const createDirectoryIfNotExists = async (absolutePath: string) => {
	const davClient = davGetClient()
	const dirExists = await davClient.exists(absolutePath)
	if (!dirExists) {
		logger.debug('Directory does not exist, creating it', { absolutePath })
		await davClient.createDirectory(absolutePath, { recursive: true })
		const stat = await davClient.stat(absolutePath, { details: true, data: davGetDefaultPropfind() }) as ResponseDataDetailed<FileStat>
		emit('files:node:created', davResultToNode(stat.data))
	}
}

const resolveConflict = async <T extends ((Directory|File)|Node)>(files: Array<T>, destination: Folder, contents: Node[]): Promise<T[]> => {
	try {
		// List all conflicting files
		const conflicts = files.filter((file: File|Node) => {
			return contents.find((node: Node) => node.basename === (file instanceof File ? file.name : file.basename))
		}).filter(Boolean) as (File|Node)[]

		// List of incoming files that are NOT in conflict
		const uploads = files.filter((file: File|Node) => {
			return !conflicts.includes(file)
		})

		// Let the user choose what to do with the conflicting files
		const { selected, renamed } = await openConflictPicker(destination.path, conflicts, contents)

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
				?? item.webkitGetAsEntry()
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
					showWarning(t('files', 'Your browser does not support the Filesystem API. Directories will not be uploaded.'))
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
		// TODO: resolve potential conflicts prior and force overwrite
		queue.push(handleCopyMoveNodeTo(node, destination, isCopy ? MoveCopyAction.COPY : MoveCopyAction.MOVE))
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
