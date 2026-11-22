/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { createApp, defineAsyncComponent } from 'vue'

const views = {
	auth: defineAsyncComponent(() => import('./views/LoginFlowAuth.vue')),
	grant: defineAsyncComponent(() => import('./views/LoginFlowGrant.vue')),
	'auth-v2': defineAsyncComponent(() => import('./views/LoginFlowV2.vue')),
	'grant-v2': defineAsyncComponent(() => import('./views/LoginFlowV2.vue')),
	done: defineAsyncComponent(() => import('./views/LoginFlowDone.vue')),
}

const state = loadState<keyof typeof views>('core', 'loginFlowState')
createApp(views[state] ?? views.done).mount('#core-loginflow')
