/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import VTooltipPlugin from 'v-tooltip'
import { sync } from 'vuex-router-sync'
import { t, n } from '@nextcloud/l10n'

import SettingsApp from './views/SettingsApp.vue'
import router from './router/index.ts'
import { useStore } from './store/index.js'
import { getCSPNonce } from '@nextcloud/auth'
import { PiniaVuePlugin, createPinia } from 'pinia'

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

const store = useStore()
sync(store, router)

// bind to window
Vue.prototype.t = t
Vue.prototype.n = n
Vue.use(PiniaVuePlugin)
Vue.use(VTooltipPlugin, { defaultHtml: false })

const pinia = createPinia()

export default new Vue({
	router,
	store,
	pinia,
	render: h => h(SettingsApp),
	el: '#content',
})
