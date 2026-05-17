/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { FileInfo } from './fileUtils'

/**
 * Check if download permissions are granted for a file
 * @param fileInfo The file info to check
 */
export function canDownload(fileInfo: FileInfo) {
	if (fileInfo.hideDownload) {
		return false
	}

	// TODO: This should probably be part of `@nextcloud/sharing`
	// check share attributes
	const shareAttributes = typeof fileInfo?.shareAttributes === 'string' ? JSON.parse(fileInfo.shareAttributes || '[]') : fileInfo?.shareAttributes

	if (shareAttributes && shareAttributes.length > 0) {
		const downloadAttribute = shareAttributes.find(({ scope, key }) => scope === 'permissions' && key === 'download')
		// We only forbid download if the attribute is *explicitly* set to 'false'
		return downloadAttribute?.value !== false
	}
	// otherwise return true (as the file needs read permission otherwise we would not have opened it)
	return true
}
