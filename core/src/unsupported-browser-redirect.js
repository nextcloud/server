/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

if (!window.TESTING && !OC?.config?.no_unsupported_browser_warning) {
	window.addEventListener('DOMContentLoaded', async function() {
		const { testSupportedBrowser } = await import('./utils/RedirectUnsupportedBrowsers.js')
		testSupportedBrowser()
	})
}
