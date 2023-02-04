import './templates.js'
import './legacy/filelistSearch.js'
import processLegacyFilesViews from './legacy/navigationMapper.js'

import Vue from 'vue'
import { createPinia, PiniaVuePlugin } from 'pinia'

import NavigationService from './services/Navigation.ts'

import NavigationView from './views/Navigation.vue'
import FilesListView from './views/FilesList.vue'

import SettingsService from './services/Settings.js'
import SettingsModel from './models/Setting.js'

import router from './router/router.js'

// Init private and public Files namespace
window.OCA.Files = window.OCA.Files ?? {}
window.OCP.Files = window.OCP.Files ?? {}

// Init Navigation Service
const Navigation = new NavigationService()
Object.assign(window.OCP.Files, { Navigation })

// Init Files App Settings Service
const Settings = new SettingsService()
Object.assign(window.OCA.Files, { Settings })
Object.assign(window.OCA.Files.Settings, { Setting: SettingsModel })

// Init Navigation View
const View = Vue.extend(NavigationView)
const FilesNavigationRoot = new View({
	name: 'FilesNavigationRoot',
	propsData: {
		Navigation,
	},
	router,
})
FilesNavigationRoot.$mount('#app-navigation-files')

// Init Pinia store
Vue.use(PiniaVuePlugin)
const pinia = createPinia()

// Init content list view
const ListView = Vue.extend(FilesListView)
const FilesList = new ListView({
	name: 'FilesListRoot',
	propsData: {
		Navigation,
	},
	router,
	pinia,
})
FilesList.$mount('#app-content-vue')

// Init legacy files views
processLegacyFilesViews()
