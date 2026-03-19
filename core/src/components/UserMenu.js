/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import AccountMenu from '../views/AccountMenu.vue'

/**
 * Set up the user menu component ("AccountMenu")
 * This is the top right menu where users can access their settings, profile, logout, etc.
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
