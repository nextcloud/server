import Vue from 'vue';
import { sync } from 'vuex-router-sync';
import App from './App.vue';
import router from './router';
import store from './store';
require("babel-polyfill");


sync(store, router);

// bind to window
Vue.prototype.t = t;
Vue.prototype.OC = OC;
Vue.prototype.oc_userconfig = oc_userconfig;
Vue.prototype.oc_current_user = oc_current_user;

const app = new Vue({
	router,
	store,
	render: h => h(App)
}).$mount('#content');

export { app, router, store };