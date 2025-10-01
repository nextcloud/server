/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { subscribe } from '@nextcloud/event-bus'
import Vue from 'vue'
import UserStatus from './UserStatus.vue'
import store from './store/index.js'

__webpack_nonce__ = getCSPNonce()

Vue.prototype.t = t
Vue.prototype.$t = t

const mountPoint = document.getElementById('user_status-menu-entry')

/**
 *
 */
function mountMenuEntry() {
	const mountPoint = document.getElementById('user_status-menu-entry')

	new Vue({
		el: mountPoint,
		render: (h) => h(UserStatus),
		store,
	})
}

if (mountPoint) {
	mountMenuEntry()
} else {
	subscribe('core:user-menu:mounted', mountMenuEntry)
}

// Register dashboard status
document.addEventListener('DOMContentLoaded', function() {
	if (!OCA.Dashboard) {
		return
	}

	OCA.Dashboard.registerStatus('status', (el) => {
		const Dashboard = Vue.extend(UserStatus)
		return new Dashboard({
			propsData: {
				inline: true,
			},
			store,
		}).$mount(el)
	})
})
