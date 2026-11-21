/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'

export interface FileTypeOption {
	id: string
	label: string
}

/** File extension like `.pdf` or `.tar.gz`, same rule as on the server */
const FILE_EXTENSION_PATTERN = /^\.[^\s/,*]+$/

/**
 * Get the MIME types known to the server and the most common MIME type groups.
 */
export function getFileTypeOptions(): FileTypeOption[] {
	const groups = [
		{ id: 'image/*', label: t('files_sharing', 'All images') },
		{ id: 'video/*', label: t('files_sharing', 'All videos') },
		{ id: 'audio/*', label: t('files_sharing', 'All audio files') },
		{ id: 'text/*', label: t('files_sharing', 'All text files') },
	]
	const mimeTypes = Object.entries(window.OC?.MimeTypeList?.names ?? {})
		.map(([mimeType, name]) => ({ id: mimeType, label: name }))
		.sort((a, b) => a.label.localeCompare(b.label))

	return [...groups, ...mimeTypes]
		.map(({ id, label }) => ({ id, label: `${label} (${id})` }))
}

/**
 * Create an option for a file extension entered by the user, e.g. "pdf", ".PDF" or "*.pdf"
 *
 * @param text The entered text
 */
export function createExtensionOption(text: string): FileTypeOption {
	const extension = '.' + text.trim().toLowerCase().replace(/^\*?\.?/, '')
	return { id: extension, label: extension }
}

/**
 * Only MIME types from the list and valid file extensions can be selected
 *
 * @param option The option to check
 * @param options The MIME type options known to the server
 */
export function isSelectableFileTypeOption(option: FileTypeOption, options: FileTypeOption[]): boolean {
	return FILE_EXTENSION_PATTERN.test(option.id) || options.some(({ id }) => id === option.id)
}

/**
 * Get the options for the given allowed file types, keeping types that are not in the list
 *
 * @param allowedFileTypes File extensions, MIME types or MIME type groups
 * @param options The MIME type options known to the server
 */
export function toFileTypeOptions(allowedFileTypes: string[], options: FileTypeOption[]): FileTypeOption[] {
	return allowedFileTypes.map((type) => options.find(({ id }) => id === type) ?? { id: type, label: type })
}
