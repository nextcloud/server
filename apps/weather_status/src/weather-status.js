/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'
import App from './App.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

Vue.prototype.t = t

document.addEventListener('DOMContentLoaded', function() {
	if (!OCA.Dashboard) {
		return
	}

	OCA.Dashboard.registerStatus('weather', (el) => {
		const Dashboard = Vue.extend(App)
		return new Dashboard().$mount(el)
	})
})
