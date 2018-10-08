import Vue from 'vue'

import AdminTwoFactor from './components/AdminTwoFactor'

Vue.prototype.t = t;

new Vue({
	el: '#two-factor-auth-settings',
	template: '<AdminTwoFactor/>',
	components: {
		AdminTwoFactor
	}
})
