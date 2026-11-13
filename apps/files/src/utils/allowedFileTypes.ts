/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { isPublicShare } from '@nextcloud/sharing/public'

/**
 * Get the file types that can be uploaded to the current public share.
 * Uses the format of the HTML `accept` attribute, an empty list means no restriction.
 */
export function getAllowedFileTypes(): string[] {
	if (!isPublicShare()) {
		return []
	}

	return loadState<string[]>('files_sharing', 'allowedFileTypes', [])
}

/**
 * Check if a file matches one of the allowed file types.
 * If the list contains MIME types, the file is left to the server to check.
 *
 * @param file The file to check
 * @param allowedFileTypes File extensions (`.pdf`), MIME types (`application/pdf`) or MIME type groups (`image/*`)
 */
export function isAllowedFileType(file: File, allowedFileTypes: string[]): boolean {
	if (allowedFileTypes.length === 0) {
		return true
	}

	/**
	 * Main idea: Browsers take MIME types from the OS (application/x-zip-compressed e.g.),
	 * which can differ from the server detection, so MIME rules are checked by the server only.
	 * We will only check extensions here, and if the list contains MIME types, we will let the server handle it.
	 * Chunking will diclined unsupported file types
	 * 
	 * But if extensions are provided, we will check them here, as the server will not check them.
	 */
	if (allowedFileTypes.some((type) => !type.startsWith('.'))) {
		return true
	}

	const name = file.name.toLowerCase()

	return allowedFileTypes.some((type) => name.endsWith(type.toLowerCase()))
}
