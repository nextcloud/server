/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import FilesApp from './FilesApp.vue'
import SettingsModel from './models/Setting.ts'
import { router } from './router/router.ts'
import RouterService from './services/RouterService.ts'
import SettingsService from './services/Settings.js'
import { pinia } from './store/index.ts'

import 'vite/modulepreload-polyfill'

// Init private and public Files namespace
window.OCA.Files = window.OCA.Files ?? {}
window.OCP.Files = window.OCP.Files ?? {}

// Expose router
if (!window.OCP.Files.Router) {
	const Router = new RouterService(router)
	Object.assign(window.OCP.Files, { Router })
}

// Init Files App Settings Service
const Settings = new SettingsService()
Object.assign(window.OCA.Files, { Settings })
Object.assign(window.OCA.Files.Settings, { Setting: SettingsModel })

const app = createApp(FilesApp)
app.use(pinia)
app.use((window.OCP.Files.Router as RouterService)._router)
app.mount('#content')
