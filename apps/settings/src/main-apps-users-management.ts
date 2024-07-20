/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import VTooltip from 'v-tooltip'
import { sync } from 'vuex-router-sync'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import SettingsApp from './views/SettingsApp.vue'
import router from './router/index.ts'
import { useStore } from './store/index.js'
import { getRequestToken } from '@nextcloud/auth'
import { PiniaVuePlugin, createPinia } from 'pinia'

Vue.use(VTooltip, { defaultHtml: false })

const store = useStore()
sync(store, router)

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken() ?? '')

// bind to window
Vue.prototype.t = t
Vue.prototype.n = n
Vue.use(PiniaVuePlugin)

const pinia = createPinia()

export default new Vue({
	router,
	store,
	pinia,
	render: h => h(SettingsApp),
	el: '#content',
})
