import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import Availability from './views/Availability.vue'

Vue.prototype.$t = translate

const View = Vue.extend(Availability);

(new View({})).$mount('#settings-personal-availability')
