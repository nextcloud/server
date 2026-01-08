/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import DashboardApp from './DashboardApp.vue'

__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t

const Dashboard = Vue.extend(DashboardApp)
const Instance = new Dashboard({}).$mount('#app-content-vue')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
	registerStatus: (app, callback) => Instance.registerStatus(app, callback),
}
