import Vue from 'vue'
import App from './App.vue'

const Dashboard = Vue.extend(App)
const Instance = new Dashboard({}).$mount('#app')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
}
