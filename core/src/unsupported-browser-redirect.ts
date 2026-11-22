/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Runs before core defines the OC global, so its config is read once the page is loaded
window.addEventListener('DOMContentLoaded', async function() {
	if (window.TESTING || window.OC?.config?.no_unsupported_browser_warning) {
		return
	}
	const { testSupportedBrowser } = await import('./utils/RedirectUnsupportedBrowsers.js')
	testSupportedBrowser()
})
