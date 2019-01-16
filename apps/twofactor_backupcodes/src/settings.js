import Vue from 'vue';
import PersonalSettings from './views/PersonalSettings';
import store from './store';

Vue.prototype.t = t;

const initialStateElem = document.getElementById('twofactor-backupcodes-initial-state');
store.replaceState(
	JSON.parse(atob(initialStateElem.value))
)

const View = Vue.extend(PersonalSettings)
new View({
	store
}).$mount('#twofactor-backupcodes-settings')
