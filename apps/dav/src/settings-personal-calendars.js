import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import Calendars from './views/Calendars.vue'

Vue.prototype.$t = translate

const View = Vue.extend(Calendars);

(new View({})).$mount('#settings-personal-calendars')
