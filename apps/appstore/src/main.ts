/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { n, t } from '@nextcloud/l10n'
import { createPinia, PiniaVuePlugin } from 'pinia'
import VTooltipPlugin from 'v-tooltip'
import Vue from 'vue'
import Vuex from 'vuex'
import { sync } from 'vuex-router-sync'
import App from './views/App.vue'
import router from './router/index.ts'
import { useStore } from './store/index.js'

// CSP config for webpack dynamic chunk loading

__webpack_nonce__ = getCSPNonce()

// bind to window
Vue.prototype.t = t
Vue.prototype.n = n
Vue.use(PiniaVuePlugin)
Vue.use(VTooltipPlugin, { defaultHtml: false })
Vue.use(Vuex)

const store = useStore()
sync(store, router)

const pinia = createPinia()

export default new Vue({
	router,
	store,
	pinia,
	render: (h) => h(App),
	el: '#content',
})
