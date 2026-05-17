/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import logger from '../services/logger.js'
import Images from '../components/Images.vue'

const enabledPreviewProviders = loadState(appName, 'enabled_preview_providers', [])

/**
 * Those mimes needs a proper preview to be displayed
 * if they are not enabled on the server, let's not activate them.
 */
const previewSupportedMimes = [
	'image/heic',
	'image/heif',
	'image/tiff',
	'image/x-xbitmap',
	'image/emf',
]

/**
 * Those mimes are always supported by the browser
 * Since we fallback to the source image if there is no
 * preview, we can always include them.
 */
const browserSupportedMimes = [
	'image/apng',
	'image/bmp',
	'image/gif',
	'image/jpeg',
	'image/png',
	'image/svg+xml',
	'image/webp',
	'image/x-icon',
]

// Filter out supported mimes that are _not_
// enabled in the preview API
const filterEnabledMimes = () => {
	return previewSupportedMimes.filter(filter => {
		return enabledPreviewProviders.findIndex(mimeRegex => {
			// Remove leading and trailing slash from string regex
			const regex = new RegExp(mimeRegex.replace(/^\/|\/$/g, ''), 'i')
			return filter.match(regex)
		}) > -1
	})
}

const enabledMimes = filterEnabledMimes()
const ignoredMimes = previewSupportedMimes.filter(x => !enabledMimes.includes(x))
if (ignoredMimes.length > 0) {
	logger.warn('Some mimes were ignored because they are not enabled in the server previews config', { ignoredMimes })
}

export default {
	id: 'images',
	group: 'media',
	mimes: [
		...browserSupportedMimes,
		...enabledMimes,
	],
	component: Images,
}
