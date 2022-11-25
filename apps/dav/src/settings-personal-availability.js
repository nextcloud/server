import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import { getRequestToken } from '@nextcloud/auth'

__webpack_nonce__ = btoa(getRequestToken())

Vue.prototype.$t = translate

import('./views/Availability').then((module) => {
	const Availability = module.default
	const View = Vue.extend(Availability);

	(new View({})).$mount('#settings-personal-availability')
})
