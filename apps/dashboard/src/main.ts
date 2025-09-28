/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import DashboardApp from './DashboardApp.vue'


const app = createApp(DashboardApp)
app.mount('#content')

window.OCA.Dashboard = {
	register: (app, callback) => app.register(app, callback),
	registerStatus: (app, callback) => app.registerStatus(app, callback),
}
