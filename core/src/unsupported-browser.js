/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'

import { browserStorageKey } from './utils/RedirectUnsupportedBrowsers.js'
import browserStorage from './services/BrowserStorageService.js'
import UnsupportedBrowser from './views/UnsupportedBrowser.vue'

// If the ignore token is set, redirect
if (browserStorage.getItem(browserStorageKey) === 'true') {
	window.location = generateUrl('/')
}

export default new Vue({
	el: '#unsupported-browser',
	// eslint-disable-next-line vue/match-component-file-name
	name: 'UnsupportedBrowserRoot',
	render: h => h(UnsupportedBrowser),
})
