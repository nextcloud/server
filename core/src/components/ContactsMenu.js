/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import ContactsMenu from '../views/ContactsMenu.vue'

/**
 * @todo move to contacts menu code https://github.com/orgs/nextcloud/projects/31#card-21213129
 */
export const setUp = () => {
	const mountPoint = document.getElementById('contactsmenu')
	if (mountPoint) {
		// eslint-disable-next-line no-new
		new Vue({
			name: 'ContactsMenuRoot',
			el: mountPoint,
			render: h => h(ContactsMenu),
		})
	}
}
