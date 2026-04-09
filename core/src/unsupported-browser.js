/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'
import UnsupportedBrowser from './views/UnsupportedBrowser.vue'
import browserStorage from './services/BrowserStorageService.js'
import { browserStorageKey } from './utils/RedirectUnsupportedBrowsers.js'

// If the ignore token is set, redirect
if (browserStorage.getItem(browserStorageKey) === 'true') {
	window.location = generateUrl('/')
}

export default new Vue({
	el: '#unsupported-browser',
	name: 'UnsupportedBrowserRoot',
	render: (h) => h(UnsupportedBrowser),
})
