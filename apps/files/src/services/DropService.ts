/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

import { davGetClient, davGetDefaultPropfind, davResultToNode, davRootPath } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
import { getUploader } from '@nextcloud/upload'
import { joinPaths } from '@nextcloud/paths'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import logger from '../logger.js'

export const handleDrop = async (data: DataTransfer): Promise<Upload[]> => {
	// TODO: Maybe handle `getAsFileSystemHandle()` in the future

	const uploads = [] as Upload[]
	for (const item of data.items) {
		if (item.kind !== 'file') {
			logger.debug('Skipping dropped item', { kind: item.kind, type: item.type })
			continue
		}

		// MDN recommends to try both, as it might be renamed in the future
		const entry = (item as unknown as { getAsEntry?: () => FileSystemEntry|undefined})?.getAsEntry?.() ?? item.webkitGetAsEntry()

		// Handle browser issues if Filesystem API is not available. Fallback to File API
		if (entry === null) {
			logger.debug('Could not get FilesystemEntry of item, falling back to file')
			const file = item.getAsFile()
			if (file === null) {
				logger.warn('Could not process DataTransferItem', { type: item.type, kind: item.kind })
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
