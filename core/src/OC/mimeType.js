/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'

const iconCache = new Map()

/**
 * Return the url to icon of the given mimeType
 *
 * @param {string} mimeType The mimeType to get the icon for
 * @return {string} Url to the icon for mimeType
 */
export function getIconUrl(mimeType) {
	if (typeof mimeType === 'undefined') {
		return undefined
	}

	while (mimeType in window.OC.MimeTypeList.aliases) {
		mimeType = window.OC.MimeTypeList.aliases[mimeType]
	}

	if (!iconCache.has(mimeType)) {
		// First try to get the correct icon from the current theme
		let gotIcon = null
		let path = ''
		if (OC.theme.folder !== '' && Array.isArray(OC.MimeTypeList.themes[OC.theme.folder])) {
			path = generateUrl('/themes/' + window.OC.theme.folder + '/core/img/filetypes/')
			const icon = getMimeTypeIcon(mimeType, window.OC.MimeTypeList.themes[OC.theme.folder])

			if (icon !== null) {
				gotIcon = true
				path += icon
			}
		}
		if (window.OCA.Theming && gotIcon === null) {
			path = generateUrl('/apps/theming/img/core/filetypes/')
			path += getMimeTypeIcon(mimeType, window.OC.MimeTypeList.files)
			gotIcon = true
		}

		// If we do not yet have an icon fall back to the default
		if (gotIcon === null) {
			path = generateUrl('/core/img/filetypes/')
			path += getMimeTypeIcon(mimeType, window.OC.MimeTypeList.files)
		}

		path += '.svg'

		if (window.OCA.Theming) {
			path += '?v=' + window.OCA.Theming.cacheBuster
		}

		// Cache the result
		iconCache.set(mimeType, path)
	}

	return iconCache.get(mimeType)
}

/**
 * Return the file icon we want to use for the given mimeType.
 * The file needs to be present in the supplied file list
 *
 * @param {string} mimeType The mimeType we want an icon for
 * @param {string[]} files The available icons in this theme
 * @return {string | null} The icon to use or null if there is no match
 */
function getMimeTypeIcon(mimeType, files) {
	const icon = mimeType.replace(new RegExp('/', 'g'), '-')

	// Generate path
	if (mimeType === 'dir' && files.includes('folder')) {
		return 'folder'
	} else if (mimeType === 'dir-encrypted' && files.includes('folder-encrypted')) {
		return 'folder-encrypted'
	} else if (mimeType === 'dir-shared' && files.includes('folder-shared')) {
		return 'folder-shared'
	} else if (mimeType === 'dir-public' && files.includes('folder-public')) {
		return 'folder-public'
	} else if ((mimeType === 'dir-external' || mimeType === 'dir-external-root') && files.includes('folder-external')) {
		return 'folder-external'
	} else if (files.includes(icon)) {
		return icon
	} else if (files.includes(icon.split('-')[0])) {
		return icon.split('-')[0]
	} else if (files.includes('file')) {
		return 'file'
	}

	return null
}
