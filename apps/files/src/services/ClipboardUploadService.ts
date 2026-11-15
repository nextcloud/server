
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode } from '@nextcloud/files'

import { openConflictPicker, showError } from '@nextcloud/dialogs'
import { getUniqueName } from '@nextcloud/files'
import { getUploader } from '@nextcloud/files/upload'
import { translate as t } from '@nextcloud/l10n'
import { basename } from '@nextcloud/paths'
import { getConflicts } from '../utils/conflicts.ts'
import { getFilenameValidity } from '../utils/filenameValidity.ts'
import { logger } from '../utils/logger.ts'

interface ClipboardUploadContext {
	folder: IFolder
	getContents: (path: string) => Promise<INode[]>
}

// Process clipboard batches sequentially to avoid filename conflicts
// between concurrent paste operations.
let pendingClipboardUpload = Promise.resolve()

/**
 * Extract image files from a clipboard event.
 *
 * @param event - The clipboard paste event
 */
export function getClipboardImages(event: ClipboardEvent): File[] {
	const clipboardData = event.clipboardData

	if (!clipboardData) {
		return []
	}

	const images = Array.from(clipboardData.items)
		.filter((item) => item.kind === 'file')
		.map((item) => item.getAsFile())
		.filter((file): file is File => file !== null)
		.filter((file) => file.type.startsWith('image/'))

	if (images.length > 0) {
		return images
	}

	return Array.from(clipboardData.files)
		.filter((file) => file.type.startsWith('image/'))
}

/**
 * Get a filename extension for an image MIME type.
 *
 * @param mimeType - The image MIME type
 */
function getImageExtension(mimeType: string): string {
	const extensions: Record<string, string> = {
		'image/png': 'png',
		'image/jpeg': 'jpg',
		'image/gif': 'gif',
		'image/webp': 'webp',
		'image/svg+xml': 'svg',
		'image/bmp': 'bmp',
		'image/tiff': 'tiff',
		'image/avif': 'avif',
		'image/heic': 'heic',
		'image/heif': 'heif',
		'image/x-icon': 'ico',
		'image/vnd.microsoft.icon': 'ico',
	}

	return extensions[mimeType] ?? ''
}

/**
 * Prepare clipboard images with valid and unique filenames.
 *
 * @param files - The clipboard image files
 */
export function prepareClipboardImages(files: File[]): File[] {
	const usedNames: string[] = []
	const originalNames = files.map((file) => file.name)

	return files.map((file) => {
		let name = file.name

		if (!name || getFilenameValidity(name, false, false) !== '') {
			const extension = getImageExtension(file.type)
			const fallbackName = t('files', 'Clipboard')

			name = extension
				? `${fallbackName}.${extension}`
				: fallbackName
		}

		if (usedNames.includes(name)) {
			name = getUniqueName(name, [...usedNames, ...originalNames])
		}

		const validationError = getFilenameValidity(name, false, false)

		if (validationError !== '') {
			throw new Error(validationError)
		}

		usedNames.push(name)

		if (name === file.name) {
			return file
		}

		return new File([file], name, {
			type: file.type,
			lastModified: file.lastModified,
		})
	})
}

/**
 * Check whether a paste target is editable.
 *
 * @param target - The event target
 */
function isEditableTarget(target: EventTarget | null): boolean {
	return target instanceof HTMLElement
		&& (
			target instanceof HTMLInputElement
			|| target instanceof HTMLTextAreaElement
			|| target instanceof HTMLSelectElement
			|| target.isContentEditable
		)
}

/**
 * Check whether a visible modal dialog is open.
 */
function hasOpenDialog(): boolean {
	return Array.from(document.getElementsByClassName('modal-mask'))
		.some((element) => element.checkVisibility())
}

/**
 * Resolve filename conflicts using the existing conflict picker.
 *
 * @param names - Incoming filenames
 * @param path - Destination path
 * @param files - Prepared clipboard image files
 * @param getContents - Function to retrieve destination contents
 */
async function handleConflicts(
	names: string[],
	path: string,
	files: File[],
	getContents: ClipboardUploadContext['getContents'],
): Promise<Record<string, string> | false> {
	try {
		const contents = await getContents(path)
		const incomingFiles = files.filter((file) => names.includes(file.name))
		const conflicts = getConflicts(incomingFiles, contents)

		const mapping = Object.fromEntries(
			names.map((name) => [name, name]),
		)

		if (conflicts.length === 0) {
			return mapping
		}

		const existingNodes = contents.filter((node) =>
            conflicts.some((file) => file.name === node.basename),
        )
        
        const result = await openConflictPicker(
            basename(path),
            conflicts,
            existingNodes,
            { recursive: false },
        )

		if (!result) {
			return false
		}

		const usedNames = [
			...contents.map((node) => node.basename),
			...names,
		]

		for (const file of result.skipped) {
			delete mapping[file.name]
		}

		for (const file of result.renamed) {
			const name = getUniqueName(file.name, usedNames)

			mapping[file.name] = name
			usedNames.push(name)
		}

		return mapping
	} catch (error) {
		logger.error('Error during clipboard upload conflict resolution', {
			error,
		})

		showError(t('files', 'Some files could not be uploaded'))

		return false
	}
}


/**
 * Upload clipboard images into the provided destination folder.
 *
 * @param event - The clipboard paste event
 * @param context - The upload destination and contents provider
 */
export async function uploadClipboardImages(
	event: ClipboardEvent,
	context: ClipboardUploadContext,
): Promise<void> {
	if (
		event.defaultPrevented
		|| isEditableTarget(event.target)
		|| isEditableTarget(document.activeElement)
		|| hasOpenDialog()
	) {
		return
	}

	const images = getClipboardImages(event)

	if (images.length === 0) {
		return
	}

	event.preventDefault()

	const root = context.folder.source

	const upload = pendingClipboardUpload.then(async () => {
		try {
			const files = prepareClipboardImages(images)

			await getUploader().batchUpload('', files, {
				root,
				callback: (names, path) =>
					handleConflicts(names, path, files, context.getContents),
			})
		} catch (error) {
			logger.error('Error while uploading clipboard images', {
				error,
			})

			showError(t('files', 'Some files could not be uploaded'))
		}
	})

	pendingClipboardUpload = upload

	await upload
}
