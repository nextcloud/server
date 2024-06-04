/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import DashboardApp from './DashboardApp.vue'
import { translate as t } from '@nextcloud/l10n'
import VTooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import { getRequestToken } from '@nextcloud/auth'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

Vue.directive('Tooltip', VTooltip)

Vue.prototype.t = t

const Dashboard = Vue.extend(DashboardApp)
const Instance = new Dashboard({}).$mount('#app-content-vue')

window.OCA.Dashboard = {
	register: (app, callback) => Instance.register(app, callback),
	registerStatus: (app, callback) => Instance.registerStatus(app, callback),
}
