/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { PiniaVuePlugin, createPinia } from 'pinia'
import VTooltipPlugin from 'v-tooltip'
import Vue from 'vue'

import AuthTokenSection from './components/AuthTokenSection.vue'

import '@nextcloud/password-confirmation/dist/style.css'

// eslint-disable-next-line camelcase
__webpack_nonce__ = getCSPNonce()

const pinia = createPinia()

Vue.use(PiniaVuePlugin)
Vue.use(VTooltipPlugin, { defaultHtml: false })
Vue.prototype.t = t

const View = Vue.extend(AuthTokenSection)
new View({ pinia }).$mount('#security-authtokens')
