/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import Vue, { defineAsyncComponent } from 'vue'

__webpack_nonce__ = getCSPNonce()

const LoginFlowAuth = defineAsyncComponent(() => import('./views/LoginFlowAuth.vue'))
const LoginFlowGrant = defineAsyncComponent(() => import('./views/LoginFlowGrant.vue'))
const LoginFlowDone = defineAsyncComponent(() => import('./views/LoginFlowDone.vue'))

const state = loadState<'auth' | 'grant' | 'done'>('core', 'loginFlowState')
const app = new Vue({
	render: (h) => h(state === 'auth'
		? LoginFlowAuth
		: (state === 'grant' ? LoginFlowGrant : LoginFlowDone)),
})
app.$mount('#core-loginflow')
