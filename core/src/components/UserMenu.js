/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import AccountMenu from '../views/AccountMenu.vue'

export const setUp = () => {
	const mountPoint = document.getElementById('user-menu')
	if (mountPoint) {
		// eslint-disable-next-line no-new
		new Vue({
			name: 'AccountMenuRoot',
			el: mountPoint,
			render: h => h(AccountMenu),
		})
	}
}
