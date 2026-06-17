/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import logger from '../logger.js'

/**
 *
 * @param text
 */
function unsecuredCopyToClipboard(text) {
	const textArea = document.createElement('textarea')
	const textAreaContent = document.createTextNode(text)
	textArea.appendChild(textAreaContent)
	document.body.appendChild(textArea)

	textArea.focus({ preventScroll: true })
	textArea.select()

	try {
		// This is a fallback for browsers that do not support the Clipboard API
		// execCommand is deprecated, but it is the only way to copy text to the clipboard in some browsers
		document.execCommand('copy')
	} catch (error) {
		window.prompt(t('core', 'Clipboard not available, please copy manually'), text)
		logger.error('files Unable to copy to clipboard', { error })
	}

	document.body.removeChild(textArea)
}

/**
 *
 */
function initFallbackClipboardAPI() {
	if (!window.navigator?.clipboard?.writeText) {
		logger.info('Clipboard API not available, using fallback')
		Object.defineProperty(window.navigator, 'clipboard', {
			value: {
				writeText: unsecuredCopyToClipboard,
			},
			writable: false,
		})
	}
}

export { initFallbackClipboardAPI }
