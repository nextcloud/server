/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import DashboardApp from './DashboardApp.vue'

const app = createApp(DashboardApp)
app.mixin({ methods: { t, n } })
const Instance = app.mount('#app-content-vue')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
	registerStatus: (app, callback) => Instance.registerStatus(app, callback),
}
