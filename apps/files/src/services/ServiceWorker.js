/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl, getRootUrl } from '@nextcloud/router'
import logger from '../logger.ts'

export default () => {
	if ('serviceWorker' in navigator) {
		// Use the window load event to keep the page load performant
		window.addEventListener('load', async () => {
			try {
				const url = generateUrl('/apps/files/preview-service-worker.js', {}, { noRewrite: true })
				const scope = getRootUrl()
				const registration = await navigator.serviceWorker.register(url, { scope })
				logger.debug('SW registered: ', { registration })
			} catch (error) {
				logger.error('SW registration failed: ', { error })
			}
		})
	} else {
		logger.debug('Service Worker is not enabled on this browser.')
	}
}
