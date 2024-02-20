/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author rakekniven <mark.ziegler@rakekniven.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import VTooltip from 'v-tooltip'
import { sync } from 'vuex-router-sync'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'

import SettingsApp from './views/SettingsApp.vue'
import router from './router/index.ts'
import store from './store/index.js'
import { getRequestToken } from '@nextcloud/auth'
import { PiniaVuePlugin, createPinia } from 'pinia'

Vue.use(VTooltip, { defaultHtml: false })

sync(store, router)

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken() ?? '')

// bind to window
Vue.prototype.t = t
Vue.prototype.n = n
Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA
// @ts-expect-error This is a private property we use
Vue.prototype.oc_userconfig = window.oc_userconfig
Vue.use(PiniaVuePlugin)

const pinia = createPinia()

export default new Vue({
	router,
	store,
	pinia,
	render: h => h(SettingsApp),
	el: '#content',
})
