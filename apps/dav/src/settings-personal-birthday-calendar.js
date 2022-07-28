import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import BirthdayCalendarSettings from './views/BirthdayCalendarSettings'

Vue.prototype.$t = translate

const View = Vue.extend(BirthdayCalendarSettings);

(new View({})).$mount('#settings-personal-birthday-calendar')
