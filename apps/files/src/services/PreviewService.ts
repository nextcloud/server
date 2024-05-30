/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// The preview service worker cache name (see webpack config)
const SWCacheName = 'previews'

/**
 * Check if the preview is already cached by the service worker
 */
export const isCachedPreview = function(previewUrl: string): Promise<boolean> {
	if (!window?.caches?.open) {
		return Promise.resolve(false)
	}

	return window?.caches?.open(SWCacheName)
		.then(function(cache) {
			return cache.match(previewUrl)
				.then(function(response) {
					return !!response
				})
		})
}
