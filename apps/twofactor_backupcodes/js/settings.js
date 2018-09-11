import Vue from 'vue';
import PersonalSettings from './views/PersonalSettings';

Vue.prototype.t = t;

export default new Vue({
	el: '#twofactor-backupcodes-settings',
	render: h => h(PersonalSettings)
});
