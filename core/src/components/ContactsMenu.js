/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import ContactsMenu from '../views/ContactsMenu.vue'
import ContactsMenuService from '../services/ContactsMenuService.ts'

/**
 * Set up the contacts menu component ("ContactsMenu")
 * This is the menu where users can access their contacts or other users on this instance.
 */
export function setUp() {
	const mountPoint = document.getElementById('contactsmenu')

	if (mountPoint) {
		window.OC.ContactsMenu = new ContactsMenuService()

		new Vue({
			name: 'ContactsMenuRoot',
			el: mountPoint,
			render: (h) => h(ContactsMenu),
		})
	}
}
