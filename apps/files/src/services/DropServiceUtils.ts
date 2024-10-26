/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { FileStat, ResponseDataDetailed } from 'webdav'

import { emit } from '@nextcloud/event-bus'
import { Folder, Node, davGetClient, davGetDefaultPropfind, davResultToNode } from '@nextcloud/files'
import { openConflictPicker } from '@nextcloud/upload'
import { showError, showInfo } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger.ts'

/**
 * This represents a Directory in the file tree
 * We extend the File class to better handling uploading
 * and stay as close as possible as the Filesystem API.
 * This also allow us to hijack the size or lastModified
 * properties to compute them dynamically.
 */
export class Directory extends File {

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

export type RootDirectory = Directory & {
	name: 'root'
}

/**
 * Traverse a file tree using the Filesystem API
 * @param entry the entry to traverse
 */
export const traverseTree = async (entry: FileSystemEntry): Promise<Directory|File> => {
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

export const createDirectoryIfNotExists = async (absolutePath: string) => {
	const davClient = davGetClient()
	const dirExists = await davClient.exists(absolutePath)
	if (!dirExists) {
		logger.debug('Directory does not exist, creating it', { absolutePath })
		await davClient.createDirectory(absolutePath, { recursive: true })
		const stat = await davClient.stat(absolutePath, { details: true, data: davGetDefaultPropfind() }) as ResponseDataDetailed<FileStat>
		emit('files:node:created', davResultToNode(stat.data))
	}
}

export const resolveConflict = async <T extends ((Directory|File)|Node)>(files: Array<T>, destination: Folder, contents: Node[]): Promise<T[]> => {
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
