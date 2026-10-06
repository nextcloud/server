/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import { createApp } from 'vue'
import UnsupportedBrowser from './views/UnsupportedBrowser.vue'
import browserStorage from './services/BrowserStorageService.ts'
import { browserStorageKey } from './utils/RedirectUnsupportedBrowsers.js'

if (browserStorage.getItem(browserStorageKey) === 'true') {
	window.location.href = generateUrl('/')
} else {
	createApp(UnsupportedBrowser).mount('#unsupported-browser')
}
