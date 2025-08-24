/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// The preview service worker cache name (see webpack config)
const SWCacheName = 'previews'

/**
 * Check if the preview is already cached by the service worker
 * @param previewUrl URL to check
 */
export async function isCachedPreview(previewUrl: string): Promise<boolean> {
	if (!window?.caches?.open) {
		return false
	}

	const cache = await window.caches.open(SWCacheName)
	const response = await cache.match(previewUrl)
	return response !== undefined
}
