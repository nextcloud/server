import { getCSPNonce } from '@nextcloud/auth'
import { PiniaVuePlugin } from 'pinia'
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import LDAPSettingsApp from './LDAPSettingsApp.vue'
import { pinia } from './store/index.ts'

__webpack_nonce__ = getCSPNonce()

// Init Pinia store
Vue.use(PiniaVuePlugin)

const LDAPSettingsAppVue = Vue.extend(LDAPSettingsApp)
new LDAPSettingsAppVue({
	name: 'LDAPSettingsApp',
	pinia,
}).$mount('#content-ldap-settings')
