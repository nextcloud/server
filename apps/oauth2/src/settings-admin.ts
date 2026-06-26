/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createApp } from 'vue'
import AdminSettings from './views/AdminSettings.vue'

import 'vite/modulepreload-polyfill'

addPasswordConfirmationInterceptors(axios)

const clients = loadState('oauth2', 'clients')

const app = createApp(AdminSettings, {
	modelValue: clients,
})
app.mount('#oauth2')
