/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'

import AdminTwoFactor from './components/AdminTwoFactor.vue'
import Encryption from './components/Encryption.vue'
import store from './store/admin-security.js'

Vue.prototype.t = t

// Not used here but required for legacy templates
window.OC = window.OC || {}
window.OC.Settings = window.OC.Settings || {}

store.replaceState(
	loadState('settings', 'mandatory2FAState'),
)

const View = Vue.extend(AdminTwoFactor)
new View({
	store,
}).$mount('#two-factor-auth-settings')

const EncryptionView = Vue.extend(Encryption)
new EncryptionView().$mount('#vue-admin-encryption')
