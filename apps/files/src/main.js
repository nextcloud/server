import './files-app-settings.js'
import './templates.js'
import './legacy/filelistSearch.js'
import processLegacyFilesViews from './legacy/navigationMapper.js'

import Vue from 'vue'
import NavigationService from './services/Navigation.ts'
import NavigationView from './views/Navigation.vue'

import router from './router/router.js'

// Init Files App Navigation Service
const Navigation = new NavigationService()

// Assign Navigation Service to the global OCP.Files
window.OCP.Files = window.OCP.Files ?? {}
Object.assign(window.OCP.Files, { Navigation })

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

// Init legacy files views
processLegacyFilesViews()
