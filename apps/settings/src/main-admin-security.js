/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCSPNonce } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'
import AdminTwoFactor from './components/AdminTwoFactor.vue'
import EncryptionSettings from './components/Encryption/EncryptionSettings.vue'
import store from './store/admin-security.js'

__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t

store.replaceState(loadState('settings', 'mandatory2FAState'))

const View = Vue.extend(AdminTwoFactor)
new View({
	store,
}).$mount('#two-factor-auth-settings')

const EncryptionView = Vue.extend(EncryptionSettings)
new EncryptionView().$mount('#vue-admin-encryption')
