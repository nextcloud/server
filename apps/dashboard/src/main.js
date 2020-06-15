import Vue from 'vue'
import App from './App.vue'
import { translate as t } from '@nextcloud/l10n'
Vue.prototype.t = t

const Dashboard = Vue.extend(App)
const Instance = new Dashboard({}).$mount('#app')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
}
