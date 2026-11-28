/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import browserStorage from '../services/BrowserStorageService.ts'
import { logger } from './logger.ts'

export const browserStorageKey = 'unsupported-browser-ignore'
const supportedBrowsersRegExp = new RegExp(__SUPPORTED_BROWSERS_REGEXP__)
const redirectPath = generateUrl('/unsupported')

const isBrowserOverridden = browserStorage.getItem(browserStorageKey) === 'true'

/**
 * Test the current browser user agent against our official browserslist config
 * and redirect if unsupported
 */
export function testSupportedBrowser() {
	if (supportedBrowsersRegExp.test(navigator.userAgent)) {
		logger.debug('this browser is officially supported ! 🚀')
		return
	}

	// If incompatible BUT ignored, let's keep going
	if (isBrowserOverridden) {
		logger.debug('this browser is NOT supported but has been manually overridden ! ⚠️')
		return
	}

	// If incompatible, NOT overridden AND NOT already on the warning page,
	// redirect to the unsupported warning page
	if (window.location.pathname.indexOf(redirectPath) === -1) {
		const redirectUrl = window.location.href.replace(window.location.origin, '')
		const base64Param = btoa(redirectUrl)
		history.pushState(null, null, `${redirectPath}?redirect_url=${base64Param}`)
		window.location.reload()
	}
}
