import Vue from 'vue'

import AdminTwoFactor from './components/AdminTwoFactor.vue'
import store from './store/'

__webpack_nonce__ = btoa(OC.requestToken)

Vue.prototype.t = t;

// Not used here but required for legacy templates
window.OC = window.OC || {};
window.OC.Settings = window.OC.Settings || {};

let initialState = OCP.InitialState.loadState('settings', 'mandatory2FAState');
store.commit('setEnforced', initialState.enforced);
store.commit('setEnforcedGroups', initialState.enforcedGroups);
store.commit('setExcludedGroups', initialState.excludedGroups);

const View = Vue.extend(AdminTwoFactor)
new View({
	store
}).$mount('#two-factor-auth-settings')
