/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { defineCustomElement } from 'vue'
import Images from '../components/Images.vue'
import { registerHandler } from '../api_package/index.ts'
import { logger } from '../services/logger.ts'

const enabledPreviewProviders = loadState<string[]>('viewer', 'enabled_preview_providers', [])

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
/**
 *
 */
function filterEnabledMimes() {
	return previewSupportedMimes.filter((filter) => {
		return enabledPreviewProviders.findIndex((mimeRegex) => {
			// Remove leading and trailing slash from string regex
			const regex = new RegExp(mimeRegex.replace(/^\/|\/$/g, ''), 'i')
			return filter.match(regex)
		}) > -1
	})
}

const enabledMimes = filterEnabledMimes()
const ignoredMimes = previewSupportedMimes.filter((x) => !enabledMimes.includes(x))
if (ignoredMimes.length > 0) {
	logger.warn('Some mimes were ignored because they are not enabled in the server previews config', { ignoredMimes })
}

export const tagname = 'oca-viewer-image'
/**
 *
 */
export function registerImageCustomElement() {
	const ImageElement = defineCustomElement(Images, {
		shadowRoot: false,
	})

	// Register the custom element.
	customElements.define(tagname, ImageElement)
}

/**
 *
 */
export function registerImageHandler() {
	registerHandler({
		id: 'images',
		displayName: t('viewer', 'Images'),
		tagname,
		canEdit: true,

		enabled: (nodes) => {
			if (nodes.length === 0) {
				return false
			}

			return nodes.every((node) => {
				// Always allow browser supported mimes
				if (browserSupportedMimes.includes(node.mime)) {
					return true
				}

				// Only allow preview supported mimes if they are enabled in the server config
				if (enabledMimes.includes(node.mime)) {
					return true
				}
				return false
			})
		},
	})
	logger.info('Image handler registered', { tagname, enabledMimes, browserSupportedMimes })
}
