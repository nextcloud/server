/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import FilesApp from './FilesApp.vue'
import SettingsModel from './models/Setting.ts'
import router from './router/router.ts'
import RouterService from './services/RouterService.ts'
import SettingsService from './services/Settings.js'
import { renderFilesView } from './services/renderFilesView.ts'
import { getPinia } from './store/index.ts'

__webpack_nonce__ = getCSPNonce()

// Init private and public Files namespace
window.OCA.Files = window.OCA.Files ?? {}
window.OCP.Files = window.OCP.Files ?? {}

// Expose router
if (!window.OCP.Files.Router) {
	const Router = new RouterService(router)
	Object.assign(window.OCP.Files, { Router })
}

// Expose the ability to render the Files UI on a foreign page
window.OCP.Files.renderFilesApp ??= renderFilesView

// Init Pinia store
Vue.use(PiniaVuePlugin)

// Init Files App Settings Service
const Settings = new SettingsService()
Object.assign(window.OCA.Files, { Settings })
Object.assign(window.OCA.Files.Settings, { Setting: SettingsModel })

// Other apps load this same script to get `OCP.Files.renderFilesApp()`.
// Only take over `#content` when we are actually on the Files app's own
// page - identifiable by the app-specific class core's layout adds to it.
const contentEl = document.getElementById('content')
if (contentEl?.classList.contains('app-files')) {
	const FilesAppVue = Vue.extend(FilesApp)
	new FilesAppVue({
		router: (window.OCP.Files.Router as RouterService)._router,
		pinia: getPinia(),
	}).$mount(contentEl)
}
