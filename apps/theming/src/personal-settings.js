/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getRequestToken } from '@nextcloud/auth'
import Vue from 'vue'

import { refreshStyles } from './helpers/refreshStyles.js'
import App from './UserTheming.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

Vue.prototype.OC = OC
Vue.prototype.t = t

const View = Vue.extend(App)
const theming = new View()
theming.$mount('#theming')
theming.$on('update:background', refreshStyles)
