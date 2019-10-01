import Vue from 'vue'
import App from './Accessibility.vue'

/* global t */
// bind to window
Vue.prototype.OC = OC
Vue.prototype.t = t

export default new Vue({
	el: '#accessibility',
	render: h => h(App)
})
