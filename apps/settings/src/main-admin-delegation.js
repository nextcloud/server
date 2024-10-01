/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import App from './components/AdminDelegating.vue'

// bind to window
Vue.prototype.OC = OC
Vue.prototype.t = t

const View = Vue.extend(App)
const accessibility = new View()
accessibility.$mount('#admin-right-sub-granting')
