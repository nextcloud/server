import Vue from 'vue';
import PersonalSettings from './views/PersonalSettings';
import store from './store';

Vue.prototype.t = t;

const initialStateElem = JSON.parse(atob(document.getElementById('twofactor-backupcodes-initial-state').value));
store.replaceState(
	initialStateElem
)

const View = Vue.extend(PersonalSettings)
new View({
	store
}).$mount('#twofactor-backupcodes-settings')
