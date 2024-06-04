/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import UserMenu from '../views/UserMenu.vue'

export const setUp = () => {
	const mountPoint = document.getElementById('user-menu')
	if (mountPoint) {
		// eslint-disable-next-line no-new
		new Vue({
			el: mountPoint,
			render: h => h(UserMenu),
		})
	}
}
