/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { createApp } from 'vue'
import LDAPSettingsApp from './LDAPSettingsApp.vue'
import { pinia } from './store/index.ts'

const app = createApp(LDAPSettingsApp)
app.use(pinia)
app.mount('#content-ldap-settings')
