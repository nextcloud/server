import Vue from 'vue'
import App from './App.vue'
import { translate as t } from '@nextcloud/l10n'
import VTooltip from '@nextcloud/vue/dist/Directives/Tooltip'
import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())
// eslint-disable-next-line camelcase
__webpack_public_path__ = generateFilePath('dashboard', '', 'js/')

Vue.directive('Tooltip', VTooltip)

Vue.prototype.t = t

// FIXME workaround to make the sidebar work
Object.assign(window.OCA.Files, { App: { fileList: { filesClient: OC.Files.getClient() } } }, window.OCA.Files)

const Dashboard = Vue.extend(App)
const Instance = new Dashboard({}).$mount('#app-content-vue')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
	registerStatus: (app, callback) => Instance.registerStatus(app, callback),
}
