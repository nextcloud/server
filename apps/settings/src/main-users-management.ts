/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { n, t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createPinia, PiniaVuePlugin } from 'pinia'
import VTooltipPlugin from 'v-tooltip'
import Vue from 'vue'
import UserManagement from './views/UserManagement.vue'
import router from './router/index.ts'
import { useUsersStore } from './store/users.ts'

addPasswordConfirmationInterceptors(axios)

// CSP config for webpack dynamic chunk loading

__webpack_nonce__ = getCSPNonce()

// bind to window
Vue.prototype.t = t
Vue.prototype.n = n
Vue.use(PiniaVuePlugin)
Vue.use(VTooltipPlugin, { defaultHtml: false })

const pinia = createPinia()

// Migrate legacy local storage settings to the database
useUsersStore(pinia).migrateLocalStorage()

const App = Vue.extend(UserManagement)
const app = new App({
	router,
	pinia,
})
app.$mount('#content')
