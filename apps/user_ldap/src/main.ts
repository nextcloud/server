/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import { PiniaVuePlugin } from 'pinia'
import { getCSPNonce } from '@nextcloud/auth'

import { pinia } from './store/index'
import LDAPSettingsApp from './LDAPSettingsApp.vue'

__webpack_nonce__ = getCSPNonce()

// Init Pinia store
Vue.use(PiniaVuePlugin)

const LDAPSettingsAppVue = Vue.extend(LDAPSettingsApp)
new LDAPSettingsAppVue({
	pinia,
}).$mount('#content-ldap-settings')
