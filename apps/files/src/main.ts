import { PiniaVuePlugin } from 'pinia'
import { getNavigation } from '@nextcloud/files'
import { getRequestToken } from '@nextcloud/auth'
import Vue from 'vue'

import { pinia } from './store/index.ts'
import router from './router/router'
import RouterService from './services/RouterService'
import SettingsModel from './models/Setting.js'
import SettingsService from './services/Settings.js'
import FilesApp from './FilesApp.vue'

// @ts-expect-error __webpack_nonce__ is injected by webpack
__webpack_nonce__ = btoa(getRequestToken())

declare global {
	interface Window {
		OC: any;
		OCA: any;
		OCP: any;
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

// Init Navigation Service
// This only works with Vue 2 - with Vue 3 this will not modify the source but return just a oberserver
const Navigation = Vue.observable(getNavigation())
Vue.prototype.$navigation = Navigation

// Init Files App Settings Service
const Settings = new SettingsService()
Object.assign(window.OCA.Files, { Settings })
Object.assign(window.OCA.Files.Settings, { Setting: SettingsModel })

const FilesAppVue = Vue.extend(FilesApp)
new FilesAppVue({
	router,
	pinia,
}).$mount('#content')
