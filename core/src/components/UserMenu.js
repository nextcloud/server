/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import AccountMenu from '../views/AccountMenu.vue'

/**
 *
 */
export function setUp() {
	const mountPoint = document.getElementById('user-menu')
	if (mountPoint) {
		new Vue({
			name: 'AccountMenuRoot',
			el: mountPoint,
			render: (h) => h(AccountMenu),
		})
	}
}
