import Vue from 'vue';
import VTooltip from 'v-tooltip';

import AuthSecurityPassword from './components/AuthSecurityPassword';

__webpack_nonce__ = btoa(OC.requestToken);

Vue.use(VTooltip);
Vue.prototype.t = t;

const View = Vue.extend(AuthSecurityPassword);
new View().$mount('#security-password');
