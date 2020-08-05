import Vue from 'vue'
import App from './App.vue'
import { translate as t } from '@nextcloud/l10n'
Vue.prototype.t = t

// FIXME workaround to make the sidebar work
Object.assign(window.OCA.Files, { App: { fileList: { filesClient: OC.Files.getClient() } } }, window.OCA.Files)

const Dashboard = Vue.extend(App)
const Instance = new Dashboard({}).$mount('#app-content-vue')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
	registerStatus: (app, callback) => Instance.registerStatus(app, callback),
}
