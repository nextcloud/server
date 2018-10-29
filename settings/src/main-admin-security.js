import Vue from 'vue'

import AdminTwoFactor from './components/AdminTwoFactor.vue'

__webpack_nonce__ = btoa(OC.requestToken)

Vue.prototype.t = t;

const View = Vue.extend(AdminTwoFactor)
new View().$mount('#two-factor-auth-settings')
