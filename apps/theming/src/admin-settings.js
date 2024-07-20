/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getRequestToken } from '@nextcloud/auth'
import Vue from 'vue'

import App from './AdminTheming.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

Vue.prototype.OC = OC
Vue.prototype.t = t

const View = Vue.extend(App)
const theming = new View()
theming.$mount('#admin-theming')
