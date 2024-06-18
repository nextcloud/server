/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import VTooltip from 'v-tooltip'

import AuthTokenSection from './components/AuthTokenSection.vue'
import { PiniaVuePlugin, createPinia } from 'pinia'

import '@nextcloud/password-confirmation/dist/style.css'

const pinia = createPinia()

Vue.use(PiniaVuePlugin)
Vue.use(VTooltip, { defaultHtml: false })
Vue.prototype.t = t

const View = Vue.extend(AuthTokenSection)
new View({ pinia }).$mount('#security-authtokens')
