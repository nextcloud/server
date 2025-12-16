/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

import 'vite/modulepreload-polyfill'

const internalOnly = loadState('federatedfilesharing', 'internalOnly', false)

if (!internalOnly) {
	const app = createApp(AdminSettings)
	app.mount('#vue-admin-federated')
}
