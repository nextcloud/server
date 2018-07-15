import Vue from 'vue';
import App from './App.vue';

/* global t */
// bind to window
Vue.prototype.OC = OC;
Vue.prototype.t = t;

new Vue({
	el: '#accessibility',
	render: h => h(App)
});
