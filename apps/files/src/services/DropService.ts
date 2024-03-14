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
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
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
import { getUploader } from '@nextcloud/upload'
import { joinPaths } from '@nextcloud/paths'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

import { handleCopyMoveNodeTo } from '../actions/moveOrCopyAction'
import { MoveCopyAction } from '../actions/moveOrCopyActionUtils'
import logger from '../logger.js'

export const handleDrop = async (data: DataTransfer): Promise<Upload[]> => {
	// TODO: Maybe handle `getAsFileSystemHandle()` in the future

	const uploads = [] as Upload[]
	// we need to cache the entries to prevent Blink engine bug that clears the list (`data.items`) after first access props of one of the entries
	const entries = [...data.items]
		.filter((item) => {
			if (item.kind !== 'file') {
				logger.debug('Skipping dropped item', { kind: item.kind, type: item.type })
				return false
			}
			return true
		})
		.map((item) => {
			// MDN recommends to try both, as it might be renamed in the future
			return (item as unknown as { getAsEntry?: () => FileSystemEntry|undefined})?.getAsEntry?.() ?? item.webkitGetAsEntry() ?? item
		})

	for (const entry of entries) {
		// Handle browser issues if Filesystem API is not available. Fallback to File API
		if (entry instanceof DataTransferItem) {
			logger.debug('Could not get FilesystemEntry of item, falling back to file')
			const file = entry.getAsFile()
			if (file === null) {
				logger.warn('Could not process DataTransferItem', { type: entry.type, kind: entry.kind })
				showError(t('files', 'One of the dropped files could not be processed'))
			} else {
				uploads.push(await handleFileUpload(file))
			}
		} else {
			logger.debug('Handle recursive upload', { entry: entry.name })
			// Use Filesystem API
			uploads.push(...await handleRecursiveUpload(entry))
		}
	}
	return uploads
}

const handleFileUpload = async (file: File, path: string = '') => {
	const uploader = getUploader()

	try {
		return await uploader.upload(`${path}${file.name}`, file)
	} catch (e) {
		showError(t('files', 'Uploading "{filename}" failed', { filename: file.name }))
		throw e
	}
}

const handleRecursiveUpload = async (entry: FileSystemEntry, path: string = ''): Promise<Upload[]> => {
	if (entry.isFile) {
		return [
			await new Promise<Upload>((resolve, reject) => {
				(entry as FileSystemFileEntry).file(
					async (file) => resolve(await handleFileUpload(file, path)),
					(error) => reject(error),
				)
			}),
		]
	} else {
		const directory = entry as FileSystemDirectoryEntry

		// TODO: Implement this on `@nextcloud/upload`
		const absolutPath = joinPaths(davRootPath, getUploader().destination.path, path, directory.name)

		logger.debug('Handle directory recursively', { name: directory.name, absolutPath })

		const davClient = davGetClient()
		const dirExists = await davClient.exists(absolutPath)
		if (!dirExists) {
			logger.debug('Directory does not exist, creating it', { absolutPath })
			await davClient.createDirectory(absolutPath, { recursive: true })
			const stat = await davClient.stat(absolutPath, { details: true, data: davGetDefaultPropfind() }) as ResponseDataDetailed<FileStat>
			emit('files:node:created', davResultToNode(stat.data))
		}

		const entries = await readDirectory(directory)
		// sorted so we upload files first before starting next level
		const promises = entries.sort((a) => a.isFile ? -1 : 1)
			.map((file) => handleRecursiveUpload(file, `${path}${directory.name}/`))
		return (await Promise.all(promises)).flat()
	}
}

/**
 * Read a directory using Filesystem API
 * @param directory the directory to read
 */
function readDirectory(directory: FileSystemDirectoryEntry) {
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

export const onDropExternalFiles = async (destination: Folder, files: FileList) => {
	const uploader = getUploader()

	// Check whether the uploader is in the same folder
	// This should never happen™
	if (!uploader.destination.path.startsWith(uploader.destination.path)) {
		logger.error('The current uploader destination is not the same as the current folder')
		showError(t('files', 'An error occurred while uploading. Please try again later.'))
		return
	}

	const previousDestination = uploader.destination
	if (uploader.destination.path !== destination.path) {
		logger.debug('Changing uploader destination', { previous: uploader.destination.path, new: destination.path })
		uploader.destination = destination
	}

	logger.debug(`Uploading files to ${destination.path}`)
	const queue = [] as Promise<Upload>[]
	for (const file of files) {
		// Because the uploader destination is properly set to the current folder
		// we can just use the basename as the relative path.
		queue.push(uploader.upload(file.name, file))
	}

	// Wait for all promises to settle
	const results = await Promise.allSettled(queue)

	// Reset the uploader destination
	uploader.destination = previousDestination

	// Check for errors
	const errors = results.filter(result => result.status === 'rejected')
	if (errors.length > 0) {
		logger.error('Error while uploading files', { errors })
		showError(t('files', 'Some files could not be uploaded'))
		return
	}

	logger.debug('Files uploaded successfully')
	showSuccess(t('files', 'Files uploaded successfully'))
}

export const onDropInternalFiles = async (destination: Folder, nodes: Node[], isCopy = false) => {
	const queue = [] as Promise<void>[]
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
