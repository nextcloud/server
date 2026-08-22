/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { isPublicShare } from '@nextcloud/sharing/public'
import { PiniaVuePlugin } from 'pinia'
import Vue from 'vue'
import FilesApp from './FilesApp.vue'
import SettingsModel from './models/Setting.ts'
import router from './router/router.ts'
import RouterService from './services/RouterService.ts'
import SettingsService from './services/Settings.js'
import { setSidebarDataProvider } from './sidebar/provider.ts'
import { createFilesStoreDataProvider } from './sidebar/providers/filesStore.ts'
import { exposeSidebarApi } from './sidebar/setup.ts'
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

// Init Pinia store
Vue.use(PiniaVuePlugin)

// The files app renders the sidebar itself, so it also provides the sidebar data.
// Same condition as for rendering it in `FilesApp.vue`.
if (!isPublicShare()) {
	setSidebarDataProvider(createFilesStoreDataProvider())
	exposeSidebarApi()
}

// Init Files App Settings Service
const Settings = new SettingsService()
Object.assign(window.OCA.Files, { Settings })
Object.assign(window.OCA.Files.Settings, { Setting: SettingsModel })

const FilesAppVue = Vue.extend(FilesApp)
new FilesAppVue({
	router: (window.OCP.Files.Router as RouterService)._router,
	pinia: getPinia(),
}).$mount('#content')
