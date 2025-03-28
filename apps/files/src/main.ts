/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCSPNonce } from '@nextcloud/auth'
import { PiniaVuePlugin } from 'pinia'
import Vue from 'vue'

import { pinia } from './store/index.ts'
import router from './router/router'
import RouterService from './services/RouterService'
import SettingsModel from './models/Setting.js'
import SettingsService from './services/Settings.js'
import FilesApp from './FilesApp.vue'

__webpack_nonce__ = getCSPNonce()

declare global {
	interface Window {
		OC: Nextcloud.v29.OC
		OCP: Nextcloud.v29.OCP
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		OCA: Record<string, any>
	}
}

// Init private and public Files namespace
window.OCA.Files = window.OCA.Files ?? {}
window.OCP.Files = window.OCP.Files ?? {}

// Expose router
const Router = new RouterService(router)
Object.assign(window.OCP.Files, { Router })

// Init Pinia store
Vue.use(PiniaVuePlugin)

// Init Files App Settings Service
const Settings = new SettingsService()
Object.assign(window.OCA.Files, { Settings })
Object.assign(window.OCA.Files.Settings, { Setting: SettingsModel })

const FilesAppVue = Vue.extend(FilesApp)
new FilesAppVue({
	router,
	pinia,
}).$mount('#content')
