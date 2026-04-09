/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { createPinia, PiniaVuePlugin } from 'pinia'
import VTooltipPlugin from 'v-tooltip'
import Vue from 'vue'
import AuthTokenSection from './components/AuthTokenSection.vue'

__webpack_nonce__ = getCSPNonce()

const pinia = createPinia()

Vue.use(PiniaVuePlugin)
Vue.use(VTooltipPlugin, { defaultHtml: false })
Vue.prototype.t = t

const View = Vue.extend(AuthTokenSection)
new View({ pinia }).$mount('#security-authtokens')
